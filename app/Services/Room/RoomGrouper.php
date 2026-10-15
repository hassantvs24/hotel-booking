<?php

namespace App\Services\Room;

use App\Models\Booking;
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

    /** The price per night that applies to a room: its active price if it has one, else its base price */
    public function priceOf(Room $room): float
    {
        $room->loadMissing('activePrices');

        return (float) ($room->activePrices->isNotEmpty()
            ? ($room->activePrices->first()->price ?? $room->base_price)
            : ($room->base_price ?? 0));
    }

    /**
     * Another room just like $room (same card) that is free for these dates, or null.
     *
     * Used when the room a guest picked was taken in the meantime: if "Deluxe 1" is gone but "Deluxe 2" is free,
     * the guest gets "Deluxe 2" instead of an error, which is the whole point of showing one "Deluxe" card.
     *
     * @param  int[]  $excludeIds  rooms that must not be chosen (already in the guest's cart or booking)
     */
    public function freeSibling(Room $room, string $checkIn, string $checkOut, array $excludeIds = []): ?Room
    {
        $room->loadMissing('facilities');
        $key = $this->key($room, $this->priceOf($room));

        $same = Room::where('property_id', $room->property_id)
            ->where('id', '!=', $room->id)
            ->whereNotIn('id', $excludeIds)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['Maintenance', 'Disabled', 'Inactive']))
            ->with(['facilities', 'activePrices'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Room $r) => $this->key($r, $this->priceOf($r)) === $key);

        if ($same->isEmpty()) {
            return null;
        }

        $busy = Booking::whereIn('room_id', $same->pluck('id'))
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin', '<', $checkOut)
            ->where('checkout', '>', $checkIn)
            ->pluck('room_id')
            ->all();

        return $same->first(fn (Room $r) => !in_array($r->id, $busy));
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
