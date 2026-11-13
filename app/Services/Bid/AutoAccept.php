<?php

namespace App\Services\Bid;

use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;

/**
 * Instant accept: the owner sets, per room, the lowest price per night (before VAT and service charge) they would take
 * (`rooms.auto_accept_min_price`). An offer that reaches it is accepted on the spot, as if the owner had said yes: the room is
 * held for the guest, who has the usual hours to pay. An offer below it goes to the owner as always.
 *
 * The number is the owner's secret: the guest is never shown it (see Room::$hidden), and the offer only records yes or no.
 */
class AutoAccept
{
    /** Instantly accepted offers one guest can hold unpaid at once, over all rooms and hotels, so the rooms cannot be blocked without paying */
    public const MAX_UNPAID = 3;

    /** Does this offer reach the owner's lowest price? (an offer is always below the room price, so a price at or above it is never reached) */
    public function reaches(Room $room, float $perNight): bool
    {
        $min = $room->auto_accept_min_price;

        if ($min === null || (float) $min <= 0 || (float) $min >= (float) $room->base_price) {
            return false;
        }

        return (int) round($perNight * 100) >= (int) round((float) $min * 100);
    }

    /** Offers this guest holds right now that were accepted instantly and are not paid yet */
    public function heldBy(int $userId): int
    {
        return RoomRequest::where('user_id', $userId)
            ->where('status', 'Approved')
            ->where('auto_accepted', true)
            ->where('request_expiration_time', '>', now())
            ->count();
    }

    /**
     * Accept a Pending offer on the spot when the owner's price is reached, the room is free for those dates and the guest is
     * under the limit. Call it inside the transaction, with the room row already locked (two offers at the same moment: the first
     * gets the room, the second is not accepted and goes to the owner).
     */
    public function tryAccept(RoomRequest $bid, Room $room): bool
    {
        if ($bid->status !== 'Pending' || !$this->reaches($room, (float) $bid->discount_price)) {
            return false;
        }

        if ($this->heldBy((int) $bid->user_id) >= self::MAX_UNPAID) {
            return false;
        }

        if (!$bid->stayIsServable()) {
            return false;
        }

        $expiresAt = now()->addHours((int) config('bidding.payment_hours'));

        $bid->update([
            'status'                  => 'Approved',
            'auto_accepted'           => true,
            'request_expiration_time' => $expiresAt,
        ]);

        RoomRequestAccepted::updateOrCreate(
            ['room_requests_id' => $bid->id],
            ['property_id' => $bid->property_id, 'request_expiration_time' => $expiresAt]
        );

        return true;
    }
}
