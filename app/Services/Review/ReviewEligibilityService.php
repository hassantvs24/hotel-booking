<?php

namespace App\Services\Review;

use App\Models\Booking;
use Carbon\CarbonImmutable;

class ReviewEligibilityService
{
    public function checkEligibility(Booking $booking): array
    {
        $booking->loadMissing('reviewSubmission');

        if ($booking->reviewSubmission) {
            return $this->ineligible('This booking has already been reviewed.');
        }

        if ($booking->payment_status !== 'paid') {
            return $this->ineligible('Only paid bookings can be reviewed.');
        }

        if ($booking->status === 'cancelled') {
            return $this->ineligible('Cancelled bookings cannot be reviewed.');
        }

        $checkout = CarbonImmutable::parse($booking->checkout)->endOfDay();

        if (now()->lt($checkout)) {
            return $this->ineligible('You can review this stay after checkout.');
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    private function ineligible(string $reason): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
        ];
    }
}
