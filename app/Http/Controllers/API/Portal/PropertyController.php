<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    use MediaMan;

    // ══════════════════════════════════════════════════════
    //  INDEX
    //  GET /portal/properties
    // ══════════════════════════════════════════════════════

    public function index(): JsonResponse
    {
        $properties = Property::query()
            ->whereHas('rooms')
            ->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'logoImage', 'facilities', 'place.city'])
            ->get();

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  PLACE WISE PROPERTIES
    //  GET /portal/properties/{place}/place
    // ══════════════════════════════════════════════════════

    public function placeWiseProperties(Place $place): JsonResponse
    {
        $properties = Property::query()
            ->with(['images', 'facilities', 'place.city'])
            ->where([
                'place_id' => $place->id,
                'status'   => Property::STATUS_PUBLISHED,
            ])
            ->get();

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  PROPERTY DETAILS
    //  GET /portal/properties/{property}/details
    // ══════════════════════════════════════════════════════

    public function details(Property $property): JsonResponse
    {
        $property->load([
            'images',
            'logoImage',
            'facilities',
            'rooms.images',
            'rooms.facilities',
            'rooms.bedType',
            'rooms.roomType',
            'place.city.state.country',
            'rules.propertyRule',
        ]);

        return $this->sendSuccess(['property' => $property]);
    }

    // ══════════════════════════════════════════════════════
    //  AVAILABLE ROOMS
    //  GET /portal/properties/{property}/available-rooms
    //      ?check_in=2026-06-01&check_out=2026-06-05&adult=2
    // :TODO but now it is giving result based on only status of room not based on booking date from and to, need to fix it.now its giving the same result
    //  Returns two groups:
    //    available_rooms → no conflict → Direct booking (Reserve)
    //    other_rooms     → has conflict → Room request + timer
    // ══════════════════════════════════════════════════════

    public function availableRooms(Request $request, $propertyId): JsonResponse
    {
        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $adult    = (int) $request->input('adult', 1);

        // ── 1. Get all room IDs for this property ──────────
        $allRooms = Room::where('property_id', $propertyId)
            ->with(['images', 'facilities', 'bedType', 'roomType', 'activePrices'])
            ->get();

        if ($allRooms->isEmpty()) {
            return $this->sendSuccess([
                'available_rooms' => [],
                'other_rooms'     => [],
            ]);
        }

        $allRoomIds = $allRooms->pluck('id')->toArray();

        // ── 2. Find rooms with overlapping confirmed bookings ──
        $bookedRoomIds = [];

        if ($checkIn && $checkOut) {
            $bookedRoomIds = Booking::whereIn('room_id', $allRoomIds)
                ->whereIn('status', ['reserved', 'approved'])
                ->where('checkin',  '<', $checkOut)
                ->where('checkout', '>', $checkIn)
                ->pluck('room_id')
                ->toArray();
        }

        // ── 3. Split + filter by capacity + resolve price ──
        $availableRooms = collect();
        $otherRooms     = collect();

        foreach ($allRooms as $room) {
            // Attach resolved price to each room
            $room->resolved_price     = $this->resolvePrice($room);
            $room->nights             = $this->nights($checkIn, $checkOut);
            $room->total_price        = $room->resolved_price * $room->nights;

            $hasConflict  = in_array($room->id, $bookedRoomIds);
            $isUnavailable = in_array($room->status, ['Booked', 'Reserved']);
            $hasCapacity  = $room->guest_capacity >= $adult;

            if (!$hasConflict && !$isUnavailable && $hasCapacity) {
                // Room is free for these dates — direct booking
                $room->booking_type = 'direct';
                $availableRooms->push($room);
            } else {
                // Room is occupied — guest can make a request
                $room->booking_type   = 'request';
                $room->conflict_reason = $this->conflictReason($hasConflict, $isUnavailable, $hasCapacity, $adult);
                $otherRooms->push($room);
            }
        }

        return $this->sendSuccess([
            'available_rooms' => $availableRooms->values(),
            'other_rooms'     => $otherRooms->values(),
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'nights'          => $this->nights($checkIn, $checkOut),
            'adult'           => $adult,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  OTHER ROOMS (standalone endpoint — kept for backward compat)
    //  GET /portal/properties/{property}/other-rooms
    // ══════════════════════════════════════════════════════

    public function otherRooms(Request $request, $propertyId): JsonResponse
    {
        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $allRoomIds = Room::where('property_id', $propertyId)->pluck('id')->toArray();

        $bookedRoomIds = [];

        if ($checkIn && $checkOut) {
            $bookedRoomIds = Booking::whereIn('room_id', $allRoomIds)
                ->whereIn('status', ['reserved', 'approved'])
                ->where('checkin',  '<', $checkOut)
                ->where('checkout', '>', $checkIn)
                ->pluck('room_id')
                ->toArray();
        }

        $bookedRooms = Room::where('property_id', $propertyId)
            ->where(function ($q) use ($bookedRoomIds) {
                $q->whereIn('id', $bookedRoomIds)
                    ->orWhereIn('status', ['Booked', 'Reserved']);
            })
            ->with(['images', 'facilities', 'bedType', 'roomType', 'activePrices'])
            ->get()
            ->map(function ($room) use ($checkIn, $checkOut) {
                $room->resolved_price = $this->resolvePrice($room);
                $room->nights         = $this->nights($checkIn, $checkOut);
                $room->total_price    = $room->resolved_price * $room->nights;
                $room->booking_type   = 'request';
                return $room;
            });

        return $this->sendSuccess(['rooms' => $bookedRooms]);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKING ROOM CHECK
    //  GET /portal/properties/{property}/booking-request-check
    //  Returns true if user already has a pending request
    // ══════════════════════════════════════════════════════

    public function bookingRoomCheck($property): JsonResponse
    {
        $data = RoomRequest::where('property_id', $property)->exists();

        return $this->sendSuccess($data);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKED DATE CHECK
    //  GET /portal/properties/{property}/booking-date-check
    //  Returns all blocked date ranges for the calendar
    // ══════════════════════════════════════════════════════

    public function checkBookedDate($propertyId): JsonResponse
    {
        try {
            // Return actual booking date ranges instead of room columns
            // This gives the calendar accurate blocked dates
            $roomIds = Room::where('property_id', $propertyId)->pluck('id');

            $bookedRanges = Booking::whereIn('room_id', $roomIds)
                ->whereIn('status', ['reserved', 'approved'])
                ->where('checkout', '>=', now()->toDateString())
                ->select('room_id', 'checkin', 'checkout', 'status')
                ->get()
                ->map(fn($b) => [
                    'room_id'  => $b->room_id,
                    'check_in' => $b->checkin,
                    'check_out'=> $b->checkout,
                    'status'   => $b->status,
                ]);

            return $this->sendSuccess(['booked_dates' => $bookedRanges]);

        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Resolve the effective price for a room.
     * Checks room_prices WHERE is_activated = 1 first,
     * falls back to rooms.base_price.
     */
    private function resolvePrice(Room $room): float|int
    {
        // activePrices is loaded via Room::activePrices() relationship
        $activePrice = $room->activePrices->first();

        return $activePrice?->price ?? $room->base_price;
    }

    /**
     * Calculate number of nights between two dates.
     * Returns 1 as minimum if dates not provided.
     */
    private function nights(?string $checkIn, ?string $checkOut): int
    {
        if (!$checkIn || !$checkOut) return 1;

        $nights = (int) now()->parse($checkIn)->diffInDays($checkOut);

        return max(1, $nights);
    }

    /**
     * Human-readable reason why a room is in other_rooms.
     */
    private function conflictReason(
        bool $hasConflict,
        bool $isUnavailable,
        bool $hasCapacity,
        int  $adult
    ): string {
        if (!$hasCapacity) {
            return "Room capacity too low for {$adult} adults.";
        }
        if ($hasConflict) {
            return 'Already booked for selected dates.';
        }
        if ($isUnavailable) {
            return 'Room is currently unavailable.';
        }
        return 'Not available.';
    }
}
