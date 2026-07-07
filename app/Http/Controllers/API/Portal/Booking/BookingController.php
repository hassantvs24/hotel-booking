<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Abstract\Payouts\SSLComm\Customer;
use App\Abstract\Payouts\SSLComm\Payments;
use App\Abstract\Payouts\SSLComm\SSLCommSession;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BidSubmitted;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\PaymentReceived;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\BookingGroup;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BID\BidReceivedNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  PAYMENT DETAILS
    // ══════════════════════════════════════════════════════
    public function paymentDetails(Room $room): JsonResponse
    {
        $room->load([
            'images', 'property', 'property.place.city',
            'activePrices', 'bedType', 'roomType',
        ]);

        return $this->sendSuccess(['room' => $room]);
    }

    // ══════════════════════════════════════════════════════
    //  CHECKOUT
    // ══════════════════════════════════════════════════════
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        BookingCart::where('user_id', $user->id)
            ->where('is_bid', false)
            ->where('expires_at', '<', now())
            ->delete();

        $cart = BookingCart::where('user_id', $user->id)
            ->with('room.property.user')
            ->get();

        if ($cart->isEmpty()) {
            return $this->sendError('Your cart is empty or has expired.', [], 422);
        }

        $direct = $cart->where('is_bid', false)->values();
        $bids   = $cart->where('is_bid', true)->values();
        $notes  = $request->input('notes');

        try {
            $result = DB::transaction(function () use ($direct, $bids, $user, $notes, $request) {

                $group    = null;
                $bookings = [];

                // ── Direct bookings ──────────────────────
                if ($direct->isNotEmpty()) {

                    if ($direct->count() > 1) {
                        $group = BookingGroup::create([
                            'user_id'        => $user->id,
                            'group_ref'      => 'GRP-' . strtoupper(Str::random(8)),
                            'total_amount'   => $direct->sum(fn($i) =>
                                $i->price * max(1, Carbon::parse($i->check_in)->diffInDays($i->check_out))
                            ),
                            'rooms_count'    => $direct->count(),
                            'payment_status' => 'pending',
                            'notes'          => $notes,
                        ]);
                    }

                    foreach ($direct as $item) {
                        $room = Room::lockForUpdate()->findOrFail($item->room_id);

                        if (Booking::where('room_id', $room->id)
                            ->whereIn('status', ['reserved', 'approved'])
                            ->where('checkin',  '<', $item->check_out)
                            ->where('checkout', '>', $item->check_in)
                            ->exists()
                        ) {
                            throw new \Exception(
                                "Room \"{$room->name}\" is no longer available."
                            );
                        }

                        $nights    = max(1, Carbon::parse($item->check_in)->diffInDays($item->check_out));
                        $bookings[] = Booking::create([
                            'booking_number'   => date('Ymd') . rand(10000000, 99999999),
                            'room_id'          => $room->id,
                            'user_id'          => $user->id,
                            'checkin'          => $item->check_in,
                            'checkout'         => $item->check_out,
                            'amount'           => $item->price * $nights,
                            'adult'            => $item->adult,
                            'children'         => $item->children,
                            'rooms'            => $item->rooms ?? 1,
                            'status'           => 'reserved',
                            'payment_status'   => 'pending',
                            'notes'            => $notes,
                            'booking_group_id' => $group?->id,
                        ]);

                        $room->update(['status' => 'Reserved']);
                    }
                }

                // ── Bids ─────────────────────────────────
                $createdBids = [];

                foreach ($bids as $item) {
                    $room = Room::with('property.user')->find($item->room_id);
                    if (!$room) continue;

                    if (RoomRequest::where('user_id', $user->id)
                            ->where('room_id', $room->id)
                            ->whereIn('status', ['Pending', 'Approved', 'Counter'])
                            ->count() >= 3
                    ) { continue; }

                    $bid = RoomRequest::create([
                        'room_id'                 => $room->id,
                        'property_id'             => $room->property_id,
                        'user_id'                 => $user->id,
                        'check_in'                => $item->check_in,
                        'check_out'               => $item->check_out,
                        'adult'                   => $item->adult,
                        'children'                => $item->children ?? 0,
                        'discount_price'          => $item->offer_price,
                        'message'                 => $item->bid_message,
                        'bid_number'              => RoomRequest::where('user_id', $user->id)
                                ->where('room_id', $room->id)->count() + 1,
                        'status'                  => 'Pending',
                        'request_expiration_time' => Carbon::now()->addHours(24),
                    ]);

                    // Notify property owner via database notification
                    $room->property->user?->notify(
                        new BidReceivedNotification($bid, $room, $user)
                    );

                    // ── Real-time: notify admin + property owner ──
                    BidSubmitted::dispatch($bid->load(['room', 'user', 'room.property']));

                    $createdBids[] = $bid;
                }

                BookingCart::where('user_id', $user->id)->delete();

                return ['bookings' => $bookings, 'group' => $group, 'bids' => $createdBids];
            });

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        $bookings    = $result['bookings'] ?? [];
        $group       = $result['group']    ?? null;
        $createdBids = $result['bids']     ?? [];
        $hasDirect   = count($bookings) > 0;
        $hasBids     = count($createdBids) > 0;

        // ── Notify admin + owner + guest of new booking ──
        foreach ($bookings as $booking) {
            $event = new BookingCreated($booking->load(['room', 'room.property', 'user']));
            event($event);
            $event->notifyAll();
        }

        return $this->sendSuccess([
            'message'       => $this->checkoutMessage($hasDirect, $hasBids),
            'bookings'      => $bookings,
            'booking_group' => $group,
            'group_ref'     => $group?->group_ref,
            'bids'          => $createdBids,
            'has_direct'    => $hasDirect,
            'has_bids'      => $hasBids,
        ], 201);
    }

    // ══════════════════════════════════════════════════════
    //  SINGLE ROOM RESERVE
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

                $room = Room::where('id', $validated['room_id'])
                    ->lockForUpdate()->first();

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

            // ── Notify admin + owner + guest of new booking ──
            $event = new BookingCreated($booking->load(['room', 'room.property', 'user']));
            event($event);
            $event->notifyAll();

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
    //  PAY NOW
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
    //  PAY GROUP
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

        return $this->initiateSSLPayment($group->bookings->first(), $request->user(), $group);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT SUCCESS
    // ══════════════════════════════════════════════════════
    public function paymentSuccess(Request $request): JsonResponse
    {
        $transactionId = $request->input('tran_id');

        $booking = Booking::where('booking_number', $transactionId)
            ->with(['room', 'room.property', 'user'])
            ->first();

        if (!$booking) {
            return $this->sendError('Booking not found.', [], 404);
        }

        DB::transaction(function () use ($booking, $request) {
            $booking->update([
                'status'         => 'approved',
                'payment_status' => 'paid',
            ]);

            $booking->room->update(['status' => 'Booked']);

            Transaction::create([
                'booking_id'            => $booking->booking_number,
                'user_id'               => $booking->user_id,
                'amount'                => $booking->amount,
                'payment_method'        => 'SSLComm',
                'transaction_reference' => $request->input('bank_tran_id'),
                'status'                => 'completed',
                'meta'                  => json_encode($request->all()),
            ]);

            if ($booking->booking_group_id) {
                $group    = BookingGroup::find($booking->booking_group_id);
                $allPaid  = $group->bookings()->where('payment_status', '!=', 'paid')->doesntExist();
                if ($allPaid) $group->update(['payment_status' => 'paid']);
            }
        });

        // ── Notify admin + owner + guest — payment confirmed ──
        $payEvent = new PaymentReceived($booking->fresh(['room', 'room.property', 'user', 'transaction']));
        event($payEvent);
        $payEvent->notifyAll();

        return $this->sendSuccess(['message' => 'Payment successful.']);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT FAIL / CANCEL
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
    //  CANCEL BOOKING (user cancels)
    // ══════════════════════════════════════════════════════
    public function cancelBooking(Request $request): JsonResponse
    {
        $request->validate([
            'booking_number' => 'required|exists:bookings,booking_number',
        ]);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $booking->update(['status' => 'cancelled']);

        Room::where('id', $booking->room_id)->update([
            'status'          => 'Available',
            'booked_date'     => null,
            'booked_off_date' => null,
        ]);

        // ── Notify admin + property owner ──
        BookingCancelled::dispatch(
            $booking->load(['room', 'room.property', 'user'])
        );

        return $this->sendSuccess(['message' => 'Booking cancelled successfully.']);
    }

    // ══════════════════════════════════════════════════════
    //  AVAILABILITY CHECK
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
    // ══════════════════════════════════════════════════════
    public function myBookings(Request $request): JsonResponse
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with([
                'room', 'room.primaryImage', 'room.roomType',
                'room.property', 'room.property.place.city', 'group',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($booking) {
                $booking->nights = max(1,
                    Carbon::parse($booking->checkin)->diffInDays($booking->checkout)
                );
                return $booking;
            });

        return $this->sendSuccess([
            'bookings' => $bookings,
            'grouped'  => $bookings->groupBy(fn($b) => $b->booking_group_id ?? 'single_' . $b->id),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  BOOKING DETAILS
    // ══════════════════════════════════════════════════════
    public function bookingDetails(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if ($request->filled('group_ref')) {
            $group = BookingGroup::where('group_ref', $request->group_ref)
                ->where('user_id', $userId)
                ->with(['bookings.room.primaryImage', 'bookings.room.property', 'bookings.room.property.place.city'])
                ->first();

            if (!$group) return $this->sendError('Booking group not found.', [], 404);

            return $this->sendSuccess(['booking_group' => $group, 'bookings' => $group->bookings]);
        }

        if ($request->filled('booking_number')) {
            $booking = Booking::where('booking_number', $request->booking_number)
                ->where('user_id', $userId)
                ->with(['room.primaryImage', 'room.property', 'room.property.place.city'])
                ->first();

            if (!$booking) return $this->sendError('Booking not found.', [], 404);

            return $this->sendSuccess(['booking' => $booking]);
        }

        return $this->sendError('booking_number or group_ref is required.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════
    private function generateBookingNumber(): int
    {
        do {
            $number = (int)(now()->format('YmdHi') . rand(1000, 9999));
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    private function initiateSSLPayment(Booking $booking, User $user, ?BookingGroup $group = null)
    {
        $amount        = $group ? $group->total_amount : $booking->amount;
        $transactionId = $group ? $group->group_ref    : $booking->booking_number;

        $paymentSession = new Payments(SSLCommSession::create([
            'store_id'       => config('sslcomm.store_id',       'hotel674dd7e831e76'),
            'store_password' => config('sslcomm.store_password', 'hotel674dd7e831e76@ssl'),
            'success_url'    => route('payment.success'),
            'fail_url'       => route('payment.fail'),
            'cancel_url'     => route('payment.cancel'),
            'currency'       => 'BDT',
        ]));

        $profile  = $user->profile;
        $customer = Customer::createFromArray([
            'name'     => ($profile->first_name ?? '') . ' ' . ($profile->last_name ?? $user->name),
            'email'    => $user->email,
            'phone'    => $user->phone ?? 'N/A',
            'address1' => $profile->address  ?? 'N/A',
            'address2' => '',
            'city'     => $profile->city     ?? 'N/A',
            'state'    => $profile->state    ?? '',
            'postcode' => $profile->zip_code ?? '',
            'country'  => $profile->country  ?? 'Bangladesh',
            'fax'      => '',
        ]);

        $checkin  = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        $rooms = $group
            ? $group->bookings->map(fn($b) => ['name' => $b->room->name ?? 'Room', 'price' => $b->amount])->toArray()
            : [['name' => $booking->room->name ?? 'Room', 'price' => $booking->amount]];

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => (string) $transactionId,
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

    private function checkoutMessage(bool $hasDirect, bool $hasBids): string
    {
        return match(true) {
            $hasDirect && $hasBids => 'Rooms booked! Your offers have also been sent to owners.',
            $hasDirect             => 'Booking confirmed! Proceed to payment.',
            $hasBids               => 'Offers sent! You\'ll be notified when owners respond.',
            default                => 'Checkout complete.',
        };
    }
}
