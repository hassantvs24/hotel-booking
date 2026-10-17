<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Jobs\CheckRefundStatuses;
use App\Jobs\ProcessRefund;
use App\Models\RefundRequest;
use App\Services\Refund\RefundQuoteService;
use App\Services\Refund\RefundStatusRefresher;
use App\Services\Refund\SslCommerzRefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        return $this->sendSuccess(
            $query->latest('requested_at')->paginate($validated['per_page'] ?? 15)
        );
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

        return $this->sendSuccess($refund->load([
            'user:id,name,email',
            'booking.room.property',
            'transaction',
            'processor:id,name',
        ]));
    }

    public function approve(Request $request, RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
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

            // Recalculate at approval time; never trust the amount stored by the UI.
            $quote = $this->refundQuoteService->quote($locked->booking);
            if (!$quote['eligible']) {
                throw ValidationException::withMessages([
                    'refund' => $quote['reason'],
                ]);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'approved_amount' => $quote['refundable_amount'],
                'cancellation_fee' => $quote['cancellation_fee'],
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

        ProcessRefund::dispatch($approved->id);

        return $this->sendSuccess(
            $approved->fresh(['user', 'booking.room.property', 'transaction', 'processor']),
            'Refund approved and queued for payment processing.'
        );
    }

    /**
     * "Ask SSLCommerz now": how is this refund doing on their side? A refund they have finished is
     * marked completed (and the guest is emailed); one they are still working on stays as it is.
     */
    public function checkStatus(RefundRequest $refund): JsonResponse
    {
        $this->authorizeRefund($refund);

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

    // ══════════════════════════════════════════════════════
    //  ACCESS SCOPE
    //  Admin: every refund. Vendor: refunds of bookings in properties they own.
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
