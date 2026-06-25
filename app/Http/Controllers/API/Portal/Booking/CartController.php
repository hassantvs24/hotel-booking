<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\Room;
use App\Models\RoomRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends BaseController
{
    private const CART_HOLD_MINUTES = 30;
    private const MAX_BIDS          = 3;

    // ─────────────────────────────────────────────────────
    //  ADD TO CART
    //  POST /portal/cart/add
    // ─────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'check_in'    => 'required|date|after_or_equal:today',
            'check_out'   => 'required|date|after:check_in',
            'adult'       => 'required|integer|min:1',
            'children'    => 'integer|min:0',
            'rooms'       => 'integer|min:1',
            'is_bid'      => 'nullable',
            'offer_price' => 'required_if:is_bid,true|nullable|numeric|min:1',
            'bid_message' => 'nullable|string|max:300',
        ]);

        $user = $request->user();

        // filter_var handles true/false/"true"/"1"/1 from both JSON and form-data
        $isBid = filter_var($data['is_bid'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $room  = Room::findOrFail($data['room_id']);

        if ($isBid) {
            $this->checkBidLimit($user->id, $room->id);
            $price     = $data['offer_price'];
            $expiresAt = null; // bid items never expire from cart

        } else {
            $this->checkAvailability($room->id, $data['check_in'], $data['check_out']);
            $price     = $room->base_price;
            $expiresAt = Carbon::now()->addMinutes(self::CART_HOLD_MINUTES);
        }

        BookingCart::updateOrCreate(
            ['user_id' => $user->id, 'room_id' => $room->id],
            [
                'check_in'    => $data['check_in'],
                'check_out'   => $data['check_out'],
                'adult'       => $data['adult'],
                'children'    => $data['children'] ?? 0,
                'rooms'       => $data['rooms']    ?? 1,
                'price'       => $price,
                'expires_at'  => $expiresAt,
                'is_bid'      => $isBid,
                'offer_price' => $isBid ? $data['offer_price'] : 0,
                'bid_message' => $isBid ? ($data['bid_message'] ?? null) : null,
            ]
        );

        return $this->sendSuccess([
            'message'    => $isBid ? 'Offer added to cart.' : 'Room added to cart.',
            'cart_count' => $this->cartCount($user->id),
        ]);
    }

    // ─────────────────────────────────────────────────────
    //  GET CART
    //  GET /portal/cart
    // ─────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Remove expired direct items only — bids have no expiry
        BookingCart::where('user_id', $userId)
            ->where('is_bid', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->delete();

        $items = BookingCart::where('user_id', $userId)
            ->with([
                'room:id,name,base_price,property_id,status',
                'room.primaryImage',
                'room.images',
                'room.bedType',
                'room.roomType',
                'room.property:id,name',
                'room.property.place.city',
            ])
            ->oldest()
            ->get()
            ->map(fn($item) => $this->enrichItem($item));

        $direct = $items->where('is_bid', false)->values();
        $bids   = $items->where('is_bid', true)->values();

        return $this->sendSuccess([
            'items'        => $items,
            'direct_items' => $direct,
            'bid_items'    => $bids,
            'total'        => $direct->sum('total_price'), // charged now
            'bid_total'    => $bids->sum('total_price'),   // charged after accept
            'rooms_count'  => $items->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────
    //  REMOVE ITEM
    //  DELETE /portal/cart/{id}
    // ─────────────────────────────────────────────────────

    public function delete(Request $request, $id): JsonResponse
    {
        BookingCart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail()
            ->delete();

        return $this->sendSuccess(['message' => 'Item removed.']);
    }

    // ─────────────────────────────────────────────────────
    //  CLEAR CART
    //  DELETE /portal/cart
    // ─────────────────────────────────────────────────────

    public function clearCart(Request $request): JsonResponse
    {
        BookingCart::where('user_id', $request->user()->id)->delete();
        return $this->sendSuccess(['message' => 'Cart cleared.']);
    }

    // ─────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────

    private function checkBidLimit(int $userId, int $roomId): void
    {
        $active = RoomRequest::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->active() // scope on RoomRequest model: whereIn status Pending/Approved/Counter
            ->count();

        $inCart = BookingCart::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('is_bid', true)
            ->count();

        if (($active + $inCart) >= self::MAX_BIDS) {
            abort(422, 'You have reached the maximum of ' . self::MAX_BIDS . ' bids for this room.');
        }
    }

    private function checkAvailability(int $roomId, string $checkIn, string $checkOut): void
    {
        $taken = Booking::where('room_id', $roomId)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $checkOut)
            ->where('checkout', '>', $checkIn)
            ->exists();

        if ($taken) {
            abort(422, 'This room is not available for the selected dates.');
        }
    }

    private function enrichItem(BookingCart $item): BookingCart
    {
        // Cast is_bid to bool — MySQL returns 0/1 integers.
        // Collection::where('is_bid', true) uses strict === comparison
        // so 1 !== true — bid items would disappear from both groups
        $item->is_bid = (bool) $item->is_bid;

        $nights            = max(1, Carbon::parse($item->check_in)->diffInDays($item->check_out));
        $item->nights      = $nights;
        $item->total_price = $item->price * $nights;

        // Bid items have no cart timer — null means "no expiry"
        // Direct items get a live countdown in seconds
        $item->seconds_left = $item->is_bid
            ? null
            : ($item->expires_at
                ? max(0, Carbon::now()->diffInSeconds($item->expires_at, false))
                : 0);

        return $item;
    }

    private function cartCount(int $userId): int
    {
        return BookingCart::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('is_bid', true)
                    ->orWhere(function ($q2) {
                        $q2->where('is_bid', false)
                            ->where('expires_at', '>', Carbon::now());
                    });
            })
            ->count();
    }
}
