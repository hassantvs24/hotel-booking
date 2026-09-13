<?php

namespace App\Http\Controllers\API\Portal;

use App\Abstract\Payouts\SSLComm\Customer;
use App\Abstract\Payouts\SSLComm\Payments;
use App\Abstract\Payouts\SSLComm\SSLCommSession;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\Transaction;
use App\Models\User;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\PaymentReceived;
use App\Notifications\Booking\PaymentConfirmedNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends BaseController
{
    private function storeId(): string
    {
        return config('services.sslcommerz.store_id', env('SSLCOMM_STORE_ID', 'hotel674dd7e831e76'));
    }

    private function storePassword(): string
    {
        return config('services.sslcommerz.store_password', env('SSLCOMM_STORE_PASSWORD', 'hotel674dd7e831e76@ssl'));
    }

    private function isSandbox(): bool
    {
        return config('services.sslcommerz.sandbox', true);
    }

    private function validationUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
            : 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
    }

    // ══════════════════════════════════════════════════════
    //  PAY NOW — single booking
    //  POST /portal/booking/pay-now
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
    //  PAY GROUP — multi-room
    //  POST /portal/booking/pay-group
    // ══════════════════════════════════════════════════════

    public function payGroup(Request $request)
    {
        $request->validate(['group_ref' => 'required|exists:booking_groups,group_ref']);

        $group = BookingGroup::where('group_ref', $request->group_ref)
            ->where('user_id', $request->user()->id)
            ->with(['bookings.room.property.place.city'])
            ->firstOrFail();

        if ($group->payment_status === 'paid') {
            return $this->sendError('This booking is already paid.', [], 422);
        }

        $primaryBooking = $group->bookings->first();
        if (!$primaryBooking) {
            return $this->sendError('No bookings found in this group.', [], 422);
        }

        return $this->initiateSSLPayment($primaryBooking, $request->user(), $group);
    }

    // ══════════════════════════════════════════════════════
    //  RETRY PAYMENT
    //  POST /portal/booking/retry-payment
    // ══════════════════════════════════════════════════════

    public function tryToPayAgain(Request $request)
    {
        $request->validate(['booking_number' => 'required|exists:bookings,booking_number']);

        $booking = Booking::where('booking_number', $request->booking_number)
            ->where('user_id', $request->user()->id)
            ->with(['room.property.place.city'])
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            return $this->sendError('This booking is already paid.', [], 422);
        }

        // If part of a group, retry the whole group
        if ($booking->booking_group_id) {
            $group = BookingGroup::find($booking->booking_group_id);
            return $this->initiateSSLPayment($booking, $request->user(), $group);
        }

        return $this->initiateSSLPayment($booking, $request->user());
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT SUCCESS — IPN callback (public, no auth)
    //  POST /payment/success
    //
    //  CRITICAL: validates val_id against SSLCommerz's
    //  Validation API before trusting the payment.
    //  Never trust this callback blindly — anyone could
    //  POST a fake "success" without validation.
    // ══════════════════════════════════════════════════════

    public function paymentSuccess(Request $request): JsonResponse
    {
        $tranId = $request->input('tran_id');
        $valId  = $request->input('val_id');

        Log::info('SSLComm payment success callback', $request->all());

        if (!$tranId || !$valId) {
            Log::warning('SSLComm callback missing tran_id or val_id', $request->all());
            return $this->sendError('Invalid callback payload.', [], 422);
        }

        // ── Verify with SSLCommerz Validation API ─────────
        $verified = $this->verifyPayment($valId);

        if (!$verified) {
            Log::error('SSLComm payment validation FAILED', ['tran_id' => $tranId, 'val_id' => $valId]);
            return $this->sendError('Payment could not be verified.', [], 422);
        }

        // ── tran_id is either a booking_number or a group_ref ──
        $group = BookingGroup::where('group_ref', $tranId)->first();

        if ($group) {
            return $this->confirmGroupPayment($group, $request);
        }

        $booking = Booking::where('booking_number', $tranId)->with('room')->first();

        if (!$booking) {
            Log::error('SSLComm callback — booking not found', ['tran_id' => $tranId]);
            return $this->sendError('Booking not found.', [], 404);
        }

        return $this->confirmSingleBooking($booking, $request);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT FAIL — IPN callback (public, no auth)
    //  POST /payment/fail
    // ══════════════════════════════════════════════════════

    public function paymentFail(Request $request): JsonResponse
    {
        $tranId = $request->input('tran_id');
        Log::warning('SSLComm payment failed', $request->all());

        $this->markFailed($tranId, $request);

        return $this->sendError('Payment failed.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  PAYMENT CANCEL — IPN callback (public, no auth)
    //  POST /payment/cancel
    // ══════════════════════════════════════════════════════

    public function paymentCancel(Request $request): JsonResponse
    {
        Log::info('SSLComm payment cancelled', $request->all());
        // Booking stays "reserved" / "pending" — guest can retry
        return $this->sendError('Payment was cancelled.', [], 422);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Verify the val_id against SSLCommerz's server-side
     * Validation API. This is the ONLY trustworthy check —
     * the POST callback itself can be spoofed by anyone.
     */
    private function verifyPayment(string $valId): bool
    {
        try {
            $response = Http::get($this->validationUrl(), [
                'val_id'    => $valId,
                'store_id'  => $this->storeId(),
                'store_passwd' => $this->storePassword(),
                'format'    => 'json',
            ]);

            if (!$response->successful()) {
                Log::error('SSLComm validation API request failed', ['status' => $response->status()]);
                return false;
            }

            $data = $response->json();

            $statusOk = in_array($data['status'] ?? null, ['VALID', 'VALIDATED']);

            if (!$statusOk) {
                Log::warning('SSLComm validation returned non-valid status', $data);
            }

            return $statusOk;

        } catch (\Exception $e) {
            Log::error('SSLComm validation exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm a single-room booking after validated payment.
     */
    private function confirmSingleBooking(Booking $booking, Request $request): JsonResponse
    {
        if ($booking->payment_status === 'paid') {
            // Already processed — IPN can fire more than once
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

        DB::transaction(function () use ($booking, $request) {
            $booking->update([
                'status'         => 'approved',
                'payment_status' => 'paid',
            ]);

            $booking->room->update(['status' => 'Booked']);

            // updateOrCreate prevents duplicate transaction on IPN retry
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
        });

        $booking->load(['room', 'room.property', 'user', 'transaction']);

        // If this booking was created from a bid, mark the bid as Done
        if ($booking->room_request_id) {
            \App\Models\RoomRequest::where('id', $booking->room_request_id)
                ->update(['status' => 'Done']);
        }

        // Fire events AFTER transaction — real-time broadcast to admin bell
        $bookingEvent = new BookingCreated($booking);
        event($bookingEvent);
        $bookingEvent->notifyAll();

        $paymentEvent = new PaymentReceived($booking);
        event($paymentEvent);
        $paymentEvent->notifyAll();

        return $this->sendSuccess(['message' => 'Payment confirmed.']);
    }

    /**
     * Confirm all bookings inside a group after validated payment.
     */
    private function confirmGroupPayment(BookingGroup $group, Request $request): JsonResponse
    {
        if ($group->payment_status === 'paid') {
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

        DB::transaction(function () use ($group, $request) {
            $group->load('bookings.room.property');

            foreach ($group->bookings as $booking) {
                if ($booking->payment_status === 'paid') continue;

                $booking->update([
                    'status'         => 'approved',
                    'payment_status' => 'paid',
                ]);

                $booking->room->update(['status' => 'Booked']);

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

            $group->update(['payment_status' => 'paid']);
        });

        $group->load('bookings.room.property.user');
        $firstBooking = $group->bookings->first()->load(['room', 'room.property', 'user', 'transaction']);

        // ONE grouped notification for admin — not one per room
        $this->notifyGroupPayment($group, $firstBooking);

        return $this->sendSuccess(['message' => 'Group payment confirmed.']);
    }

    private function notifyGroupPayment(BookingGroup $group, Booking $firstBooking): void
    {
        $roomCount = $group->bookings->count();
        $property  = $firstBooking->room?->property;
        $guestName = $firstBooking->user?->name ?? 'Guest';
        $total     = number_format($group->total_amount);

        $title   = "{$roomCount} room" . ($roomCount > 1 ? 's' : '') . ' booked — ' . ($property?->name ?? 'property');
        $message = "By {$guestName} · BDT {$total} · Ref: {$group->group_ref}";

        // Real-time broadcast (uses first booking as carrier)
        $event = new BookingCreated($firstBooking);
        event($event);

        // ONE admin DB notification — shouldSend() dedupes by group_ref
        \App\Models\User::admins()->each(fn($admin) =>
        $admin->notify(new \App\Notifications\Admin\AdminNotification(
            type:    'booking',
            title:   $title,
            message: $message,
            icon:    'bx-calendar-check',
            color:   'teal',
            extra:   ['group_ref' => $group->group_ref, 'rooms_count' => $roomCount],
            channel: 'admin',
        ))
        );

        // Guest — once for the whole group
        $firstBooking->user?->notify(new PaymentConfirmedNotification($firstBooking, 'guest'));

        // Owner — once
        $property?->user?->notify(new PaymentConfirmedNotification($firstBooking, 'owner'));
    }

    /**
     * Mark a booking (or all bookings in a group) as failed.
     */
    private function markFailed(?string $tranId, Request $request): void
    {
        if (!$tranId) return;

        $group = BookingGroup::where('group_ref', $tranId)->first();

        if ($group) {
            $group->bookings()->update(['payment_status' => 'failed']);
            foreach ($group->bookings as $booking) {
                Transaction::create([
                    'booking_id' => $booking->booking_number,
                    'user_id'    => $booking->user_id,
                    'amount'     => $booking->amount,
                    'status'     => 'failed',
                    'meta'       => json_encode($request->all()),
                ]);
            }
            return;
        }

        $booking = Booking::where('booking_number', $tranId)->first();
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
    }

    /**
     * Build SSLComm payment session and return GatewayPageURL.
     */
    private function initiateSSLPayment(Booking $booking, User $user, ?BookingGroup $group = null)
    {
        $amount    = $group ? $group->total_amount : $booking->amount;
        $tranId    = $group ? $group->group_ref : $booking->booking_number;

        // IMPORTANT: success/fail/cancel here are BROWSER redirect URLs
        // (where the guest's tab navigates to) — they point to the
        // Vue FRONTEND, not this API. The actual payment confirmation
        // happens via SSLComm's server-to-server IPN call to
        // /payment/success which is registered separately in routes
        // and is NOT the same as this success_url.
        $frontendUrl = rtrim(env('APP_FRONTEND_URL', 'http://hotel-booking.test:5173'), '/');

        $paymentSession = new Payments(SSLCommSession::create([
            'store_id'       => $this->storeId(),
            'store_password' => $this->storePassword(),
            'success_url'    => $frontendUrl . '/success',
            'fail_url'       => $frontendUrl . '/failed',
            'cancel_url'     => $frontendUrl . '/cancelled',
            'currency'       => 'BDT',
        ]));

        // Profile may not exist yet — guard against null
        $profile = $user->profile;

        $customer = Customer::createFromArray([
            'name'     => trim(($profile?->first_name ?? '') . ' ' . ($profile?->last_name ?? '')) ?: $user->name,
            'email'    => $user->email,
            'phone'    => $user->phone ?? 'N/A',
            'address1' => $profile?->address  ?? 'N/A',
            'address2' => '',
            'city'     => $profile?->city     ?? 'N/A',
            'state'    => $profile?->state    ?? '',
            'postcode' => $profile?->zip_code ?? '',
            'country'  => $profile?->country  ?? 'Bangladesh',
            'fax'      => '',
        ]);

        $checkin  = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        $rooms = $group
            ? $group->bookings->map(fn($b) => [
                'name'  => $b->room->name ?? 'Room',
                'price' => $b->amount,
            ])->toArray()
            : [['name' => $booking->room->name ?? 'Room', 'price' => $booking->amount]];

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => (string) $tranId,
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

        try {
            // create() returns a RAW JSON STRING from SSLComm's API
            // (see Payments::create() — it returns getBody()->getContents())
            // We must json_decode it before sending to the frontend.
            $rawResponse = $paymentSession->create($customer, $paymentItem);

            $decoded = json_decode($rawResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('SSLComm response was not valid JSON', [
                    'tran_id' => $tranId,
                    'raw'     => $rawResponse,
                ]);
                return $this->sendError('Payment gateway returned an invalid response.', [], 502);
            }

            Log::info('SSLComm session created', [
                'tran_id' => $tranId,
                'status'  => $decoded['status'] ?? 'unknown',
            ]);

            // SSLComm returns status: FAILED with a failedreason if something
            // is wrong with the store credentials or request data
            if (($decoded['status'] ?? null) === 'FAILED') {
                Log::error('SSLComm rejected the session', $decoded);
                return $this->sendError(
                    'Payment gateway rejected the request: ' . ($decoded['failedreason'] ?? 'Unknown reason'),
                    [],
                    422
                );
            }

            // Return the decoded array directly — frontend reads
            // GatewayPageURL straight off this JSON object
            return response()->json($decoded);

        } catch (\Exception $e) {
            Log::error('SSLComm session creation failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return $this->sendError(
                'Could not connect to payment gateway: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}
