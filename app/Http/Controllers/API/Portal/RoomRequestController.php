<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use App\Notifications\BID\BidReceivedNotification;
use App\Notifications\BID\BidStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomRequestController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  SUBMIT BID / OFFER
    //  POST /portal/room-request/store
    //
    //  Rules:
    //   - Max 3 active bids per user per room
    //   - Active = Pending | Approved | Counter
    //   - Timeout / Declined do NOT count toward limit
    //   - Room stays Available — NOT reserved until paid
    // ══════════════════════════════════════════════════════

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'        => 'required|exists:rooms,id',
            'property_id'    => 'required|exists:properties,id',
            'check_in'       => 'required|date|after_or_equal:today',
            'check_out'      => 'required|date|after:check_in',
            'adult'          => 'required|integer|min:1',
            'children'       => 'integer|min:0',
            'discount_price' => 'required|numeric|min:1',
            'message'        => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;
        $roomId = $validated['room_id'];

        // ── Check active bid count (max 3) ──────────────
        $activeBidCount = RoomRequest::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->whereIn('status', ['Pending', 'Approved', 'Counter'])
            ->count();

        if ($activeBidCount >= 3) {
            return $this->sendError(
                'You have reached the maximum of 3 bids for this room.',
                ['bid_count' => $activeBidCount],
                422
            );
        }

        // ── Check room exists and is not permanently offline ──
        $room = Room::with('property.user')->findOrFail($roomId);

        if (in_array($room->status, ['Unavailable', 'Maintenance'])) {
            return $this->sendError('This room is not available for requests.', [], 422);
        }

        // ── Create bid ──────────────────────────────────
        $bid = RoomRequest::create([
            'room_id'                => $roomId,
            'property_id'            => $validated['property_id'],
            'user_id'                => $userId,
            'check_in'               => $validated['check_in'],
            'check_out'              => $validated['check_out'],
            'adult'                  => $validated['adult'],
            'children'               => $validated['children'] ?? 0,
            'discount_price'         => $validated['discount_price'],
            'message'                => $validated['message'] ?? null,
            'bid_number'             => $activeBidCount + 1,
            'status'                 => 'Pending',
            'request_expiration_time'=> Carbon::now()->addHours(24),
        ]);

        // ── Notify property owner ──────────────────────
        $owner = $room->property->user ?? null;
        if ($owner) {
            $owner->notify(new BidReceivedNotification($bid, $room, $request->user()));
        }

        return $this->sendSuccess([
            'message'    => 'Your bid has been submitted. The owner will respond within 24 hours.',
            'bid'        => $bid,
            'bid_number' => $bid->bid_number,
            'bids_left'  => 3 - $bid->bid_number,
            'expires_at' => $bid->request_expiration_time,
        ], 201);
    }

    // ══════════════════════════════════════════════════════
    //  BID COUNT
    //  GET /portal/room-request/bid-count/:roomId
    //  Frontend uses this to show "2/3 bids used" in OfferModal
    // ══════════════════════════════════════════════════════

    public function bidCount(Request $request, $roomId): JsonResponse
    {
        $userId = $request->user()->id;

        $active = RoomRequest::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->whereIn('status', ['Pending', 'Approved', 'Counter'])
            ->count();

        $total = RoomRequest::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->count();

        return $this->sendSuccess([
            'active_bids' => $active,
            'total_bids'  => $total,
            'bids_left'   => max(0, 3 - $active),
            'can_bid'     => $active < 3,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  MY BIDS (guest view)
    //  GET /portal/room-request/my-bids
    // ══════════════════════════════════════════════════════

    public function myBids(Request $request): JsonResponse
    {
        $bids = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                'room',
                'room.primaryImage',
                'room.property',
                'room.property.place.city',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($bid) {
                $bid->nights   = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));
                $bid->is_active = in_array($bid->status, ['Pending', 'Approved', 'Counter']);
                $bid->is_expired = $bid->request_expiration_time
                    && Carbon::parse($bid->request_expiration_time)->isPast()
                    && $bid->status === 'Pending';
                return $bid;
            });

        return $this->sendSuccess(['bids' => $bids]);
    }

    // ══════════════════════════════════════════════════════
    //  OWNER — INCOMING BID NOTIFICATIONS
    //  GET /portal/room-request/incoming
    //  Owner sees all pending bids on their properties
    // ══════════════════════════════════════════════════════

    public function incoming(Request $request): JsonResponse
    {
        $ownedPropertyIds = $request->user()
            ->properties()
            ->pluck('id');

        $bids = RoomRequest::whereIn('property_id', $ownedPropertyIds)
            ->whereIn('status', ['Pending', 'Counter'])
            ->with([
                'room',
                'room.primaryImage',
                'user',
                'user.profile',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($bid) {
                $bid->nights          = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));
                $bid->total_offered   = $bid->discount_price * $bid->nights;
                $bid->room_base_total = $bid->room->base_price * $bid->nights;
                $bid->seconds_left    = $bid->request_expiration_time
                    ? max(0, now()->diffInSeconds($bid->request_expiration_time, false))
                    : 0;
                return $bid;
            });

        return $this->sendSuccess(['bids' => $bids]);
    }

    // ══════════════════════════════════════════════════════
    //  OWNER — ACCEPT BID
    //  POST /portal/room-request/:id/accept
    // ══════════════════════════════════════════════════════

    public function accept(Request $request, $id): JsonResponse
    {
        $bid = $this->findOwnerBid($request, $id);
        if (!$bid) return $this->sendError('Bid not found.', [], 404);

        if ($bid->status !== 'Pending') {
            return $this->sendError('Only pending bids can be accepted.', [], 422);
        }

        $bid->update(['status' => 'Approved']);

        // Create RoomRequestAccepted record
        RoomRequestAccepted::updateOrCreate(
            ['room_requests_id' => $bid->id],
            [
                'property_id'            => $bid->property_id,
                'request_expiration_time'=> Carbon::now()->addHours(2), // 2hr payment window
            ]
        );

        // Notify guest
        $bid->user->notify(new BidStatusNotification($bid, 'accepted'));

        return $this->sendSuccess([
            'message' => 'Bid accepted. Guest has 2 hours to complete payment.',
            'bid'     => $bid->fresh(),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  OWNER — COUNTER OFFER
    //  POST /portal/room-request/:id/counter
    // ══════════════════════════════════════════════════════

    public function counter(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'counter_price' => 'required|numeric|min:1',
            'message'       => 'nullable|string|max:500',
        ]);

        $bid = $this->findOwnerBid($request, $id);
        if (!$bid) return $this->sendError('Bid not found.', [], 404);

        if ($bid->status !== 'Pending') {
            return $this->sendError('Only pending bids can receive a counter offer.', [], 422);
        }

        $bid->update([
            'status'        => 'Counter',
            'counter_price' => $validated['counter_price'],
            'message'       => $validated['message'] ?? $bid->message,
        ]);

        // Notify guest
        $bid->user->notify(new BidStatusNotification($bid, 'countered'));

        return $this->sendSuccess([
            'message' => 'Counter offer sent to guest.',
            'bid'     => $bid->fresh(),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  GUEST — ACCEPT COUNTER OFFER
    //  POST /portal/room-request/:id/accept-counter
    //  Guest accepts owner's counter price → goes to payment
    // ══════════════════════════════════════════════════════

    public function acceptCounter(Request $request, $id): JsonResponse
    {
        $bid = RoomRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'Counter')
            ->first();

        if (!$bid) {
            return $this->sendError('Counter offer not found or already actioned.', [], 404);
        }

        $bid->update(['status' => 'Approved']);

        // Create accepted record with 2hr payment window
        RoomRequestAccepted::updateOrCreate(
            ['room_requests_id' => $bid->id],
            [
                'property_id'            => $bid->property_id,
                'request_expiration_time'=> Carbon::now()->addHours(2),
            ]
        );

        // Notify owner
        $owner = $bid->room->property->user ?? null;
        if ($owner) {
            $owner->notify(new BidStatusNotification($bid, 'counter_accepted'));
        }

        return $this->sendSuccess([
            'message'       => 'Counter accepted. Please complete payment within 2 hours.',
            'bid'           => $bid->fresh(),
            'payment_price' => $bid->counter_price,
            'expires_at'    => Carbon::now()->addHours(2),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  OWNER — DECLINE BID
    //  POST /portal/room-request/:id/decline
    // ══════════════════════════════════════════════════════

    public function decline(Request $request, $id): JsonResponse
    {
        $bid = $this->findOwnerBid($request, $id);
        if (!$bid) return $this->sendError('Bid not found.', [], 404);

        if (!in_array($bid->status, ['Pending', 'Counter'])) {
            return $this->sendError('This bid cannot be declined.', [], 422);
        }

        $bid->update(['status' => 'Declined']);

        // Notify guest
        $bid->user->notify(new BidStatusNotification($bid, 'declined'));

        return $this->sendSuccess(['message' => 'Bid declined.']);
    }

    // ══════════════════════════════════════════════════════
    //  PAY ACCEPTED BID
    //  POST /portal/room-request/:id/pay
    //  Creates a booking from an accepted bid at the offered/counter price
    //  Then redirects to SSLComm payment
    // ══════════════════════════════════════════════════════

    public function payBid(Request $request, $id): JsonResponse
    {
        $bid = RoomRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'Approved')
            ->with('room')
            ->first();

        if (!$bid) {
            return $this->sendError('Approved bid not found.', [], 404);
        }

        // Check acceptance window hasn't expired
        $accepted = RoomRequestAccepted::where('room_requests_id', $bid->id)->first();
        if ($accepted && $accepted->request_expiration_time < now()) {
            $bid->update(['status' => 'Timeout']);
            return $this->sendError('Payment window has expired. Please submit a new bid.', [], 422);
        }

        try {
            $booking = DB::transaction(function () use ($bid, $request) {
                // Lock + re-check — room must still be available
                $room = Room::where('id', $bid->room_id)->lockForUpdate()->first();

                $conflict = Booking::where('room_id', $room->id)
                    ->whereIn('status', ['reserved', 'approved'])
                    ->where('checkin',  '<', $bid->check_out)
                    ->where('checkout', '>', $bid->check_in)
                    ->exists();

                if ($conflict) {
                    throw new \Exception('This room was just booked by someone else.');
                }

                // Use counter_price if set (guest accepted counter), else discount_price
                $price   = $bid->counter_price ?? $bid->discount_price;
                $nights  = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));

                $booking = Booking::create([
                    'booking_number' => $this->generateBookingNumber(),
                    'room_id'        => $room->id,
                    'user_id'        => $bid->user_id,
                    'checkin'        => $bid->check_in,
                    'checkout'       => $bid->check_out,
                    'amount'         => $price * $nights,
                    'adult'          => $bid->adult,
                    'children'       => $bid->children,
                    'rooms'          => $bid->rooms,
                    'status'         => 'reserved',
                    'payment_status' => 'pending',
                    'reference'      => 'BID-' . $bid->id,
                ]);

                $room->update([
                    'status'          => 'Reserved',
                    'booked_date'     => $bid->check_in,
                    'booked_off_date' => $bid->check_out,
                ]);

                // Mark bid as Done
                $bid->update(['status' => 'Done']);

                return $booking;
            });

            // Redirect to SSLComm payment
            $bookingController = new BookingController();
            return $bookingController->bookNow(
                $request->merge(['booking_number' => $booking->booking_number])
            );

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    // ══════════════════════════════════════════════════════
    //  OWNER — REMOVE NOTIFICATION
    //  DELETE /portal/room-request/notification/:propertyId
    // ══════════════════════════════════════════════════════

    public function removeNotification(Request $request, $propertyId): JsonResponse
    {
        $deleted = RoomRequest::where('property_id', $propertyId)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['Timeout', 'Declined', 'Done'])
            ->delete();

        return $this->sendSuccess(['deleted' => $deleted]);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Find a bid that belongs to a property owned by the current user.
     */
    private function findOwnerBid(Request $request, $bidId): ?RoomRequest
    {
        $ownedPropertyIds = $request->user()->properties()->pluck('id');

        return RoomRequest::where('id', $bidId)
            ->whereIn('property_id', $ownedPropertyIds)
            ->with(['room', 'user', 'user.profile'])
            ->first();
    }

    /**
     * Generate unique booking number — same logic as BookingController.
     */
    private function generateBookingNumber(): int
    {
        do {
            $number = (int)(now()->format('YmdHi') . rand(1000, 9999));
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    // ADD this method to your existing RoomRequestController
// It was in the old controller but missing from the new one
// Add it inside the class — anywhere before the closing }

    // ══════════════════════════════════════════════════════
    //  ROOM REQUEST NOTIFICATION
    //  GET /portal/room/request-notification
    //  Returns unique properties with pending room requests
    //  for the logged-in user — used by the notification bell
    // ══════════════════════════════════════════════════════

    public function roomRequestNotification(Request $request): JsonResponse
    {
        $roomRequests = RoomRequest::with('room.property')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['Pending', 'Approved', 'Counter'])
            ->get();

        $uniqueProperties = [];

        foreach ($roomRequests as $roomRequest) {
            $property = $roomRequest->room?->property;
            if (!$property) continue;

            $propertyId = $property->id;

            if (!isset($uniqueProperties[$propertyId])) {
                $uniqueProperties[$propertyId] = [
                    'property_id'             => $propertyId,
                    'property_name'           => $property->name,
                    'request_expiration_time' => $roomRequest->request_expiration_time,
                ];
            } else {
                // Keep the earliest expiration time
                if ($roomRequest->request_expiration_time < $uniqueProperties[$propertyId]['request_expiration_time']) {
                    $uniqueProperties[$propertyId]['request_expiration_time'] = $roomRequest->request_expiration_time;
                }
            }
        }

        return $this->sendSuccess([
            'properties' => array_values($uniqueProperties),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  ROOM RESPONSE LIST
    //  GET /portal/room/request-response/{propertyId}
    //  Also add this if missing — used by PropertyResponse page
    // ══════════════════════════════════════════════════════

    public function roomResponselist(Request $request, $propertyId): JsonResponse
    {
        $roomRequests = RoomRequest::where('user_id', $request->user()->id)
            ->where('property_id', $propertyId)
            ->get();

        $totalRequests    = $roomRequests->count();
        $approvedRequests = $roomRequests->where('status', 'Approved')->count();

        $roomRequest = RoomRequest::where('user_id', $request->user()->id)
            ->whereIn('status', ['Approved', 'Counter'])
            ->where('property_id', $propertyId)
            ->with([
                'room',
                'room.primaryImage',
                'room.property.facilities',
                'room.property.place.city',
                'acceptedRequest',
            ])
            ->get();

        return $this->sendSuccess([
            'total_requests'    => $totalRequests,
            'approved_requests' => $approvedRequests,
            'room_request'      => $roomRequest,
        ]);
    }
}
