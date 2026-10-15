<?php

namespace App\Services\Room;

use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Rooms that are the same in every way a guest can see (type, bed, size, capacity, facilities, price ...) are
 * shown as ONE card: "Deluxe — 3 left", the way Booking.com does. The rooms stay separate in the database, and a
 * booking is still for one particular room; the card only stands for the whole set.
 *
 * "Deluxe 1", "Deluxe 2" ... "Deluxe (copy)" all share the base name "Deluxe".
 */
class RoomGrouper
{
    /**
     * @param  Collection<int, Room>  $rooms     the rooms of ONE property, facilities loaded
     * @param  callable               $priceOf   Room => the price per night that applies to it
     * @return Collection<string, Collection<int, Room>>  rooms keyed by their group key
     */
    public function group(Collection $rooms, callable $priceOf): Collection
    {
        return $rooms
            ->sortBy('id')
            ->groupBy(fn (Room $room) => $this->key($room, $priceOf($room)));
    }

    /** "Deluxe 3" and "Deluxe (copy 2)" both become "Deluxe"; a name that is only a number stays as it is */
    public function baseName(string $name): string
    {
        $base = preg_replace('/\s*\(copy(?: \d+)?\)$/i', '', trim($name));
        $base = preg_replace('/[\s#\-]*\d+$/', '', $base);

        return trim($base) !== '' ? trim($base) : trim($name);
    }

    public function key(Room $room, float|int $price): string
    {
        $facilities = $room->relationLoaded('facilities')
            ? $room->facilities->pluck('id')->sort()->values()->all()
            : $room->facilities()->pluck('facility_subs.id')->sort()->values()->all();

        return md5(json_encode([
            mb_strtolower($this->baseName($room->name)),
            $room->room_type_id,
            $room->bed_type_id,
            $room->room_size,
            $room->guest_capacity,
            $room->extra_bed,
            $room->total_balcony,
            $room->total_window,
            (float) $price,
            $room->vat_percent !== null ? (float) $room->vat_percent : null,
            $room->service_charge_percent !== null ? (float) $room->service_charge_percent : null,
            $facilities,
        ]));
    }
}
