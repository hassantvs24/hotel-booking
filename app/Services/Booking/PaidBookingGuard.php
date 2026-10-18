<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\Transaction;

/**
 * Keeps a booking that still holds the guest's money from being changed in ways that break the money records:
 * deleted, set to "cancelled" or "refunded" by hand (no money goes back), or moved to another room or other dates.
 * Those things go through the refund flow instead.
 *
 * A booking "holds money" when the guest paid and has not been fully refunded. An unpaid booking, or one that was
 * fully refunded, can be changed freely.
 */
class PaidBookingGuard
{
    public function holdsMoney(Booking $booking): bool
    {
        $payment = Transaction::query()
            ->where('booking_id', $booking->booking_number)
            ->whereIn('status', ['completed', 'refunded'])
            ->first();

        if ($payment) {
            $refunded = (float) RefundRequest::query()
                ->where('transaction_id', $payment->id)
                ->where('status', RefundRequest::STATUS_COMPLETED)
                ->sum('approved_amount');

            return round((float) $payment->amount - $refunded, 2) > 0;
        }

        // an older booking marked "paid" with no payment record: treat the money as real
        return $booking->payment_status === 'paid';
    }

    /**
     * Bookings in this query that hold the guest's money and whose stay has not finished (check-out today or later,
     * in Dhaka). These are the ones that stand in the way of removing a guest, an owner or a property.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Booking> $bookings
     * @return \Illuminate\Support\Collection<int,Booking>
     */
    public function upcomingWithMoney($bookings)
    {
        $today = now(config('refund.timezone', 'Asia/Dhaka'))->toDateString();

        return $bookings->whereDate('checkout', '>=', $today)->get()->filter(fn (Booking $b) => $this->holdsMoney($b))->values();
    }

    /** @return string|null why it cannot be deleted, or null when it can */
    public function whyNotDelete(Booking $booking): ?string
    {
        return $this->holdsMoney($booking)
            ? "This booking has been paid for, so it cannot be deleted: the guest's money would be left without a booking. If the guest should get their money back, use Refund requests."
            : null;
    }

    /** @return string|null why the status cannot be set by hand, or null when it can */
    public function whyNotStatus(Booking $booking, string $new): ?string
    {
        if (!in_array($new, ['cancelled', 'refunded', 'pending', 'reserved'], true) || $new === $booking->status) {
            return null;
        }

        if (!$this->holdsMoney($booking)) {
            return null;
        }

        return in_array($new, ['cancelled', 'refunded'], true)
            ? "A paid booking cannot be set to \"{$new}\" by hand, because no money would go back to the guest. Use Refund requests to cancel it and refund the guest."
            : "A paid booking cannot be set back to \"{$new}\". It can stay Approved or be marked Completed.";
    }

    /**
     * @param array<string,mixed> $changes what the admin wants to change (room_id, checkin, checkout, ...)
     * @return string|null why it cannot be changed, or null when it can
     */
    public function whyNotChange(Booking $booking, array $changes): ?string
    {
        $room  = array_key_exists('room_id', $changes) && (int) $changes['room_id'] !== (int) $booking->room_id;
        $dates = (array_key_exists('checkin', $changes) && substr((string) $changes['checkin'], 0, 10) !== substr((string) $booking->checkin, 0, 10))
              || (array_key_exists('checkout', $changes) && substr((string) $changes['checkout'], 0, 10) !== substr((string) $booking->checkout, 0, 10));

        if (!$room && !$dates) {
            return null;
        }

        return $this->holdsMoney($booking)
            ? 'The guest has already paid for this booking, so its room and dates cannot be changed. To move the guest, cancel it through Refund requests and make a new booking.'
            : null;
    }
}
