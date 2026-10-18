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
use App\Services\Booking\BookingConfirmationMailer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends BaseController
{
    // Everything about SSLCommerz comes from config/sslcommerz.php (which reads .env). Nothing is read straight from
    // env() here, and there are no built-in store credentials: on a site that caches its config, env() returns nothing,
    // and a built-in fallback would quietly make real guests pay a test store.
    private function storeId(): string
    {
        return (string) config('sslcommerz.apiCredentials.store_id');
    }

    private function storePassword(): string
    {
        return (string) config('sslcommerz.apiCredentials.store_password');
    }

    private function isSandbox(): bool
    {
        return (bool) config('sslcommerz.sandbox', true);
    }

    private function validationUrl(): string
    {
        return rtrim((string) config('sslcommerz.apiDomain'), '/') . config('sslcommerz.apiUrl.order_validate');
    }

    /** The gateway session for a new payment, talking to the test server or the live one as the settings say */
    protected function paymentGateway(): Payments
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return (new Payments(SSLCommSession::create([
            'store_id'       => $this->storeId(),
            'store_password' => $this->storePassword(),
            'success_url'    => $frontendUrl . '/success',
            'fail_url'       => $frontendUrl . '/failed',
            'cancel_url'     => $frontendUrl . '/cancelled',
            'currency'       => 'BDT',
        ])))->setMode($this->isSandbox() ? 'sandbox' : 'live');
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

        // A live site must never accept payments validated on the SSLCommerz test server (anyone can make those for free)
        if (app()->isProduction() && $this->isSandbox()) {
            Log::critical('SSLComm is in sandbox mode on a production site: payment callbacks are refused until services.sslcommerz.sandbox is false');
            return $this->sendError('The payment gateway is not ready.', [], 503);
        }

        // ── tran_id is either a booking_number or a group_ref ──
        $group = BookingGroup::where('group_ref', $tranId)->with('bookings')->first();

        if ($group) {
            $numbers  = $group->bookings->pluck('booking_number')->all();
            $expected = $this->groupTotal($group);
        } else {
            $booking = Booking::where('booking_number', $tranId)->with('room')->first();

            if (!$booking) {
                Log::error('SSLComm callback — booking not found', ['tran_id' => $tranId]);
                return $this->sendError('Booking not found.', [], 404);
            }

            $numbers  = [$booking->booking_number];
            $expected = (float) $booking->amount;
        }

        // ── Ask SSLCommerz, and check its answer is about THIS payment and THIS amount ──
        $validated = $this->validatePayment($valId, (string) $tranId, $expected, $numbers);

        if (!$validated) {
            return $this->sendError('Payment could not be verified.', [], 422);
        }

        return $group
            ? $this->confirmGroupPayment($group, $request, $validated)
            : $this->confirmSingleBooking($booking, $request, $validated);
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
     * Ask SSLCommerz's Validation API about a payment, and accept it only when its answer matches what we asked the
     * guest to pay. The callback itself can be sent by anyone, so a "VALID" status alone is not enough: a guest could
     * pay a small booking and reuse that payment's val_id to "confirm" a bigger one.
     *
     * Returns SSLCommerz's own answer when everything matches, otherwise null (and says why in the log).
     *
     * @param  float $expected       what the guest was asked to pay, in BDT
     * @param  int[] $bookingNumbers the bookings this payment is meant to cover
     * @return array<string,mixed>|null
     */
    private function validatePayment(string $valId, string $tranId, float $expected, array $bookingNumbers): ?array
    {
        $reject = function (string $why, array $extra = []) use ($valId, $tranId, $expected) {
            Log::critical('SSLComm payment REJECTED: ' . $why, ['tran_id' => $tranId, 'val_id' => $valId, 'expected' => $expected] + $extra);

            return null;
        };

        try {
            $response = Http::get($this->validationUrl(), [
                'val_id'       => $valId,
                'store_id'     => $this->storeId(),
                'store_passwd' => $this->storePassword(),
                'format'       => 'json',
            ]);
        } catch (\Exception $e) {
            return $reject('validation request failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return $reject('validation API answered HTTP ' . $response->status());
        }

        $data = $response->json();

        if (!is_array($data) || !in_array($data['status'] ?? null, ['VALID', 'VALIDATED'], true)) {
            return $reject('status is not VALID', ['answer' => $data]);
        }

        // 1. It must be the payment for this booking or group, not some other payment on the store
        if ((string) ($data['tran_id'] ?? '') !== $tranId) {
            return $reject('the payment belongs to another transaction', ['answer_tran_id' => $data['tran_id'] ?? null]);
        }

        // 2. In BDT, and for the full amount (a single paisa under is refused)
        $currency = strtoupper((string) ($data['currency_type'] ?? $data['currency'] ?? 'BDT'));
        if ($currency !== 'BDT') {
            return $reject('currency is not BDT', ['currency' => $currency]);
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || abs((float) $data['amount'] - $expected) > 0.001) {
            return $reject('the amount paid is not the amount due', ['paid' => $data['amount'] ?? null]);
        }

        // 3. The bank's own reference must exist, and must not already belong to a payment for different bookings
        $bank = (string) ($data['bank_tran_id'] ?? '');
        if ($bank === '') {
            return $reject('no bank transaction id in the answer');
        }

        $usedElsewhere = Transaction::query()
            ->where('transaction_reference', $bank)
            ->whereNotIn('booking_id', $bookingNumbers)
            ->exists();

        if ($usedElsewhere) {
            return $reject('this payment was already used for other bookings');
        }

        if ((string) ($data['risk_level'] ?? '0') === '1') {
            Log::warning('SSLComm says this payment is risky: it is held for a look in the money records', ['tran_id' => $tranId]);
        }

        return $data;
    }

    /** What a group of bookings costs: the bookings added up. If the group's own total disagrees, nothing is accepted. */
    private function groupTotal(BookingGroup $group): float
    {
        $sum = round((float) $group->bookings->sum('amount'), 2);

        if (abs($sum - (float) $group->total_amount) > 0.001) {
            Log::critical('Booking group total does not match its bookings', ['group_ref' => $group->group_ref, 'group_total' => $group->total_amount, 'sum' => $sum]);

            return -1.0;   // no payment can equal this
        }

        return $sum;
    }

    /** What is kept about a payment: the bank's own answer (amount, fee, card type, risk) next to the callback as it came in */
    private function paymentMeta(Request $request, array $validated): string
    {
        return json_encode(['callback' => $request->all(), 'validated' => $validated]);
    }
    /**
     * Confirm a single-room booking after validated payment.
     */
    private function confirmSingleBooking(Booking $booking, Request $request, array $validated): JsonResponse
    {
        if ($booking->payment_status === 'paid') {
            // Already processed — IPN can fire more than once
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

        // A bank callback that arrives again after the booking was refunded must not bring it back to life
        if ($booking->payment_status === 'refunded' || $booking->status === 'refunded') {
            return $this->sendSuccess(['message' => 'Already processed.']);
        }

        $alreadyDone = false;

        DB::transaction(function () use ($booking, $request, $validated, &$alreadyDone) {
            // The same payment can be reported twice at the very same moment (the guest's browser, the bank's server, the
            // 5-minute check). The second one waits here, then sees it is done: one payment record, one email.
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if (!$locked || in_array($locked->payment_status, ['paid', 'refunded'], true) || $locked->status === 'refunded') {
                $alreadyDone = true;

                return;
            }

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
                    'transaction_reference' => $validated['bank_tran_id'],
                    'status'                => 'completed',
                    'meta'                  => $this->paymentMeta($request, $validated),
                ]
            );
        });

        if ($alreadyDone) {
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

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

        // The guest's "booking confirmed" email (never allowed to fail the payment)
        BookingConfirmationMailer::send($booking);

        return $this->sendSuccess(['message' => 'Payment confirmed.']);
    }

    /**
     * Confirm all bookings inside a group after validated payment.
     */
    private function confirmGroupPayment(BookingGroup $group, Request $request, array $validated): JsonResponse
    {
        if ($group->payment_status === 'paid') {
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

        $alreadyDone = false;

        DB::transaction(function () use ($group, $request, $validated, &$alreadyDone) {
            // the same payment reported twice at the same moment: the second one waits here and finds it done
            $locked = BookingGroup::where('id', $group->id)->lockForUpdate()->first();

            if (!$locked || $locked->payment_status === 'paid') {
                $alreadyDone = true;

                return;
            }

            $group->load('bookings.room.property');

            foreach ($group->bookings as $booking) {
                if (in_array($booking->payment_status, ['paid', 'refunded'], true) || $booking->status === 'refunded') continue;

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
                        'transaction_reference' => $validated['bank_tran_id'],
                        'status'                => 'completed',
                        'meta'                  => $this->paymentMeta($request, $validated),
                    ]
                );
            }

            $group->update(['payment_status' => 'paid']);
        });

        if ($alreadyDone) {
            return $this->sendSuccess(['message' => 'Already confirmed.']);
        }

        $group->load('bookings.room.property.user');
        $firstBooking = $group->bookings->first()->load(['room', 'room.property', 'user', 'transaction']);

        // ONE grouped notification for admin — not one per room
        $this->notifyGroupPayment($group, $firstBooking);

        // One email for the whole group
        BookingConfirmationMailer::send($group);

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
        if ($group && $this->groupTotal($group->loadMissing('bookings')) < 0) {
            return $this->sendError('The price of this booking changed. Please start the booking again.', [], 422);
        }

        $amount    = $group ? $group->total_amount : $booking->amount;
        $tranId    = $group ? $group->group_ref : $booking->booking_number;

        // IMPORTANT: success/fail/cancel here are BROWSER redirect URLs
        // (where the guest's tab navigates to) — they point to the
        // Vue FRONTEND, not this API. The actual payment confirmation
        // happens via SSLComm's server-to-server IPN call to
        // /payment/success which is registered separately in routes
        // and is NOT the same as this success_url.
        $paymentSession = $this->paymentGateway();

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
