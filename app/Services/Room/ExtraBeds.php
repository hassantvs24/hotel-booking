<?php

namespace App\Services\Room;

use App\Models\Room;

/**
 * Extra beds a guest can book with a room.
 *
 * The owner sets, per room, how many extra beds it can take (`extra_bed`) and what ONE bed costs for ONE night
 * (`extra_bed_price`). A room with beds but no price (NULL) cannot sell them online: the price must be known before
 * the guest pays. 0 is a price: a free bed, on purpose.
 *
 * Every bed lets one more guest stay in the room, so a room that sleeps 2 with 1 bookable bed takes 3.
 */
class ExtraBeds
{
    /** Can this room sell extra beds online? */
    public function offered(?Room $room): bool
    {
        return $room !== null && (int) $room->extra_bed > 0 && $room->extra_bed_price !== null;
    }

    /** How many beds can be booked with this room (0 when it sells none) */
    public function max(?Room $room): int
    {
        return $this->offered($room) ? (int) $room->extra_bed : 0;
    }

    /** The most guests the room takes, counting the beds it can sell */
    public function maxGuests(?Room $room): int
    {
        return (int) ($room?->guest_capacity ?? 0) + $this->max($room);
    }

    /** The beds a party needs: the guests that do not fit the room's own beds */
    public function needed(?Room $room, int $guests): int
    {
        return max(0, $guests - (int) ($room?->guest_capacity ?? 0));
    }

    /**
     * The beds that will be booked for a party, or a plain sentence saying why it cannot be.
     * A party that does not fit needs beds: fewer than needed is refused, more than the room has is refused.
     *
     * @return array{beds: int, error: ?string}
     */
    public function resolve(?Room $room, int $guests, int $requested): array
    {
        $requested = max(0, $requested);
        $needed    = $this->needed($room, $guests);
        $max       = $this->max($room);
        $capacity  = (int) ($room?->guest_capacity ?? 0);

        if ($capacity > 0 && $needed > $max) {
            return ['beds' => 0, 'error' => $max > 0
                ? "This room takes up to {$this->maxGuests($room)} guests with its extra beds. Please choose a bigger room or fewer guests."
                : "This room takes up to {$capacity} guest" . ($capacity > 1 ? 's' : '') . '. Please choose a bigger room or fewer guests.'];
        }

        if ($requested > $max) {
            return ['beds' => 0, 'error' => $max > 0
                ? "This room can take up to {$max} extra bed" . ($max > 1 ? 's' : '') . '.'
                : 'This room has no extra bed that can be booked online.'];
        }

        // fewer beds than the party needs would put guests in the room with nowhere to sleep: say so, never add a bed (and its price) silently
        if ($requested < $needed) {
            return ['beds' => 0, 'error' => "For {$guests} guests this room needs {$needed} extra bed" . ($needed > 1 ? 's' : '') . '. Please add ' . ($needed > 1 ? 'them' : 'it') . '.'];
        }

        return ['beds' => $requested, 'error' => null];
    }
}
