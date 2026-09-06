<?php

namespace App\Http\Controllers\API\Portal\Booking;

use App\Abstract\Payouts\SSLComm\Customer;
use App\Abstract\Payouts\SSLComm\Payments;
use App\Abstract\Payouts\SSLComm\SSLCommSession;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\PaymentReceived;
use App\Events\Booking\BidSubmitted;
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
    //  Creates bookings with status=reserved and bids.
    //  Does NOT fire payment yet — guest proceeds to pay separately.
    //  Notification: admin notified ONLY when payment succeeds.
    // ══════════════════════════════════════════════════════
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Clean expired non-bid carts
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
            $result = DB::transaction(function () use ($direct, $bids, $user, $notes) {

                $group    = null;
                $bookings = [];

                // ── Direct bookings ──────────────────────────────
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

                        // Conflict check
                        if (Booking::where('room_id', $room->id)
                            ->whereIn('status', ['reserved', 'approved'])
                            ->where('checkin', '<', $item->check_out)
                            ->where('checkout', '>', $item->check_in)
                            ->exists()
                        ) {
                            throw new \Exception("Room \"{$room->name}\" is no longer available.");
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
                            'status'           => 'reserved',      // ← reserved, not pending
                            'payment_status'   => 'pending',
                            'notes'            => $notes,
                            'booking_group_id' => $group?->id,
                        ]);

                        // Lock the room
                        $room->update(['status' => 'Reserved']);
                    }
                }

                // ── Bids ─────────────────────────────────────────
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

                    // Real-time broadcast to admin + owner channels
                    // notifyAll() handles admin DB + guest DB
                    // BidReceivedNotification (owner WebPush) fired separately below
                    $bidEvent = new BidSubmitted($bid->load(['room', 'room.property', 'user']));
                    event($bidEvent);
                    $bidEvent->notifyAll();

                    // Owner WebPush — separate from real-time, fired AFTER notifyAll
                    // to avoid duplicate since notifyAll no longer calls this
                    $room->property->user?->notify(
                        new BidReceivedNotification($bid, $room, $user)
                    );

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
    //  SINGLE ROOM RESERVE (no cart)
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
                $room = Room::lockForUpdate()->findOrFail($validated['room_id']);

                if (Booking::where('room_id', $room->id)
                    ->whereIn('status', ['reserved', 'approved'])
                    ->where('checkin', '<', $validated['check_out'])
                    ->where('checkout', '>', $validated['check_in'])
                    ->exists()
                ) {
                    throw new \Exception('Room is no longer available for the selected dates.');
                }

                $nights  = max(1, Carbon::parse($validated['check_in'])->diffInDays($validated['check_out']));
                $price   = $room->activePrices->first()?->price ?? $room->base_price;

                $booking = Booking::create([
                    'booking_number' => $this->generateBookingNumber(),
                    'room_id'        => $room->id,
                    'user_id'        => $request->user()->id,
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
    //  PAY NOW — single booking
    // ══════════════════════════════════════════════════════
    public function bookNow(Request $request)
    {
        $request->validate(['booking_number' => 'required|exists:bookings,booking_number']);

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
    //  PAY GROUP — multi-room payment
    // ══════════════════════════════════════════════════════
    public function payGroup(Request $request)
    {
        $request->validate(['group_ref' => 'required|exists:booking_groups,group_ref']);

        $group = BookingGroup::where('group_ref', $request->group_ref)
            ->where('user_id', $request->user()->id)
            ->with(['bookings.room.property.place.city'])
            ->firstOrFail();

        if ($group->payment_status === 'paid') {
            return $this->sendError('This group is already paid.', [], 422);
        }

        return $this->initiateSSLPayment($group->bookings->first(), $request->user(), $group);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT SUCCESS
    //  This is the ONLY place BookingCreated fires.
    //  Room is confirmed booked, payment is saved.
    //  If network error caused this to be called twice,
    //  the transaction check prevents double-processing.
    // ══════════════════════════════════════════════════════
    public function paymentSuccess(Request $request): JsonResponse
    {
        $tranId = $request->input('tran_id');

        // Find booking or group
        $group = BookingGroup::where('group_ref', $tranId)->first();

        DB::transaction(function () use ($tranId, $group, $request) {

            if ($group) {
                // ── Group payment ──────────────────────────────
                // Idempotency: skip if already paid
                if ($group->payment_status === 'paid') return;

                foreach ($group->bookings as $booking) {
                    $this->confirmBooking($booking, $request);
                }

                $group->update(['payment_status' => 'paid']);

                // Only notify once — check if already notified for this group
                $firstBooking = $group->bookings->first()->load(['room', 'room.property', 'user', 'transaction']);

                $alreadyNotified = $firstBooking->user?->notifications()
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.group_ref')) = ?", [$group->group_ref])
                    ->exists() ?? false;

                if (!$alreadyNotified) {
                    $this->notifyGroupBooking($group, $firstBooking);
                }

            } else {
                // ── Single booking ─────────────────────────────
                $booking = Booking::where('booking_number', $tranId)
                    ->with(['room', 'room.property', 'user'])
                    ->first();

                if (!$booking) return;

                // Idempotency: skip if already paid
                $alreadyPaid = $booking->payment_status === 'paid';
                if ($alreadyPaid) return;

                $this->confirmBooking($booking, $request);

                $booking->load(['room', 'room.property', 'user', 'transaction']);

                // Only notify once — skip if already notified for this booking
                $alreadyNotified = $booking->user?->notifications()
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.booking_number')) = ?", [$booking->booking_number])
                    ->exists() ?? false;

                if (!$alreadyNotified) {
                    $event = new BookingCreated($booking);
                    event($event);
                    $event->notifyAll();

                    $payEvent = new PaymentReceived($booking);
                    event($payEvent);
                    $payEvent->notifyAll();
                }
            }
        });

        return $this->sendSuccess(['message' => 'Payment successful.']);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT FAIL
    //  Room stays RESERVED — guest can retry payment.
    //  Transaction saved as failed for audit trail.
    // ══════════════════════════════════════════════════════
    public function paymentFail(Request $request): JsonResponse
    {
        $tranId = $request->input('tran_id');

        $group = BookingGroup::where('group_ref', $tranId)->first();

        if ($group) {
            // Group payment failed — all bookings stay reserved
            foreach ($group->bookings as $booking) {
                $this->recordFailedTransaction($booking, $request);
            }
            $group->update(['payment_status' => 'failed']);
        } else {
            $booking = Booking::where('booking_number', $tranId)->first();
            if ($booking) {
                // Room stays reserved so guest can retry
                $this->recordFailedTransaction($booking, $request);
            }
        }

        return $this->sendError('Payment failed. Your room is still reserved — you can retry payment.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT CANCEL
    //  Room stays RESERVED — guest can retry payment.
    // ══════════════════════════════════════════════════════
    public function paymentCancel(Request $request): JsonResponse
    {
        // Room stays reserved, guest can pay later
        return $this->sendError('Payment cancelled. Your room is still reserved.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  RETRY PAYMENT
    //  Guest can pay again after fail/cancel.
    //  Room must still be reserved.
    // ══════════════════════════════════════════════════════
    public function tryToPayAgain(Request $request)
    {
        $request->validate([
            'booking_number' => 'nullable|exists:bookings,booking_number',
            'group_ref'      => 'nullable|exists:booking_groups,group_ref',
        ]);

        if ($request->filled('group_ref')) {
            $group = BookingGroup::where('group_ref', $request->group_ref)
                ->where('user_id', $request->user()->id)
                ->with('bookings.room.property.place.city')
                ->firstOrFail();

            if ($group->payment_status === 'paid') {
                return $this->sendError('This group is already paid.', [], 422);
            }

            return $this->initiateSSLPayment($group->bookings->first(), $request->user(), $group);
        }

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            return $this->sendError('Already paid.', [], 422);
        }

        if ($booking->status === 'cancelled') {
            return $this->sendError('This booking has been cancelled and cannot be paid.', [], 422);
        }

        return $this->initiateSSLPayment($booking, $request->user());
    }

    // ══════════════════════════════════════════════════════
    //  CANCEL BOOKING
    //  Frees the room and notifies admin + owner.
    // ══════════════════════════════════════════════════════
    public function cancelBooking(Request $request): JsonResponse
    {
        $request->validate(['booking_number' => 'required|exists:bookings,booking_number']);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            return $this->sendError('Paid bookings cannot be self-cancelled. Contact support.', [], 422);
        }

        $booking->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);

        Room::where('id', $booking->room_id)->update([
            'status'          => 'Available',
            'booked_date'     => null,
            'booked_off_date' => null,
        ]);

        $event = new BookingCancelled($booking->load(['room', 'room.property', 'user']));
        event($event);
        $event->notifyAll();

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
            ->where('checkin', '<', $request->check_out)
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

            if (!$group) return $this->sendError('Group not found.', [], 404);

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

    /**
     * Confirm a booking after successful payment.
     * Idempotent — safe to call multiple times.
     */
    private function confirmBooking(Booking $booking, Request $request): void
    {
        $booking->update([
            'status'         => 'approved',
            'payment_status' => 'paid',
        ]);

        $booking->room->update(['status' => 'Booked']);

        // Upsert transaction — prevents duplicate on network retry
        Transaction::updateOrCreate(
            ['booking_id' => $booking->booking_number],
            [
                'user_id'               => $booking->user_id,
                'amount'                => $booking->amount,
                'payment_method'        => 'SSLComm',
                'transaction_reference' => $request->input('bank_tran_id'),
                'status'                => 'completed',
                'meta'                  => json_encode($request->all()),
            ]
        );
    }

    /**
     * Record a failed payment without changing booking/room status.
     * Room stays reserved so guest can retry.
     */
    private function recordFailedTransaction(Booking $booking, Request $request): void
    {
        Transaction::updateOrCreate(
            ['booking_id' => $booking->booking_number],
            [
                'user_id'               => $booking->user_id,
                'amount'                => $booking->amount,
                'payment_method'        => 'SSLComm',
                'transaction_reference' => $request->input('bank_tran_id', 'FAILED'),
                'status'                => 'failed',
                'meta'                  => json_encode($request->all()),
            ]
        );

        // Update booking payment_status to failed but keep status=reserved
        $booking->update(['payment_status' => 'failed']);
    }

    /**
     * Send ONE grouped notification for a multi-room booking.
     */
    private function notifyGroupBooking(BookingGroup $group, Booking $firstBooking): void
    {
        $bookings  = $group->bookings->load(['room', 'room.property']);
        $roomCount = $bookings->count();
        $property  = $firstBooking->room?->property;
        $guestName = $firstBooking->user?->name ?? 'Guest';
        $groupRef  = $group->group_ref;
        $total     = number_format($group->total_amount);

        $title   = "{$roomCount} room" . ($roomCount > 1 ? 's' : '') . ' booked — ' . ($property?->name ?? 'property');
        $message = "By {$guestName} · BDT {$total} · Ref: {$groupRef}";

        // Real-time broadcast — use first booking as carrier
        $broadcastPayload = $firstBooking->load(['room', 'room.property', 'user', 'transaction']);

        // Override broadcastWith data by firing a BookingCreated
        // but with custom title via the extra field in AdminNotification
        $event = new BookingCreated($broadcastPayload);
        event($event);

        // DB notification — ONE for all admins, grouped title
        \App\Models\User::admins()->each(fn($admin) =>
        $admin->notify(new \App\Notifications\Admin\AdminNotification(
            type:    'booking',
            title:   $title,
            message: $message,
            icon:    'bx-calendar-check',
            color:   'teal',
            extra:   ['group_ref' => $groupRef, 'rooms_count' => $roomCount],
            channel: 'admin',
        ))
        );

        // Guest confirmation
        if ($firstBooking->user) {
            $firstBooking->user->notify(
                new \App\Notifications\Booking\PaymentConfirmedNotification($firstBooking, 'guest')
            );
        }

        // Owner confirmation
        if ($property?->user) {
            $property->user->notify(
                new \App\Notifications\Booking\PaymentConfirmedNotification($firstBooking, 'owner')
            );
        }
    }


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
            $hasDirect             => 'Room reserved. Proceed to payment.',
            $hasBids               => 'Offers sent! You\'ll be notified when owners respond.',
            default                => 'Checkout complete.',
        };
    }
}
