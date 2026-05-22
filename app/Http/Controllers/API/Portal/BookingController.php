<?php

namespace App\Http\Controllers\API\Portal;

use App\Abstract\Payouts\SSLComm\Customer;
use App\Abstract\Payouts\SSLComm\Payments;
use App\Abstract\Payouts\SSLComm\SSLCommSession;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\BookingGroup;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  PAYMENT DETAILS
    //  GET /portal/room/:id/payment
    //  Used by single-room payment page to show room summary
    // ══════════════════════════════════════════════════════

    public function paymentDetails(Room $room): JsonResponse
    {
        $room->load([
            'images',
            'property',
            'property.place.city',
            'activePrices',
            'bedType',
            'roomType',
        ]);

        return $this->sendSuccess(['room' => $room]);
    }

    // ══════════════════════════════════════════════════════
    //  CHECKOUT — SINGLE OR MULTI-ROOM
    //  POST /portal/booking/checkout
    //
    //  Atomically:
    //   1. Re-validates availability for all cart rooms
    //   2. Creates booking_group (if multi-room)
    //   3. Creates one booking per room
    //   4. Marks rooms as Reserved
    //   5. Clears cart
    //
    //  Uses lockForUpdate() on each room to prevent race conditions.
    //  If ANY room fails → entire transaction rolls back.
    // ══════════════════════════════════════════════════════

    public function checkout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Clean expired items first
        BookingCart::where('user_id', $userId)
            ->where('expires_at', '<', now())
            ->delete();

        $cartItems = BookingCart::where('user_id', $userId)
            ->with('room')
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Your cart is empty or all items have expired.', [], 422);
        }

        $notes = $request->input('notes');

        try {
            $result = DB::transaction(function () use ($cartItems, $userId, $notes) {

                $isMultiRoom  = $cartItems->count() > 1;
                $bookingGroup = null;

                // ── Create booking_group for multi-room ──
                if ($isMultiRoom) {
                    $totalAmount = $cartItems->sum(function ($item) {
                        $nights = max(1, Carbon::parse($item->check_in)->diffInDays($item->check_out));
                        return $item->price * $nights;
                    });

                    $bookingGroup = BookingGroup::create([
                        'user_id'        => $userId,
                        'group_ref'      => 'GRP-' . strtoupper(Str::random(8)),
                        'total_amount'   => $totalAmount,
                        'rooms_count'    => $cartItems->count(),
                        'payment_status' => 'pending',
                        'notes'          => $notes,
                    ]);
                }

                $bookings = [];

                foreach ($cartItems as $item) {
                    // ── Lock row — prevents concurrent double-booking ──
                    $room = Room::where('id', $item->room_id)
                        ->lockForUpdate()
                        ->first();

                    // ── Re-check availability inside the lock ──
                    $conflict = Booking::where('room_id', $room->id)
                        ->whereIn('status', ['reserved', 'approved'])
                        ->where('checkin',  '<', $item->check_out)
                        ->where('checkout', '>', $item->check_in)
                        ->exists();

                    if ($conflict) {
                        // Throws — entire transaction rolls back
                        throw new \Exception(
                            "Room \"{$room->name}\" is no longer available for the selected dates. Please remove it from your cart."
                        );
                    }

                    $nights = max(1, Carbon::parse($item->check_in)->diffInDays($item->check_out));

                    // ── Create booking row ──
                    $booking = Booking::create([
                        'booking_number'   => $this->generateBookingNumber(),
                        'room_id'          => $room->id,
                        'user_id'          => $userId,
                        'checkin'          => $item->check_in,
                        'checkout'         => $item->check_out,
                        'amount'           => $item->price * $nights,
                        'adult'            => $item->adult,
                        'children'         => $item->children,
                        'rooms'            => $item->rooms,
                        'status'           => 'reserved',
                        'payment_status'   => 'pending',
                        'notes'            => $notes,
                        'booking_group_id' => $bookingGroup?->id,
                    ]);

                    // ── Mark room as Reserved ──
                    $room->update([
                        'status'         => 'Reserved',
                        'booked_date'    => $item->check_in,
                        'booked_off_date'=> $item->check_out,
                    ]);

                    $bookings[] = $booking;
                }

                // ── Clear cart after successful booking ──
                BookingCart::where('user_id', $userId)->delete();

                return [
                    'bookings'      => $bookings,
                    'booking_group' => $bookingGroup,
                ];
            });

            return $this->sendSuccess([
                'message'       => 'Booking confirmed! Proceed to payment.',
                'bookings'      => $result['bookings'],
                'booking_group' => $result['booking_group'],
                'group_ref'     => $result['booking_group']?->group_ref,
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    // ══════════════════════════════════════════════════════
    //  SINGLE ROOM DIRECT RESERVE (no cart)
    //  POST /portal/booking/store
    //  For guests who just want one room immediately.
    // ══════════════════════════════════════════════════════

    public function bookingStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adult'     => 'required|integer|min:1',
            'children'  => 'integer|min:0',
            'rooms'     => 'integer|min:1',
            'notes'     => 'nullable|string',
        ]);

        try {
            $booking = DB::transaction(function () use ($validated, $request) {
                $userId = $request->user()->id;

                // Lock + re-check inside transaction
                $room = Room::where('id', $validated['room_id'])
                    ->lockForUpdate()
                    ->first();

                $conflict = Booking::where('room_id', $room->id)
                    ->whereIn('status', ['reserved', 'approved'])
                    ->where('checkin',  '<', $validated['check_out'])
                    ->where('checkout', '>', $validated['check_in'])
                    ->exists();

                if ($conflict) {
                    throw new \Exception('Room is no longer available for the selected dates.');
                }

                $nights  = max(1, Carbon::parse($validated['check_in'])->diffInDays($validated['check_out']));
                $price   = $room->activePrices->first()?->price ?? $room->base_price;

                $booking = Booking::create([
                    'booking_number' => $this->generateBookingNumber(),
                    'room_id'        => $room->id,
                    'user_id'        => $userId,
                    'checkin'        => $validated['check_in'],
                    'checkout'       => $validated['check_out'],
                    'amount'         => $price * $nights,
                    'adult'          => $validated['adult'],
                    'children'       => $validated['children'] ?? 0,
                    'rooms'          => $validated['rooms']    ?? 1,
                    'status'         => 'reserved',
                    'payment_status' => 'pending',
                    'notes'          => $validated['notes'] ?? null,
                ]);

                $room->update([
                    'status'          => 'Reserved',
                    'booked_date'     => $validated['check_in'],
                    'booked_off_date' => $validated['check_out'],
                ]);

                return $booking;
            });

            return $this->sendSuccess([
                'message'        => 'Room reserved. Proceed to payment.',
                'booking'        => $booking,
                'booking_number' => $booking->booking_number,
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    // ══════════════════════════════════════════════════════
    //  PAY NOW — SINGLE BOOKING
    //  POST /portal/booking/pay-now
    //  Initiates SSLComm payment for one booking_number
    // ══════════════════════════════════════════════════════

    public function bookNow(Request $request)
    {
        $request->validate([
            'booking_number' => 'required|exists:bookings,booking_number',
        ]);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->with(['room.property.place.city'])
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            return $this->sendError('This booking is already paid.', [], 422);
        }

        return $this->initiateSSLPayment($booking, $request->user());
    }

    // ══════════════════════════════════════════════════════
    //  PAY GROUP — MULTI-ROOM PAYMENT
    //  POST /portal/booking/pay-group
    //  Pays for all bookings in a booking_group at once
    // ══════════════════════════════════════════════════════

    public function payGroup(Request $request)
    {
        $request->validate([
            'group_ref' => 'required|exists:booking_groups,group_ref',
        ]);

        $group = BookingGroup::where('group_ref', $request->group_ref)
            ->where('user_id', $request->user()->id)
            ->with(['bookings.room.property.place.city'])
            ->firstOrFail();

        if ($group->payment_status === 'paid') {
            return $this->sendError('This booking group is already paid.', [], 422);
        }

        // Use first booking as the primary SSLComm transaction anchor
        // Amount = total group amount
        $primaryBooking = $group->bookings->first();

        return $this->initiateSSLPayment($primaryBooking, $request->user(), $group);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT SUCCESS CALLBACK
    //  POST /payment/success  (public — no auth)
    // ══════════════════════════════════════════════════════

    public function paymentSuccess(Request $request): JsonResponse
    {
        $transactionId = $request->input('tran_id'); // booking_number from SSLComm

        $booking = Booking::where('booking_number', $transactionId)
            ->with('room')
            ->first();

        if (!$booking) {
            return $this->sendError('Booking not found.', [], 404);
        }

        DB::transaction(function () use ($booking, $request) {
            // Mark booking paid
            $booking->update([
                'status'         => 'approved',
                'payment_status' => 'paid',
            ]);

            // Mark room as Booked
            $booking->room->update(['status' => 'Booked']);

            // Create transaction record
            Transaction::create([
                'booking_id'            => $booking->booking_number,
                'user_id'               => $booking->user_id,
                'amount'                => $booking->amount,
                'payment_method'        => 'SSLComm',
                'transaction_reference' => $request->input('bank_tran_id'),
                'status'                => 'completed',
                'meta'                  => json_encode($request->all()),
            ]);

            // If part of a group — check if ALL bookings in group are now paid
            if ($booking->booking_group_id) {
                $group = BookingGroup::find($booking->booking_group_id);
                $allPaid = $group->bookings()->where('payment_status', '!=', 'paid')->doesntExist();
                if ($allPaid) {
                    $group->update(['payment_status' => 'paid']);
                }
            }
        });

        return $this->sendSuccess(['message' => 'Payment successful.']);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT FAIL / CANCEL CALLBACKS
    // ══════════════════════════════════════════════════════

    public function paymentFail(Request $request): JsonResponse
    {
        $booking = Booking::where('booking_number', $request->input('tran_id'))->first();
        if ($booking) {
            $booking->update(['payment_status' => 'failed']);
            Transaction::create([
                'booking_id' => $booking->booking_number,
                'user_id'    => $booking->user_id,
                'amount'     => $booking->amount,
                'status'     => 'failed',
                'meta'       => json_encode($request->all()),
            ]);
        }
        return $this->sendError('Payment failed.', [], 422);
    }

    public function paymentCancel(Request $request): JsonResponse
    {
        return $this->sendError('Payment was cancelled.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  RETRY PAYMENT
    //  POST /portal/booking/retry-payment
    // ══════════════════════════════════════════════════════

    public function tryToPayAgain(Request $request)
    {
        $request->validate([
            'booking_number' => 'required|exists:bookings,booking_number',
        ]);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->initiateSSLPayment($booking, $request->user());
    }

    // ══════════════════════════════════════════════════════
    //  AVAILABILITY CHECK
    //  GET /portal/booking/check/:room
    // ══════════════════════════════════════════════════════

    public function bookingCheck(Request $request, $room): JsonResponse
    {
        $request->validate([
            'check_in'  => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
        ]);

        $conflict = Booking::where('room_id', $room)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin',  '<', $request->check_out)
            ->where('checkout', '>', $request->check_in)
            ->exists();

        return $this->sendSuccess(['available' => !$conflict]);
    }

    // ══════════════════════════════════════════════════════
    //  MY BOOKINGS
    //  GET /portal/booking/my-bookings
    // ══════════════════════════════════════════════════════

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with([
                'room',
                'room.primaryImage',
                'room.roomType',
                'room.property',
                'room.property.place.city',
                'group',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($booking) {
                $booking->nights = max(1,
                    Carbon::parse($booking->checkin)->diffInDays($booking->checkout)
                );
                return $booking;
            });

        // Group by booking_group_id for display
        $grouped = $bookings->groupBy(fn($b) => $b->booking_group_id ?? 'single_' . $b->id);

        return $this->sendSuccess([
            'bookings' => $bookings,
            'grouped'  => $grouped,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKING DETAILS
    //  GET /portal/booking/details
    // ══════════════════════════════════════════════════════

    public function bookingDetails(Request $request): JsonResponse
    {
        $request->validate([
            'booking_number' => 'required',
        ]);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->with(['room.property.place.city', 'group'])
            ->first();

        if (!$booking) {
            return $this->sendError('Booking not found.', [], 404);
        }

        return $this->sendSuccess(['booking' => $booking]);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Generate a unique booking number.
     * Format: timestamp + 4 random digits = 14 digits total.
     */
    private function generateBookingNumber(): int
    {
        do {
            $number = (int)(now()->format('YmdHi') . rand(1000, 9999));
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Initiate SSLComm payment session.
     * Used by bookNow, payGroup, and tryToPayAgain.
     */
    private function initiateSSLPayment(Booking $booking, User $user, ?BookingGroup $group = null)
    {
        $amount = $group ? $group->total_amount : $booking->amount;
        $transactionId = $group
            ? $group->group_ref   // for group payments SSLComm tran_id = group_ref
            : $booking->booking_number;

        $paymentSession = new Payments(SSLCommSession::create([
            'store_id'       => config('sslcomm.store_id', 'hotel674dd7e831e76'),
            'store_password' => config('sslcomm.store_password', 'hotel674dd7e831e76@ssl'),
            'success_url'    => route('payment.success'),
            'fail_url'       => route('payment.fail'),
            'cancel_url'     => route('payment.cancel'),
            'currency'       => 'BDT',
        ]));

        $profile = $user->profile;

        $customer = Customer::createFromArray([
            'name'     => ($profile->first_name ?? '') . ' ' . ($profile->last_name ?? $user->name),
            'email'    => $user->email,
            'phone'    => $user->phone ?? 'N/A',
            'address1' => $profile->address ?? 'N/A',
            'address2' => '',
            'city'     => $profile->city    ?? 'N/A',
            'state'    => $profile->state   ?? '',
            'postcode' => $profile->zip_code ?? '',
            'country'  => $profile->country ?? 'Bangladesh',
            'fax'      => '',
        ]);

        $checkin  = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        // Build rooms array — for group include all rooms
        $rooms = $group
            ? $group->bookings->map(fn($b) => [
                'name'  => $b->room->name ?? 'Room',
                'price' => $b->amount,
            ])->toArray()
            : [['name' => $booking->room->name ?? 'Room', 'price' => $booking->amount]];

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => (string)$transactionId,
            'length_of_stay' => $checkin->diffInDays($checkout) . ' days',
            'hotel_name'     => $booking->room->property->name ?? 'Hotel',
            'hotel_city'     => $booking->room->property->place->city->name ?? 'N/A',
            'rooms'          => $rooms,
            'amount'         => $amount,
            'discount'       => 0,
            'vat'            => 0,
            'fee'            => 0,
            'total'          => $amount,
        ]);

        return $paymentSession->create($customer, $paymentItem);
    }
}
