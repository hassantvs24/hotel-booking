<?php

namespace App\Services\Refund;

use App\Models\Booking;
use App\Models\RefundRequest;
use Carbon\CarbonImmutable;

class RefundQuoteService
{
    public function quote(Booking $booking) : array
    {
        $booking->loadMissing(['transaction', 'refundRequests']);

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

        $checkIn = CarbonImmutable::parse($booking->checkin)->startOfDay();
        $hoursBeforeCheckIn = (int) floor(now()->diffInHours($checkIn, false));

        if ($hoursBeforeCheckIn <= 0) {
            return $this->ineligible('The check-in time has passed.');
        }

        $tier = $this->resolveTier($hoursBeforeCheckIn);

        if (!$tier) {
            return $this->ineligible('This booking is outside the refundable period.');
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
            'original_amount' => $this->fromCents($originalCents),
            'remaining_refundable_balance' => $this->fromCents($remainingCents),
            'cancellation_fee' => $this->fromCents($feeCents),
            'refundable_amount' => $this->fromCents($refundCents),
            'transaction_id' => $booking->transaction->id,
        ];
    }

    private function resolveTier(int $hoursBeforeCheckIn): ?array
    {
        $tiers = collect(config('refund.tiers', []))
            ->sortByDesc('minimum_hours_before_checkin');

        return $tiers->first(
            fn (array $tier) =>
                $hoursBeforeCheckIn >= (int) $tier['minimum_hours_before_checkin']
        );
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
