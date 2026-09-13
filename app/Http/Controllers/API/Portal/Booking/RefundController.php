<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Services\Refund\RefundQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RefundController extends BaseController
{
    public function __construct(
        private readonly RefundQuoteService $refundQuoteService
    ) {}

    public function quote(Request $request, Booking $booking): JsonResponse
    {
        $this->ensureOwnership($request, $booking);

        $quote = $this->refundQuoteService->quote($booking);

        if (!$quote['eligible']) {
            return $this->sendError($quote['reason'], $quote, 422);
        }

        return $this->sendSuccess($quote, 'Refund quote calculated.');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_number' => ['required', 'integer', 'exists:bookings,booking_number'],
            'reason_code' => [
                'required',
                'string',
                Rule::in(array_keys(config('refund.reasons', []))),
            ],
            'reason_details' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['reason_code'] === 'other' && empty($validated['reason_details'])) {
            throw ValidationException::withMessages([
                'reason_details' => 'Please explain the reason for this refund request.',
            ]);
        }

        $refund = DB::transaction(/**
         * @throws ValidationException
         */ function () use ($request, $validated) {
            $booking = Booking::query()
                ->where('booking_number', $validated['booking_number'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOwnership($request, $booking);

            $hasOpenRequest = RefundRequest::query()
                ->where('booking_id', $booking->id)
                ->whereIn('status', [
                    RefundRequest::STATUS_REQUESTED,
                    RefundRequest::STATUS_APPROVED,
                    RefundRequest::STATUS_PROCESSING,
                ])
                ->exists();

            if ($hasOpenRequest) {
                throw ValidationException::withMessages([
                    'booking_number' => 'This booking already has an active refund request.',
                ]);
            }

            $quote = $this->refundQuoteService->quote($booking);

            if (!$quote['eligible']) {
                throw ValidationException::withMessages([
                    'booking_number' => $quote['reason'],
                ]);
            }

            return RefundRequest::create([
                'refund_number' => 'RF-' . now()->format('Ymd') . '-' . Str::ulid(),
                'booking_id' => $booking->id,
                'transaction_id' => $quote['transaction_id'],
                'user_id' => $request->user()->id,
                'original_amount' => $quote['original_amount'],
                'requested_amount' => $quote['refundable_amount'],
                'cancellation_fee' => $quote['cancellation_fee'],
                'currency' => $quote['currency'],
                'status' => RefundRequest::STATUS_REQUESTED,
                'reason_code' => $validated['reason_code'],
                'reason_details' => $validated['reason_details'] ?? null,
                'idempotency_key' => (string) Str::uuid(),
                'requested_at' => now(),
            ]);
        });

        return $this->sendSuccess(
            $refund->load(['booking.room.property', 'transaction']),
            'Refund request submitted.',
            201
        );
    }

    public function index(Request $request): JsonResponse
    {
        $refunds = RefundRequest::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'booking:id,booking_number,room_id,checkin,checkout,amount,status,payment_status',
                'booking.room:id,name,property_id',
                'booking.room.property:id,name',
            ])
            ->latest('requested_at')
            ->paginate(min(100, max(1, $request->integer('per_page', 15))));

        return $this->sendSuccess($refunds);
    }

    public function show(Request $request, RefundRequest $refund): JsonResponse
    {
        $this->ensureRefundOwnership($request, $refund);

        return $this->sendSuccess(
            $refund->load([
                'booking.room.property',
                'transaction',
                'processor:id,name',
            ])
        );
    }

    public function cancel(Request $request, RefundRequest $refund): JsonResponse
    {
        DB::transaction(/**
         * @throws ValidationException
         */ function () use ($request, $refund): void {
            $lockedRefund = RefundRequest::query()
                ->lockForUpdate()
                ->findOrFail($refund->id);

            $this->ensureRefundOwnership($request, $lockedRefund);

            if ($lockedRefund->status !== RefundRequest::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'refund' => 'Only a refund awaiting review can be cancelled.',
                ]);
            }

            $lockedRefund->update([
                'status' => RefundRequest::STATUS_CANCELLED,
            ]);
        });

        return $this->sendSuccess([], 'Refund request cancelled.');
    }

    private function ensureOwnership(Request $request, Booking $booking): void
    {
        abort_unless(
            $booking->user_id === $request->user()->id,
            403,
            'You do not own this booking.'
        );
    }

    private function ensureRefundOwnership(
        Request $request,
        RefundRequest $refund
    ): void {
        abort_unless(
            $refund->user_id === $request->user()->id,
            403,
            'You do not own this refund request.'
        );
    }
}
