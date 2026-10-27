<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Pricing\PriceService;
use App\Services\Room\RoomGrouper;

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
        $room  = Room::with('property:id,status')->findOrFail($data['room_id']);

        // The search and the room list already hide these cases; the server holds the same line
        if (($room->property?->status ?? null) !== Property::STATUS_PUBLISHED) {
            abort(422, 'This property is not available for booking.');
        }

        $guests = (int) $data['adult'] + (int) ($data['children'] ?? 0);   // the children sleep in the room too

        if ((int) $room->guest_capacity > 0 && $guests > (int) $room->guest_capacity) {
            abort(422, "This room takes up to {$room->guest_capacity} guest" . ($room->guest_capacity > 1 ? 's' : '') . '. Please choose a bigger room or fewer guests.');
        }

        if ($isBid) {
            $this->checkBidLimit($user->id, $room->id, $data['check_in'], $data['check_out']);
            $this->checkOfferPrice($room, (float) ($data['offer_price'] ?? 0));

            // Negotiation: stay above their own earlier offer, below an open counter
            if ($err = RoomRequest::offerWindowError(
                $user->id, $room->id, $data['check_in'], $data['check_out'], (float) $data['offer_price']
            )) {
                abort(422, $err);
            }
            $price     = $data['offer_price'];
            $expiresAt = null; // bid items never expire from cart

        } else {
            // The room was taken after the guest saw it: another room of the same type will do
            if ($this->isTaken($room->id, $data['check_in'], $data['check_out'])) {
                $room = app(RoomGrouper::class)->freeSibling(
                    $room, $data['check_in'], $data['check_out'], BookingCart::where('user_id', $user->id)->pluck('room_id')->all()
                ) ?? abort(422, 'This room is not available for the selected dates.');
            }
            $price     = app(RoomGrouper::class)->priceOf($room);   // same price the room card showed
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
            ->whereHas('room')   // a deleted room cannot be booked any more
            ->with([
                'room:id,name,base_price,vat_percent,service_charge_percent,property_id,status',
                'room.primaryImage',
                'room.images',
                'room.bedType',
                'room.roomType',
                'room.property:id,name,vat_percent,service_charge_percent',
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
            'total'        => $direct->sum('total_price'), // charged now (VAT and service charge included)
            'bid_total'    => $bids->sum('total_price'),   // charged after accept (taxes included)
            'summary'      => [
                'subtotal'       => round($direct->sum('base_total'), 2),
                'service_charge' => round($direct->sum('service_charge_total'), 2),
                'vat'            => round($direct->sum('vat_total'), 2),
                'total'          => round($direct->sum('total_price'), 2),
                'offers_subtotal'       => round($bids->sum('base_total'), 2),
                'offers_service_charge' => round($bids->sum('service_charge_total'), 2),
                'offers_vat'            => round($bids->sum('vat_total'), 2),
                'offers_total'          => round($bids->sum('total_price'), 2),
            ],
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

    private function checkBidLimit(int $userId, int $roomId, string $checkIn, string $checkOut): void
    {
        // Offers already spent on this room for these stay dates (live + declined).
        // The cart row is not counted: adding another offer for the room replaces it.
        $used = RoomRequest::offersUsed($userId, $roomId, $checkIn, $checkOut);

        if ($used >= self::MAX_BIDS) {
            abort(422, 'You have used all ' . self::MAX_BIDS . ' offers for this room and these dates.');
        }
    }

    /**
     * The offer modal only lets a guest pick 1%–99% off the room's base price;
     * the API has to hold the same line, since the price comes from the client.
     */
    private function checkOfferPrice(Room $room, float $offer): void
    {
        $base = (float) $room->base_price;

        if ($base <= 0) {
            return;
        }

        // Whole cents, with one cent of slack each side: the browser rounds the
        // discounted price itself, and it can land a cent away from PHP's rounding.
        $baseCents = (int) round($base * 100);
        $minCents  = (int) round($baseCents * 0.01);
        $maxCents  = (int) round($baseCents * 0.99);
        $offerCents = (int) round($offer * 100);

        if ($offerCents < $minCents - 1 || $offerCents > $maxCents + 1) {
            abort(422, 'Your offer must be between BDT ' . number_format($minCents / 100, 2)
                . ' and BDT ' . number_format($maxCents / 100, 2) . ' (1%–99% off the room price).');
        }
    }

    private function isTaken(int $roomId, string $checkIn, string $checkOut): bool
    {
        return Booking::where('room_id', $roomId)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $checkOut)
            ->where('checkout', '>', $checkIn)
            ->exists();
    }

    private function enrichItem(BookingCart $item): BookingCart
    {
        // Cast is_bid to bool — MySQL returns 0/1 integers.
        // Collection::where('is_bid', true) uses strict === comparison
        // so 1 !== true — bid items would disappear from both groups
        $item->is_bid = (bool) $item->is_bid;

        $nights       = max(1, Carbon::parse($item->check_in)->diffInDays($item->check_out));
        $item->nights = $nights;

        // price = the room price per night (an offer's price for a bid); VAT and service charge go on top.
        // total_price is what the guest pays (or would pay, for an offer), so every screen that adds it up
        // stays right; the parts are there for the price summary.
        $tax = app(PriceService::class)->forStay($item->room, (float) $item->price, $nights);

        $item->base_total            = $tax['stay']['base'];
        $item->service_charge_total  = $tax['stay']['service_charge'];
        $item->vat_total             = $tax['stay']['vat'];
        $item->price_with_tax        = $tax['per_night']['total'];
        $item->tax_rates             = $tax['rates'];
        $item->total_price           = $tax['stay']['total'];

        // Bid items have no cart timer — null means "no expiry"
        // Direct items get a live countdown in seconds
        $item->seconds_left = $item->is_bid
            ? null
            : ($item->expires_at
                ? max(0, (int) floor(Carbon::now()->diffInSeconds($item->expires_at, false)))   // whole seconds (Carbon 3 returns a fraction)
                : 0);

        return $item;
    }

    private function cartCount(int $userId): int
    {
        return BookingCart::where('user_id', $userId)
            ->whereHas('room')
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
