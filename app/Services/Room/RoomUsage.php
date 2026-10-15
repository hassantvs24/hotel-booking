<?php

namespace App\Services\Room;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;

/**
 * What a room is still tied up in. A room with upcoming bookings must not be deleted: the guests have paid for
 * it. Open guest offers do not block a delete, but the owner is told about them.
 */
class RoomUsage
{
    /** Bookings that still matter: paid or confirmed, and the stay has not finished yet */
    private const LIVE_BOOKING_STATUSES = ['reserved', 'approved'];

    /**
     * @param  int[]  $roomIds
     * @return array<int, array{bookings: int, next_checkin: ?string, offers: int}>  keyed by room id
     */
    public function forRooms(array $roomIds): array
    {
        $usage = array_fill_keys($roomIds, ['bookings' => 0, 'next_checkin' => null, 'offers' => 0]);

        if (!$roomIds) {
            return $usage;
        }

        $bookings = Booking::whereIn('room_id', $roomIds)
            ->whereIn('status', self::LIVE_BOOKING_STATUSES)
            ->whereDate('checkout', '>=', now()->toDateString())
            ->selectRaw('room_id, COUNT(*) as total, MIN(checkin) as next_checkin')
            ->groupBy('room_id')
            ->get();

        foreach ($bookings as $row) {
            $usage[$row->room_id]['bookings']     = (int) $row->total;
            $usage[$row->room_id]['next_checkin'] = $row->next_checkin ? substr((string) $row->next_checkin, 0, 10) : null;
        }

        $offers = RoomRequest::whereIn('room_id', $roomIds)
            ->whereIn('status', RoomRequest::LIVE_STATUSES)
            ->selectRaw('room_id, COUNT(*) as total')
            ->groupBy('room_id')
            ->get();

        foreach ($offers as $row) {
            $usage[$row->room_id]['offers'] = (int) $row->total;
        }

        return $usage;
    }

    public function forRoom(Room $room): array
    {
        return $this->forRooms([$room->id])[$room->id];
    }

    /** The reason a room cannot be deleted, or null when it can */
    public function blockReason(Room $room, ?array $usage = null): ?string
    {
        $usage ??= $this->forRoom($room);

        if ($usage['bookings'] < 1) {
            return null;
        }

        $when = $usage['next_checkin'] ? ' (the next check-in is on ' . $usage['next_checkin'] . ')' : '';

        return sprintf(
            '"%s" has %d upcoming %s%s. Wait until the stays are over, or cancel the booking%s first.',
            $room->name,
            $usage['bookings'],
            $usage['bookings'] === 1 ? 'booking' : 'bookings',
            $when,
            $usage['bookings'] === 1 ? '' : 's'
        );
    }
}
