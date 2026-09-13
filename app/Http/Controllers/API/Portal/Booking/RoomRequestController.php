<?php

namespace App\Http\Controllers\API\Portal\Booking;

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
    private const MAX_BIDS        = 3;
    private const PENDING_HOURS   = 24;
    private const PAYMENT_HOURS   = 2;
    private const ACTIVE_STATUSES = ['Pending', 'Approved', 'Counter'];

    public function bidCount(Request $request, $roomId): JsonResponse
    {
        $count = RoomRequest::where('user_id', $request->user()->id)
            ->where('room_id', $roomId)
            ->active()
            ->count();

        return $this->sendSuccess([
            'active_bids'    => $count,
            'bids_remaining' => max(0, self::MAX_BIDS - $count),
            'limit_reached'  => $count >= self::MAX_BIDS,
        ]);
    }

    public function myBids(Request $request): JsonResponse
    {
        $bids = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                'room:id,name,base_price,property_id',
                'room.primaryImage',
                'room.property:id,name',
                'room.property.place.city',
            ])
            ->latest()
            ->get()
            ->map(fn($bid) => $this->formatBidForGuest($bid));

        return $this->sendSuccess(['bids' => $bids]);
    }

    public function incoming(Request $request): JsonResponse
    {
        $propertyIds = $request->user()->properties()->pluck('id');

        $bids = RoomRequest::whereIn('property_id', $propertyIds)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->with([
                'room:id,name,base_price,property_id',
                'room.primaryImage',
                'user:id,name,email',
                'user.profile',
            ])
            ->latest()
            ->get()
            ->map(fn($bid) => $this->formatBidForOwner($bid));

        return $this->sendSuccess([
            'bids'      => $bids,
            'pending'   => $bids->where('status', 'Pending')->count(),
            'approved'  => $bids->where('status', 'Approved')->count(),
            'countered' => $bids->where('status', 'Counter')->count(),
        ]);
    }

    public function accept(Request $request, $id): JsonResponse
    {
        $bid       = $this->ownerBid($request->user(), $id, 'Pending');
        $expiresAt = Carbon::now()->addHours(self::PAYMENT_HOURS);

        DB::transaction(function () use ($bid, $expiresAt) {
            $bid->update([
                'status'                  => 'Approved',
                'request_expiration_time' => $expiresAt,
            ]);

            RoomRequestAccepted::updateOrCreate(
                ['room_requests_id' => $bid->id],
                ['request_expiration_time' => $expiresAt]
            );
        });

        $bid->user?->notify(new BidStatusNotification($bid, 'accepted'));

        return $this->sendSuccess([
            'message' => 'Offer accepted. Guest has ' . self::PAYMENT_HOURS . ' hours to pay.',
            'bid'     => $this->formatBidForOwner($bid->fresh()),
        ]);
    }

    public function counter(Request $request, $id): JsonResponse
    {
        $request->validate([
            'counter_price' => 'required|numeric|min:1',
            'message'       => 'nullable|string|max:300',
        ]);

        $bid = $this->ownerBid($request->user(), $id, 'Pending');

        $bid->update([
            'status'                  => 'Counter',
            'counter_price'           => $request->counter_price,
            'message'                 => $request->message,
            'request_expiration_time' => Carbon::now()->addHours(self::PENDING_HOURS),
        ]);

        $bid->user?->notify(new BidStatusNotification($bid, 'countered'));

        return $this->sendSuccess([
            'message' => 'Counter offer sent to guest.',
            'bid'     => $this->formatBidForOwner($bid->fresh()),
        ]);
    }

    public function acceptCounter(Request $request, $id): JsonResponse
    {
        $bid       = $this->guestBid($request->user(), $id, 'Counter');
        $expiresAt = Carbon::now()->addHours(self::PAYMENT_HOURS);

        DB::transaction(function () use ($bid, $expiresAt) {
            $bid->update([
                'status'                  => 'Approved',
                'discount_price'          => $bid->counter_price, // agreed price = counter
                'request_expiration_time' => $expiresAt,
            ]);

            RoomRequestAccepted::updateOrCreate(
                ['room_requests_id' => $bid->id],
                ['request_expiration_time' => $expiresAt]
            );
        });

        $bid->room?->property?->user?->notify(
            new BidStatusNotification($bid, 'counter_accepted')
        );

        return $this->sendSuccess([
            'message' => 'Counter accepted. Please pay within ' . self::PAYMENT_HOURS . ' hours.',
            'bid'     => $this->formatBidForGuest($bid->fresh()),
        ]);
    }

    public function decline(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $bid = RoomRequest::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('property_id', $user->properties()->pluck('id'));
            })
            ->whereIn('status', ['Pending', 'Counter'])
            ->firstOrFail();

        $bid->update(['status' => 'Declined']);

        $isGuest = $bid->user_id === $user->id;
        if ($isGuest) {
            $bid->room?->property?->user?->notify(new BidStatusNotification($bid, 'declined'));
        } else {
            $bid->user?->notify(new BidStatusNotification($bid, 'declined'));
        }

        return $this->sendSuccess(['message' => 'Offer declined.']);
    }

    /**
     * Guest pays an approved bid.
     * Creates a reservation booking linked to the bid via room_request_id.
     * The bid is marked 'Done' in LocalConfirmController / PaymentController
     * after payment succeeds.
     */
    public function payBid(Request $request, $id): JsonResponse
    {
        $bid = $this->guestBid($request->user(), $id, 'Approved');

        if ($bid->request_expiration_time?->isPast()) {
            $bid->update(['status' => 'Timeout']);
            return $this->sendError(
                'The ' . self::PAYMENT_HOURS . '-hour payment window has expired. Please submit a new offer.',
                [], 422
            );
        }

        // Check room is still available
        $taken = Booking::where('room_id', $bid->room_id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $bid->check_out)
            ->where('checkout', '>', $bid->check_in)
            ->exists();

        if ($taken) {
            $bid->update(['status' => 'Declined']);
            return $this->sendError(
                'This room was just booked by someone else. Your bid has been cancelled.',
                [], 409
            );
        }

        // Refresh to get latest discount_price (updated by acceptCounter)
        $bid->refresh();

        $nights      = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));
        $agreedPrice = (float) $bid->discount_price; // = counter_price after acceptCounter
        $totalAmount = $agreedPrice * $nights;

        $booking = Booking::create([
            'booking_number'  => date('Ymd') . rand(10000000, 99999999),
            'room_id'         => $bid->room_id,
            'user_id'         => $request->user()->id,
            'checkin'         => $bid->check_in,
            'checkout'        => $bid->check_out,
            'amount'          => $totalAmount,
            'adult'           => $bid->adult,
            'children'        => $bid->children ?? 0,
            'rooms'           => 1,
            'status'          => 'reserved',
            'payment_status'  => 'pending',
            'room_request_id' => $bid->id, // links back so bid marked Done after payment
        ]);

        // Lock the room
        $bid->room?->update(['status' => 'Reserved']);

        // Initiate SSLComm payment
        return app(\App\Http\Controllers\API\Portal\PaymentController::class)
            ->bookNow($request->merge(['booking_number' => $booking->booking_number]));
    }

    public function roomRequestNotification(Request $request): JsonResponse
    {
        $bids = RoomRequest::where('user_id', $request->user()->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->with('room.property')
            ->get();

        $byProperty = $bids->groupBy('property_id')->map(function ($group) {
            $earliest = $group->sortBy('request_expiration_time')->first();
            return [
                'property_id'             => $earliest->property_id,
                'property_name'           => $earliest->room?->property?->name,
                'request_expiration_time' => $earliest->request_expiration_time,
            ];
        })->values();

        return $this->sendSuccess(['properties' => $byProperty]);
    }

    public function removeNotification(Request $request, $propertyId): JsonResponse
    {
        RoomRequest::where('user_id', $request->user()->id)
            ->where('property_id', $propertyId)
            ->whereIn('status', ['Pending', 'Counter'])
            ->update(['status' => 'Declined']);

        return $this->sendSuccess(['message' => 'Notification removed.']);
    }

    public function roomResponselist(Request $request, $propertyId): JsonResponse
    {
        $all    = RoomRequest::where('user_id', $request->user()->id)
            ->where('property_id', $propertyId)
            ->get();

        $active = $all->whereIn('status', ['Approved', 'Counter'])->values();

        return $this->sendSuccess([
            'total_requests'    => $all->count(),
            'approved_requests' => $all->where('status', 'Approved')->count(),
            'room_request'      => $active->load([
                'room', 'room.primaryImage',
                'room.property.facilities',
                'room.property.place.city',
            ]),
        ]);
    }

    private function ownerBid($user, $id, string $status): RoomRequest
    {
        return RoomRequest::where('id', $id)
            ->whereIn('property_id', $user->properties()->pluck('id'))
            ->where('status', $status)
            ->with('room.property.user', 'user')
            ->firstOrFail();
    }

    private function guestBid($user, $id, string $status): RoomRequest
    {
        return RoomRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', $status)
            ->with('room.property.user', 'user')
            ->firstOrFail();
    }

    private function formatBidForGuest(RoomRequest $bid): array
    {
        $nights = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));

        return [
            'id'                      => $bid->id,
            'status'                  => $bid->status,
            'room'                    => $bid->room,
            'check_in'                => $bid->check_in,
            'check_out'               => $bid->check_out,
            'adult'                   => $bid->adult,
            'children'                => $bid->children,
            'nights'                  => $nights,
            'discount_price'          => $bid->discount_price,
            'counter_price'           => $bid->counter_price,
            'message'                 => $bid->message,
            'bid_number'              => $bid->bid_number,
            'total_offered'           => $bid->discount_price * $nights,
            'room_base_total'         => ($bid->room?->base_price ?? 0) * $nights,
            'is_active'               => in_array($bid->status, self::ACTIVE_STATUSES),
            'request_expiration_time' => $bid->request_expiration_time,
            'seconds_left'            => $bid->request_expiration_time
                ? max(0, now()->diffInSeconds($bid->request_expiration_time, false))
                : 0,
            'created_at'              => $bid->created_at,
        ];
    }

    private function formatBidForOwner(RoomRequest $bid): array
    {
        return [
            ...$this->formatBidForGuest($bid),
            'user'         => $bid->user,
            'discount_pct' => $bid->room?->base_price
                ? round((($bid->room->base_price - $bid->discount_price) / $bid->room->base_price) * 100)
                : 0,
        ];
    }
}
