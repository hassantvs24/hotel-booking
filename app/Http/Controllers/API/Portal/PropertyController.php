<?php

namespace App\Http\Controllers\API\Portal;

use App\Services\Pricing\PriceService;
use App\Services\Refund\RefundPolicyService;
use App\Services\Room\RoomGrouper;
use App\Http\Controllers\BaseController;
use Carbon\Carbon;
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
            ->get()
            ->each->makeHidden(array_merge(Property::PRIVATE_FOR_GUESTS, ['phone_number']));

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  PLACE WISE PROPERTIES
    //  GET /portal/properties/{place}/place
    // ══════════════════════════════════════════════════════

    public function placeWiseProperties(Place $place): JsonResponse
    {
        // same rule as the main list and search: a property shows once it has a room a guest could book
        $properties = Property::query()
            ->whereHas('rooms')
            ->with(['images', 'facilities', 'place.city'])
            ->where([
                'place_id' => $place->id,
                'status'   => Property::STATUS_PUBLISHED,
            ])
            ->get()
            ->each->makeHidden(array_merge(Property::PRIVATE_FOR_GUESTS, ['phone_number']));

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  PROPERTY DETAILS
    //  GET /portal/properties/{property}/details
    // ══════════════════════════════════════════════════════

    /**
     * A property that is approved but not published yet is not for guests: it is missing from every list and search,
     * and it must not open by its address or id either. Only its owner and the admins may (to get it ready).
     */
    private function ensureVisible(Request $request, $property): void
    {
        $property = $property instanceof Property ? $property : Property::find($property);

        if (!$property || $property->status === Property::STATUS_PUBLISHED) {
            return;
        }

        $viewer = $request->user('sanctum');

        if ($viewer && ($viewer->is_admin || (int) $viewer->id === (int) $property->user_id)) {
            return;
        }

        abort(404, 'This property is not available.');
    }

    public function details(Request $request, Property $property): JsonResponse
    {
        $this->ensureVisible($request, $property);

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
            'faqs.answer',
        ]);

        // the owner's account and the admin's notes are not for the guest's browser (the phone number is, the page shows it)
        $property->makeHidden(array_diff(Property::PRIVATE_FOR_GUESTS, ['verified_at', 'deleted_at']));

        return $this->sendSuccess([
            'property'      => $property,
            'refund_policy' => app(RefundPolicyService::class)->summary($property),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  AVAILABLE ROOMS
    //  GET /portal/properties/{property}/available-rooms
    //      ?check_in=2026-06-01&check_out=2026-06-05&adult=2
    //
    //  Returns two groups:
    //    available_rooms → no conflict → Direct booking (Reserve)
    //    other_rooms     → has conflict → Room request + timer
    // ══════════════════════════════════════════════════════

    public function availableRooms(Request $request, $propertyId): JsonResponse
    {
        $this->ensureVisible($request, $propertyId);

        // What the cart would refuse is not shown as available: no stay of zero nights, none in the past, no nonsense
        $request->validate([
            'check_in'  => 'nullable|required_with:check_out|date|after_or_equal:today',
            'check_out' => 'nullable|required_with:check_in|date|after:check_in',
            'adult'     => 'nullable|integer|min:1|max:50',
            'child'     => 'nullable|integer|min:0|max:50',
        ], \App\Services\PropertySearchService::messages());

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $adult    = (int) $request->input('adult', 1);
        $guests   = $adult + (int) $request->input('child', 0);   // everybody who sleeps in the room counts for its size

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

        // ── 3. Group identical rooms, then filter by availability and capacity ──
        // Identical rooms ("Deluxe 1" ... "Deluxe 5") are one card: "Deluxe — 3 left". The card stands for the
        // first free room of the set; the rest are listed in room_ids so the guest can add more than one.

        $nights  = $this->nights($checkIn, $checkOut);
        $grouper = app(RoomGrouper::class);

        $available      = collect();
        $hiddenCapacity = [];

        foreach ($grouper->group($allRooms, fn ($room) => $this->resolvePrice($room)) as $key => $members) {
            $free = $members->filter(function ($room) use ($bookedRoomIds) {
                return !in_array($room->id, $bookedRoomIds)
                    && !in_array($room->status, ['Maintenance', 'Disabled', 'Inactive']);
            })->values();

            if ($free->isEmpty()) {
                continue;
            }

            $room = $free->first();
            $name = $members->count() > 1 ? $grouper->baseName($room->name) : $room->name;

            // Too small for the party: not offered, but the guest is told, so they know why it is missing
            if ($room->guest_capacity < $guests) {
                $hiddenCapacity[] = ['name' => $name, 'guest_capacity' => (int) $room->guest_capacity, 'count' => $free->count()];
                continue;
            }

            $room->name           = $name;
            $room->resolved_price = $this->resolvePrice($room);
            $room->nights         = $nights;
            $room->total_price    = $room->resolved_price * $nights;
            $room->booking_type   = 'direct';
            $room->tax            = app(PriceService::class)->forStay($room, (float) $room->resolved_price, $nights);
            $room->group_key      = $key;
            $room->group_total    = $members->count();
            $room->available_count = $free->count();
            $room->room_ids       = $free->pluck('id')->values();

            $available->push($room);
        }

        return $this->sendSuccess([
            'available_rooms'     => $available->values(),
            'hidden_for_capacity' => $hiddenCapacity,
            'check_in'            => $checkIn,
            'check_out'           => $checkOut,
            'nights'              => $nights,
            'adult'               => $adult,
            // what the cancellation policy means for THESE dates, today (the hotel page's "Cancellation" line)
            'refund_for_stay'     => $checkIn
                ? app(\App\Services\Refund\RefundPolicyService::class)->forStay(\App\Models\Property::find($propertyId), $checkIn)
                : null,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  OTHER ROOMS (standalone endpoint — kept for backward compat)
    //  GET /portal/properties/{property}/other-rooms
    // ══════════════════════════════════════════════════════

    public function otherRooms(Request $request, $propertyId): JsonResponse
    {
        $this->ensureVisible($request, $propertyId);

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

        // Only use booking overlap as source of truth — NOT rooms.status
        // rooms.status = 'Booked' is stale after checkout date passes
        $bookedRooms = Room::where('property_id', $propertyId)
            ->whereIn('id', $bookedRoomIds)
            ->with(['images', 'facilities', 'bedType', 'roomType', 'activePrices'])
            ->get()
            ->map(function ($room) use ($checkIn, $checkOut) {
                $room->resolved_price = $this->resolvePrice($room);
                $room->nights         = $this->nights($checkIn, $checkOut);
                $room->total_price    = $room->resolved_price * $room->nights;
                $room->booking_type   = 'request';
                $room->tax            = app(PriceService::class)->forStay($room, (float) $room->resolved_price, $room->nights);
                return $room;
            });

        return $this->sendSuccess(['rooms' => $bookedRooms]);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKING ROOM CHECK
    //  GET /portal/properties/{property}/booking-request-check
    //  Returns true if user already has a pending request
    // ══════════════════════════════════════════════════════

    public function bookingRoomCheck(Request $request, $property): JsonResponse
    {
        $this->ensureVisible($request, $property);

        $data = RoomRequest::where('property_id', $property)->exists();

        return $this->sendSuccess($data);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKED DATE CHECK
    //  GET /portal/properties/{property}/booking-date-check
    //  Returns all blocked date ranges for the calendar
    // ══════════════════════════════════════════════════════

    public function checkBookedDate(Request $request, $propertyId): JsonResponse
    {
        $this->ensureVisible($request, $propertyId);

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
        // Use active price if loaded and present, fall back to base_price
        // activePrices relationship: room_prices where is_activated = 1
        if ($room->relationLoaded('activePrices') && $room->activePrices->isNotEmpty()) {
            return $room->activePrices->first()->price ?? $room->base_price;
        }

        return $room->base_price ?? 0;
    }

    /**
     * Calculate number of nights between two dates.
     * Returns 1 as minimum if dates not provided.
     */
    private function nights(?string $checkIn, ?string $checkOut): int
    {
        if (!$checkIn || !$checkOut) return 1;

        try {
            return max(1, (int) Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)));
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Human-readable reason why a room is in other_rooms.
     */
    private function conflictReason(
        bool $hasConflict,
        bool $isPermanentlyUnavailable,
        bool $hasCapacity,
        int  $adult
    ): string {
        if (!$hasCapacity) {
            return "Room capacity is too low for {$adult} adult(s). Try a different room.";
        }
        if ($hasConflict) {
            return 'Already booked for your selected dates. You can make a price request.';
        }
        if ($isPermanentlyUnavailable) {
            return 'This room is currently offline for maintenance.';
        }
        return 'Not available for the selected dates.';
    }
}
