<?php

namespace App\Services\Refund;

use App\Models\Booking;
use App\Models\RefundRequest;
use Carbon\CarbonImmutable;

class RefundQuoteService
{
    public function __construct(private RefundPolicyService $policies) {}

    public function quote(Booking $booking) : array
    {
        $booking->loadMissing(['transaction', 'refundRequests', 'room.property']);

        if ($booking->payment_status !== 'paid') {
            return $this->ineligible('Only paid bookings can be refunded.');
        }

        if ($booking->status !== 'approved') {
            return $this->ineligible('This booking is not eligible for cancellation.');
        }

        if (!$booking->transaction || $booking->transaction->status !== 'completed') {
            return $this->ineligible('A completed payment transaction was not found.');
        }

        if (!$booking->transaction->transaction_reference) {
            return $this->ineligible('The bank transaction reference is missing.');
        }

        // Check-in day starts at midnight where the hotel is (Dhaka), not at midnight UTC, which is six hours later
        $checkIn = CarbonImmutable::parse(substr((string) $booking->checkin, 0, 10), config('refund.timezone', 'Asia/Dhaka'))->startOfDay();
        $hoursBeforeCheckIn = (int) floor(now()->diffInHours($checkIn, false));

        if ($hoursBeforeCheckIn <= 0) {
            return $this->ineligible('The check-in time has passed.');
        }

        $property = $booking->room?->property;
        // the rules the guest was promised when booking, not whatever the owner has set since
        $tier = $this->policies->resolveTierForBooking($booking, $property, $hoursBeforeCheckIn);

        if (!$tier) {
            return $this->ineligible(
                $this->policies->tiersForBooking($booking, $property) === []
                    ? 'This property\'s policy does not allow refunds.'
                    : 'This booking is outside the refundable period of the property\'s refund policy.'
            );
        }

        $originalCents = min(
            $this->toCents($booking->amount),
            $this->toCents($booking->transaction->amount),
        );

        $alreadyCommittedCents = $booking->refundRequests
            ->whereIn('status', [
                RefundRequest::STATUS_APPROVED,
                RefundRequest::STATUS_PROCESSING,
                RefundRequest::STATUS_COMPLETED,
            ])
            ->sum(fn (RefundRequest $refund) => $this->toCents(
                $refund->approved_amount ?? 0
            ));

        $remainingCents = max(0, $originalCents - $alreadyCommittedCents);

        if ($remainingCents === 0) {
            return $this->ineligible('No refundable balance remains.');
        }

        $refundCents = intdiv(
            $remainingCents * (int) $tier['refund_percentage'],
            100
        );

        $feeCents = $remainingCents - $refundCents;

        return [
            'eligible' => true,
            'reason' => null,
            'currency' => config('refund.currency', 'BDT'),
            'hours_before_checkin' => $hoursBeforeCheckIn,
            'policy_label' => $tier['label'],
            'refund_percentage' => (int) $tier['refund_percentage'],
            'policy_lines' => $this->policies->lines($this->policies->tiersForBooking($booking, $property)),
            'original_amount' => $this->fromCents($originalCents),
            'remaining_refundable_balance' => $this->fromCents($remainingCents),
            'cancellation_fee' => $this->fromCents($feeCents),
            'refundable_amount' => $this->fromCents($refundCents),
            'transaction_id' => $booking->transaction->id,
        ];
    }

    private function ineligible(string $reason): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'currency' => config('refund.currency', 'BDT'),
            'refundable_amount' => '0.00',
            'cancellation_fee' => '0.00',
        ];
    }

    private function toCents(string|int|float|null $amount): int
    {
        $normalised = number_format((float) ($amount ?? 0), 2, '.', '');

        return (int) str_replace('.', '', $normalised);
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
