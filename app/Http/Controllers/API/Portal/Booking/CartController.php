<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  CART — LIST
    //  Returns active (non-expired) cart items with room details
    // ══════════════════════════════════════════════════════
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Removing the expired item from the cart.
        BookingCart::query()->where('user_id', $userId)
            ->where('expires_at', '<', now())
            ->delete();

        $items = BookingCart::query()->where('user_id', $userId)
            ->with([
                'room',
                'room.images',
                'room.primaryImage',
                'room.bedType',
                'room.roomType',
                'room.property',
                'room.property.place.city',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function ($item) {
                $nights = Carbon::parse($item->check_in)->diffInDays($item->check_out);
                $item->nights        = max(1, $nights);
                $item->total_price   = $item->price * $item->nights;
                $item->seconds_left  = max(0, now()->diffInSeconds($item->expires_at, false));
                return $item;
            });

        $total = $items->sum('total_price');

        return $this->sendSuccess([
            'items'       => $items,
            'total'       => $total,
            'rooms_count' => $items->count(),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  CART — ADD ROOM
    //  POST /portal/cart/add
    //  Adds one room to the guest's cart.
    //  Soft-holds the room for 15 minutes.
    // ══════════════════════════════════════════════════════

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adult'     => 'required|integer|min:1',
            'children'  => 'integer|min:0',
            'rooms'     => 'integer|min:1',
        ]);

        $userId = $request->user()->id;
        $room   = Room::with('activePrices')->findOrFail($validated['room_id']);

        // ── Checking room isn't already confirmed-booked for these dates ──
        $conflict = Booking::where('room_id', $room->id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $validated['check_out'])
            ->where('checkout', '>', $validated['check_in'])
            ->exists();

        if ($conflict) {
            return $this->sendError('Room is not available for the selected dates.', [], 422);
        }

        // ── Resolve price at cart-add time (locks the price) ──
        $price = $room->activePrices->first()?->price ?? $room->base_price;

        // ── Upsert — replace if same user+room already in cart ──
        BookingCart::updateOrCreate(
            ['user_id' => $userId, 'room_id' => $room->id],
            [
                'check_in'   => $validated['check_in'],
                'check_out'  => $validated['check_out'],
                'adult'      => $validated['adult'],
                'children'   => $validated['children'] ?? 0,
                'rooms'      => $validated['rooms']    ?? 1,
                'price'      => $price,
                'expires_at' => Carbon::now()->addMinutes(15),
            ]
        );

        $cartCount = BookingCart::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->count();

        return $this->sendSuccess([
            'message'    => 'Room added to cart.',
            'cart_count' => $cartCount,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  CART — REMOVE
    //  DELETE /portal/cart/:id
    // ══════════════════════════════════════════════════════

    public function delete(Request $request, $id): JsonResponse
    {
        $deleted = BookingCart::query()->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return $this->sendError('Cart item not found.', [], 404);
        }

        return $this->sendSuccess(['message' => 'Room removed from cart.']);
    }

    // ══════════════════════════════════════════════════════
    //  CART — CLEAR
    //  DELETE /portal/cart
    // ══════════════════════════════════════════════════════

    public function clearCart(Request $request): JsonResponse
    {
        BookingCart::query()
            ->where('user_id', $request->user()->id)
            ->delete();
        return $this->sendSuccess(['message' => 'Cart cleared.']);
    }
}
