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
use App\Services\Pricing\PriceService;
use App\Services\Room\RoomGrouper;

class RoomRequestController extends BaseController
{
    private const MAX_BIDS        = 3;
    // How long an offer waits for an answer, and how long a guest has to pay: see config/bidding.php
    private const ACTIVE_STATUSES = ['Pending', 'Approved', 'Counter'];

    public function bidCount(Request $request, $roomId): JsonResponse
    {
        $count = RoomRequest::where('user_id', $request->user()->id)
            ->where('room_id', $roomId)
            ->active()
            ->count();

        // With the stay dates: the offers already spent for those dates (live + declined),
        // and where the next one has to land (above their earlier offer, below an open counter)
        $window = ['floor' => null, 'ceiling' => null];
        if ($request->filled('check_in') && $request->filled('check_out')) {
            $userId = $request->user()->id;
            $count  = RoomRequest::offersUsed(
                $userId, (int) $roomId, $request->input('check_in'), $request->input('check_out')
            );
            $window = RoomRequest::offerWindow(
                $userId, (int) $roomId, $request->input('check_in'), $request->input('check_out')
            );
        }

        return $this->sendSuccess([
            'active_bids'    => $count,
            'bids_remaining' => max(0, self::MAX_BIDS - $count),
            'limit_reached'  => $count >= self::MAX_BIDS,
            'min_price'      => $window['floor'],     // new offer must be above this (per night)
            'max_price'      => $window['ceiling'],   // ...and below this
        ]);
    }

    public function myBids(Request $request): JsonResponse
    {
        RoomRequest::expireLapsed();

        $bids = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                // the rates are needed too: the "with taxes" amounts follow the room's and the property's own VAT / service charge
                'room:id,name,base_price,vat_percent,service_charge_percent,property_id',
                'room.primaryImage',
                'room.property:id,name,vat_percent,service_charge_percent',
                'room.property.place.city',
            ])
            ->latest()
            ->get()
            ->map(fn($bid) => $this->formatBidForGuest($bid));

        return $this->sendSuccess(['bids' => $bids]);
    }

    public function incoming(Request $request): JsonResponse
    {
        RoomRequest::expireLapsed();

        $propertyIds = $request->user()->properties()->pluck('id');

        $bids = RoomRequest::whereIn('property_id', $propertyIds)
            ->active()
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
        $bid = $this->ownerBid($request->user(), $id, 'Pending');

        if ($expired = $this->rejectIfExpired($bid)) {
            return $expired;
        }

        $expiresAt = Carbon::now()->addHours(config('bidding.payment_hours'));

        DB::transaction(function () use ($bid, $expiresAt) {
            $bid->update([
                'status'                  => 'Approved',
                'request_expiration_time' => $expiresAt,
            ]);

            RoomRequestAccepted::updateOrCreate(
                ['room_requests_id' => $bid->id],
                [
                    'property_id'             => $bid->property_id,
                    'request_expiration_time' => $expiresAt,
                ]
            );
        });

        $bid->user?->notify(new BidStatusNotification($bid, 'accepted'));

        return $this->sendSuccess([
            'message' => 'Offer accepted. Guest has ' . config('bidding.payment_hours') . ' hours to pay.',
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

        if ($expired = $this->rejectIfExpired($bid)) {
            return $expired;
        }

        if ($error = $bid->counterPriceError((float) $request->counter_price)) {
            return $this->sendError($error, ['counter_price' => [$error]], 422);
        }

        $bid->update([
            'status'                  => 'Counter',
            'counter_price'           => $request->counter_price,
            'message'                 => $request->message,
            'request_expiration_time' => Carbon::now()->addHours(config('bidding.pending_hours')),
        ]);

        $bid->user?->notify(new BidStatusNotification($bid, 'countered'));

        return $this->sendSuccess([
            'message' => 'Counter offer sent to guest.',
            'bid'     => $this->formatBidForOwner($bid->fresh()),
        ]);
    }

    public function acceptCounter(Request $request, $id): JsonResponse
    {
        $bid = $this->guestBid($request->user(), $id, 'Counter');

        if ($expired = $this->rejectIfExpired($bid)) {
            return $expired;
        }

        $expiresAt = Carbon::now()->addHours(config('bidding.payment_hours'));

        DB::transaction(function () use ($bid, $expiresAt) {
            $bid->update([
                // discount_price stays the guest's original offer; the agreed price
                // is counter_price (see agreedPrice()) so both stay visible afterwards
                'status'                  => 'Approved',
                'request_expiration_time' => $expiresAt,
            ]);

            RoomRequestAccepted::updateOrCreate(
                ['room_requests_id' => $bid->id],
                [
                    'property_id'             => $bid->property_id,
                    'request_expiration_time' => $expiresAt,
                ]
            );
        });

        $bid->room?->property?->user?->notify(
            new BidStatusNotification($bid, 'counter_accepted')
        );

        return $this->sendSuccess([
            'message' => 'Counter accepted. Please pay within ' . config('bidding.payment_hours') . ' hours.',
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
            $bid->room?->property?->user?->notify(new BidStatusNotification($bid, 'withdrawn'));
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
                'The ' . config('bidding.payment_hours') . '-hour payment window has expired. Please submit a new offer.',
                [], 422
            );
        }

        // A guest who reached the gateway and came back to pay again already has an
        // unpaid booking for this bid — reuse it, and don't mistake it for someone else's.
        $existing = Booking::where('room_request_id', $bid->id)
            ->where('status', 'reserved')
            ->where('payment_status', 'pending')
            ->latest('id')
            ->first();

        // Check room is still available (this bid's own booking doesn't count)
        $taken = Booking::where('room_id', $bid->room_id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $bid->check_out)
            ->where('checkout', '>', $bid->check_in)
            ->where(fn ($q) => $q->whereNull('room_request_id')->orWhere('room_request_id', '!=', $bid->id))
            ->exists();

        if ($taken) {
            // The offer was made on one room of a card ("Deluxe"); another room just like it will do
            $sibling = $bid->room
                ? app(RoomGrouper::class)->freeSibling($bid->room, (string) $bid->check_in, (string) $bid->check_out)
                : null;

            if (!$sibling) {
                $bid->update(['status' => 'Declined']);
                return $this->sendError(
                    'This room was just booked by someone else. Your bid has been cancelled.',
                    [], 409
                );
            }

            $bid->update(['room_id' => $sibling->id]);
        }

        $bid->refresh();

        $nights      = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));
        $agreedPrice = $bid->agreed_price;

        $booking = $existing ?? Booking::create([
            'booking_number'  => date('Ymd') . rand(10000000, 99999999),
            'room_id'         => $bid->room_id,
            'user_id'         => $request->user()->id,
            'checkin'         => $bid->check_in,
            'checkout'        => $bid->check_out,
            // the agreed price per night, with VAT and service charge on it
            ...app(PriceService::class)->bookingFields($bid->room, (float) $agreedPrice, $nights),
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
        $response = app(\App\Http\Controllers\API\Portal\PaymentController::class)
            ->bookNow($request->merge(['booking_number' => $booking->booking_number]));

        // The gateway reply doesn't say which booking it is for, and the payment-success
        // page needs that number (stored before the redirect) to confirm the booking.
        if ($response->getStatusCode() === 200) {
            return response()->json(array_merge(
                $response->getData(true),
                ['booking_number' => $booking->booking_number]
            ));
        }

        return $response;
    }

    public function roomRequestNotification(Request $request): JsonResponse
    {
        $bids = RoomRequest::where('user_id', $request->user()->id)
            ->active()
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

    public function roomResponseList(Request $request, $propertyId): JsonResponse
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

    /**
     * A bid past its window can't be acted on — flag it Timeout (the
     * scheduled job would anyway) and tell the caller why.
     */
    private function rejectIfExpired(RoomRequest $bid): ?JsonResponse
    {
        if (!$bid->isExpired()) {
            return null;
        }

        $bid->update(['status' => 'Timeout']);
        RoomRequestAccepted::where('room_requests_id', $bid->id)->delete();

        return $this->sendError('This offer has expired.', [], 422);
    }

    private function ownerBid($user, $id, string $status): RoomRequest
    {
        $bid = RoomRequest::where('id', $id)
            ->whereIn('property_id', $user->properties()->pluck('id'))
            ->where('status', $status)
            ->with('room.property.user', 'user')
            ->firstOrFail();

        $this->abortIfSuperseded($bid);

        return $bid;
    }

    private function guestBid($user, $id, string $status): RoomRequest
    {
        $bid = RoomRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', $status)
            ->with('room.property.user', 'user')
            ->firstOrFail();

        $this->abortIfSuperseded($bid);

        return $bid;
    }

    /** An offer the guest has since replaced with a newer one can no longer be acted on */
    private function abortIfSuperseded(RoomRequest $bid): void
    {
        if ($bid->isSuperseded()) {
            abort(422, 'This offer was replaced by a newer offer — please use the latest one.');
        }
    }

    private function formatBidForGuest(RoomRequest $bid): array
    {
        $nights = max(1, Carbon::parse($bid->check_in)->diffInDays($bid->check_out));
        $svc    = app(PriceService::class);
        $tax    = ['rates' => $svc->ratesFor($bid->room)];

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
            'agreed_price'            => $bid->agreed_price,   // what the guest pays per night
            'agreed_total'            => $bid->agreed_price * $nights,
            'total_offered'           => $bid->discount_price * $nights,
            // the same with VAT and service charge on (what the guest really pays)
            'tax_rates'               => $tax['rates'],
            'agreed_total_with_tax'   => $bid->agreed_price ? $svc->breakdown($bid->agreed_price * $nights, $tax['rates'])['total'] : null,
            'offered_total_with_tax'  => $svc->breakdown($bid->discount_price * $nights, $tax['rates'])['total'],
            'counter_total_with_tax'  => $bid->counter_price ? $svc->breakdown($bid->counter_price * $nights, $tax['rates'])['total'] : null,
            'room_base_total_with_tax' => $svc->breakdown(($bid->room?->base_price ?? 0) * $nights, $tax['rates'])['total'],
            'room_base_total'         => ($bid->room?->base_price ?? 0) * $nights,
            'superseded'              => $superseded = $bid->isSuperseded(),
            'is_active'               => in_array($bid->status, self::ACTIVE_STATUSES) && !$superseded,
            'request_expiration_time' => $bid->request_expiration_time,
            'seconds_left'            => $bid->request_expiration_time
                ? max(0, (int) floor(now()->diffInSeconds($bid->request_expiration_time, false)))
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
