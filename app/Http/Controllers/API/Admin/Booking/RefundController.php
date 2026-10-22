<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Jobs\CheckRefundStatuses;
use App\Jobs\ProcessRefund;
use App\Models\RefundRequest;
use App\Services\Refund\RefundNotificationMailer;
use App\Services\Refund\RefundQuoteService;
use App\Services\Refund\RefundStatusRefresher;
use App\Services\Refund\SecretScrubber;
use App\Services\Refund\SslCommerzRefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RefundController extends BaseController
{
    public function __construct(
        private readonly RefundQuoteService $refundQuoteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Opening the list also finishes any refund SSLCommerz has completed (after this response is sent)
        RefundStatusRefresher::afterResponse();

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(RefundRequest::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->scoped()
            ->with([
                'user:id,name,email',
                'booking:id,booking_number,room_id,checkin,checkout,amount,status,payment_status',
                'booking.room:id,name,property_id',
                'booking.room.property:id,name',
                'transaction:id,booking_id,amount,status,payment_method,transaction_reference',
                'processor:id,name',
            ]);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('refund_number', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($booking) =>
                    $booking->where('booking_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) =>
                    $user->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // Requests that wait for a decision: the one whose check-in comes first is at the top
        if (($validated['status'] ?? null) === RefundRequest::STATUS_REQUESTED) {
            $query->orderBy(DB::raw('(select substr(bookings.checkin, 1, 10) from bookings where bookings.id = refund_requests.booking_id)'));
        }

        $page = $query->latest('requested_at')->paginate($validated['per_page'] ?? 15);
        $page->getCollection()->each(fn (RefundRequest $r) => $this->forViewer($r));

        return $this->sendSuccess($page);
    }

    public function stats(): JsonResponse
    {
        $counts = $this->scoped()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->sendSuccess([
            'total' => $counts->sum(),
            'requested' => (int) ($counts[RefundRequest::STATUS_REQUESTED] ?? 0),
            'approved' => (int) ($counts[RefundRequest::STATUS_APPROVED] ?? 0),
            'processing' => (int) ($counts[RefundRequest::STATUS_PROCESSING] ?? 0),
            'completed' => (int) ($counts[RefundRequest::STATUS_COMPLETED] ?? 0),
            'rejected' => (int) ($counts[RefundRequest::STATUS_REJECTED] ?? 0),
            'failed' => (int) ($counts[RefundRequest::STATUS_FAILED] ?? 0),
        ]);
    }

    public function show(RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);

        $refund->load([
            'user:id,name,email',
            'booking.room.property',
            'transaction',
            'processor:id,name',
        ]);

        $this->forViewer($refund);

        // For a request still waiting: exactly what Approve will do (so the admin never has to know the policies by heart)
        return $this->sendSuccess(array_merge($refund->toArray(), [
            'review' => $refund->status === RefundRequest::STATUS_REQUESTED ? $this->review($refund) : null,
        ]));
    }

    /**
     * The platform admin sees everything about a refund. A host sees the refunds of their own bookings (guest, amounts, status),
     * not the platform's private notes, SSLCommerz's answers, or the inside of the payment record (the platform's gateway fee,
     * card details).
     */
    private function forViewer(RefundRequest $refund): RefundRequest
    {
        if (auth()->user()?->is_admin) {
            return $refund;
        }

        $refund->makeHidden(RefundRequest::INTERNAL_FIELDS);
        $refund->unsetRelation('processor');
        $refund->transaction?->makeHidden(['meta', 'transaction_reference']);

        return $refund;
    }

    /**
     * What approving this request will do, judged on the day the guest asked: the property's refund policy as it was promised
     * when the booking was made, how many days before check-in the guest asked, the % that gives and the amount.
     */
    private function review(RefundRequest $refund): array
    {
        $booking = $refund->booking;
        $quote = $this->refundQuoteService->quote($booking, $refund->requested_at ?? $refund->created_at);
        $started = $this->refundQuoteService->checkInStarted($booking);

        return [
            'property_name' => $booking?->room?->property?->name,
            'asked_at' => ($refund->requested_at ?? $refund->created_at)?->toIso8601String(),
            'eligible' => $quote['eligible'],
            'reason' => $quote['reason'] ?? null,
            'policy_lines' => $quote['policy_lines'] ?? [],
            'policy_label' => $quote['policy_label'] ?? null,
            'days_before_checkin' => $quote['days_before_checkin'] ?? null,
            'hours_before_checkin' => $quote['hours_before_checkin'] ?? null,
            'refund_percentage' => $quote['refund_percentage'] ?? null,
            'original_amount' => $quote['original_amount'] ?? null,
            'will_pay' => $quote['refundable_amount'] ?? null,
            'cancellation_fee' => $quote['cancellation_fee'] ?? null,
            'check_in_started' => $started,
            'needs_stay_confirmation' => $started,
        ];
    }

    public function approve(Request $request, RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);
        $this->adminOnly();

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'confirm_not_stayed' => ['nullable', 'boolean'],
        ]);

        $approved = DB::transaction(function () use ($request, $refund, $validated) {
            $locked = RefundRequest::query()
                ->with(['booking.transaction', 'booking.refundRequests'])
                ->lockForUpdate()
                ->findOrFail($refund->id);

            if (!$locked->canTransitionTo(RefundRequest::STATUS_APPROVED)) {
                throw ValidationException::withMessages([
                    'refund' => "A {$locked->status} refund cannot be approved.",
                ]);
            }

            // A refund that FAILED (SSLCommerz said no, or could not be reached) is tried again for the SAME amount the guest was
            // already owed: nothing is worked out again (the booking is already cancelled, so it would no longer look refundable).
            if ($locked->status === RefundRequest::STATUS_FAILED) {
                if ((float) $locked->approved_amount <= 0 || $locked->booking->payment_status !== 'paid' || $locked->transaction?->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'refund' => 'This refund cannot be tried again: the payment is no longer refundable.',
                    ]);
                }

                $note = trim(($validated['admin_note'] ?? '') . ($locked->gateway_refund_id ? " (try again; the failed SSLCommerz reference was {$locked->gateway_refund_id})" : ' (try again)'));
                $locked->update([
                    'status' => RefundRequest::STATUS_APPROVED,
                    'processed_by' => $request->user()->id,
                    'admin_note' => $note !== '' ? $note : null,
                    'approved_at' => now(),
                    'processed_at' => null,
                    'failure_reason' => null,
                    'gateway_refund_id' => null,
                    'gateway_status' => null,
                    // SSLCommerz refused it: a new request, a new id. SSLCommerz may already HAVE it (no answer came back): the
                    // same id, so that SSLCommerz knows it is the same request and the guest can never be paid twice.
                    'gateway_attempt' => (int) $locked->gateway_attempt + ($locked->gateway_maybe_paid ? 0 : 1),
                ]);

                return $locked->fresh(['user', 'booking.room.property', 'transaction', 'processor']);
            }

            // Worked out again here (never trust an amount sent by the screen), but AS OF THE DAY THE GUEST ASKED: the guest
            // is owed what the policy said that day, however long the admin took.
            $asked = $locked->requested_at ?? $locked->created_at;
            $quote = $this->refundQuoteService->quote($locked->booking, $asked);
            if (!$quote['eligible']) {
                throw ValidationException::withMessages([
                    'refund' => $quote['reason'],
                ]);
            }

            // Once the check-in day has begun the guest may already be in the hotel: the admin must say they checked that
            // the guest did not stay.
            $started = $this->refundQuoteService->checkInStarted($locked->booking);
            if ($started && empty($validated['confirm_not_stayed'])) {
                throw ValidationException::withMessages([
                    'confirm_not_stayed' => 'The check-in day has begun. Check with the hotel that the guest did not stay, then tick the box to confirm.',
                ]);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'approved_amount' => $quote['refundable_amount'],
                'cancellation_fee' => $quote['cancellation_fee'],
                'not_stayed_confirmed_at' => $started ? now() : null,
                'processed_by' => $request->user()->id,
                'admin_note' => $validated['admin_note'] ?? null,
                'approved_at' => now(),
                'failure_reason' => null,
            ]);

            /*
             * Approval accepts the customer's cancellation request, so the
             * reservation must stop blocking these dates immediately.
             * Keep payment_status=paid until the gateway confirms the refund.
             */
            $locked->booking->update([
                'status' => 'cancelled',
            ]);

            // Guests see the dates as free already (availability is read from the bookings). Keep the room's own
            // label honest too, unless some other booking still holds this room or it was set aside (Maintenance...).
            $room = $locked->booking->room;
            $otherHold = \App\Models\Booking::where('room_id', $locked->booking->room_id)
                ->whereIn('status', ['reserved', 'approved'])
                ->where('id', '!=', $locked->booking->id)
                ->exists();
            if ($room && !$otherHold && in_array($room->status, ['Booked', 'Reserved'], true)) {
                $room->update(['status' => 'Available']);
            }

            return $locked->fresh(['user', 'booking.room.property', 'transaction', 'processor']);
        });

        // Where the queue runs the job straight away (sync), a refusal by SSLCommerz comes back as an exception. The approval
        // itself is saved and the job has already marked the refund failed, so the admin gets an answer about that, not a 500.
        try {
            ProcessRefund::dispatch($approved->id);
        } catch (\Throwable $e) {
            Log::warning('Refund job ended with an error', ['refund_id' => $approved->id, 'message' => SecretScrubber::describe($e)]);
        }

        $fresh = $approved->fresh(['user', 'booking.room.property', 'transaction', 'processor']);

        return $this->sendSuccess($fresh, match ($fresh->status) {
            RefundRequest::STATUS_FAILED => $fresh->gateway_maybe_paid
                ? 'Approved, but SSLCommerz may already have paid this refund (no clear answer was received): ' . $fresh->failure_reason . ' It is marked Failed. The guest\'s money and the host\'s payout stay on hold until it is settled.'
                : 'Approved, but the refund did not go through: ' . $fresh->failure_reason . ' It is marked Failed. Fix the cause and press "Try again". The guest\'s money and the host\'s payout stay on hold until it is settled.',
            RefundRequest::STATUS_COMPLETED => 'Refund approved and paid back to the guest.',
            default => 'Refund approved and queued for payment processing.',
        });
    }

    /**
     * A refund that failed with "no answer" may in fact have been paid by SSLCommerz. The admin looks in the SSLCommerz panel;
     * if the refund is there, this records it (with SSLCommerz's own reference) instead of paying the guest a second time.
     * The reconciliation later asks SSLCommerz about that reference, so a wrong entry shows up as a mismatch.
     */
    public function markRefunded(Request $request, RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);
        $this->adminOnly();

        $validated = $request->validate([
            'gateway_reference' => ['required', 'string', 'min:3', 'max:100'],
            'admin_note' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $reference = trim($validated['gateway_reference']);

        $done = DB::transaction(function () use ($request, $refund, $validated, $reference) {
            $locked = RefundRequest::query()->with(['booking', 'transaction'])->lockForUpdate()->findOrFail($refund->id);

            if ($locked->status !== RefundRequest::STATUS_FAILED) {
                throw ValidationException::withMessages(['refund' => "Only a failed refund can be marked as refunded by hand (this one is {$locked->status})."]);
            }
            if ($locked->booking?->payment_status !== 'paid' || $locked->transaction?->status !== 'completed') {
                throw ValidationException::withMessages(['refund' => 'The payment of this booking is not waiting for a refund any more.']);
            }
            if (str_starts_with(strtoupper($reference), 'LOCAL') || RefundRequest::where('gateway_refund_id', $reference)->where('id', '!=', $locked->id)->exists()) {
                throw ValidationException::withMessages(['gateway_reference' => 'Enter the refund reference that SSLCommerz shows for this refund (it must not belong to another refund).']);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_COMPLETED,
                'gateway_refund_id' => $reference,
                'gateway_status' => 'completed',
                'gateway_response' => ['marked_by_hand' => true, 'by_user_id' => $request->user()->id],
                'completed_at' => now(),
                'failure_reason' => null,
                'processed_by' => $request->user()->id,
                'admin_note' => trim(($locked->admin_note ? $locked->admin_note . ' | ' : '') . 'Marked refunded by hand (SSLCommerz reference ' . $reference . '): ' . $validated['admin_note']),
            ]);
            $locked->booking->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            $locked->transaction->update(['status' => 'refunded']);

            return $locked->fresh(['user', 'booking.room.property', 'transaction', 'processor']);
        });

        RefundNotificationMailer::sendCompleted($done);

        return $this->sendSuccess($done, 'Marked as refunded. It is in the money books now.');
    }

    /**
     * "Ask SSLCommerz now": how is this refund doing on their side? A refund they have finished is
     * marked completed (and the guest is emailed); one they are still working on stays as it is.
     */
    public function checkStatus(RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);
        $this->adminOnly();

        $waiting = $refund->status === RefundRequest::STATUS_PROCESSING
            && filled($refund->gateway_refund_id)
            && !str_starts_with($refund->gateway_refund_id, 'LOCAL-REFUND-');

        if (!$waiting) {
            return $this->sendError('This refund is not waiting on SSLCommerz.', [], 422);
        }

        (new CheckRefundStatuses())->checkOne(app(SslCommerzRefundService::class), $refund);

        $fresh = $refund->fresh(['user', 'booking.room.property', 'transaction', 'processor']);

        return $this->sendSuccess($fresh, match ($fresh->status) {
            RefundRequest::STATUS_COMPLETED => 'SSLCommerz has finished this refund.',
            RefundRequest::STATUS_FAILED    => 'SSLCommerz could not complete this refund: ' . $fresh->failure_reason,
            default                         => 'SSLCommerz is still processing this refund.',
        });
    }

    public function reject(Request $request, RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);
        $this->adminOnly();

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $rejected = DB::transaction(function () use ($request, $refund, $validated) {
            $locked = RefundRequest::query()->lockForUpdate()->findOrFail($refund->id);

            if (!$locked->canTransitionTo(RefundRequest::STATUS_REJECTED)) {
                throw ValidationException::withMessages([
                    'refund' => "A {$locked->status} refund cannot be rejected.",
                ]);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_REJECTED,
                'processed_by' => $request->user()->id,
                'admin_note' => $validated['admin_note'],
                'approved_amount' => null,
            ]);

            return $locked->fresh(['user', 'booking.room.property', 'transaction', 'processor']);
        });

        return $this->sendSuccess($rejected, 'Refund request rejected.');
    }

    /**
     * Every guest payment goes to the platform's own SSLCommerz account, so a refund is paid out of the platform's money.
     * Only the platform admin may approve or reject one (or ask SSLCommerz about it). A vendor sets the refund POLICY of their
     * property (see PropertyRefundPolicyController) and can SEE the requests of their own bookings, but never moves the money.
     */
    private function adminOnly(): void
    {
        abort_unless(auth()->user()->is_admin, 403, 'Only the platform admin can approve or reject a refund: all payments are held by the platform, so the platform pays the refund.');
    }

    // ══════════════════════════════════════════════════════
    //  ACCESS SCOPE
    //  Admin: every refund. Vendor: refunds of bookings in properties they own (to look at, not to act on).
    //  Anyone else (e.g. a guest account): forbidden. Refunds move money, so no one
    //  but the people who run the property may even see them.
    // ══════════════════════════════════════════════════════

    /** null = unrestricted (admin) */
    private function accessiblePropertyIds(): ?Collection
    {
        $user = auth()->user();

        if ($user->is_admin) {
            return null;
        }

        if ($user->is_merchant) {
            return $user->properties()->pluck('id');
        }

        abort(403, 'Unauthorized Action');
    }

    private function scoped(): Builder
    {
        $propertyIds = $this->accessiblePropertyIds();

        return RefundRequest::query()->when(
            $propertyIds !== null,
            fn (Builder $query) => $query->whereHas(
                'booking.room',
                fn ($room) => $room->whereIn('property_id', $propertyIds)
            )
        );
    }

    /** A refund outside the user's scope looks like it does not exist */
    private function authorizeRefund(RefundRequest $refund): void
    {
        if (!$this->scoped()->whereKey($refund->id)->exists()) {
            abort(404);
        }
    }
}
