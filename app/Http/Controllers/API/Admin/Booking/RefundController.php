<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Jobs\ProcessRefund;
use App\Models\RefundRequest;
use App\Services\Refund\RefundQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(RefundRequest::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = RefundRequest::query()
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
        $counts = RefundRequest::query()
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
        return $this->sendSuccess($refund->load([
            'user:id,name,email',
            'booking.room.property',
            'transaction',
            'processor:id,name',
        ]));
    }

    public function approve(Request $request, RefundRequest $refund): JsonResponse
    {
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

            return $locked->fresh(['user', 'booking.room.property', 'transaction', 'processor']);
        });

        ProcessRefund::dispatch($approved->id);

        return $this->sendSuccess(
            $approved->fresh(['user', 'booking.room.property', 'transaction', 'processor']),
            'Refund approved and queued for payment processing.'
        );
    }

    public function reject(Request $request, RefundRequest $refund): JsonResponse
    {
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
}
