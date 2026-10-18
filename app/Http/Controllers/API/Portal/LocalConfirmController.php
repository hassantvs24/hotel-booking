<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\RoomRequest;
use App\Models\Transaction;
use App\Notifications\Booking\PaymentConfirmedNotification;
use App\Services\Booking\BookingConfirmationMailer;
use App\Services\Refund\SslCommerzPaymentLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalConfirmController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  LOCAL DEV ONLY — manually confirm payment
    //  POST /portal/booking/confirm-local
    //
    //  In production, SSLCommerz's IPN handles this automatically.
    //  In local dev, SSLCommerz can't reach localhost, so we
    //  manually trigger the same logic the IPN would run.
    //
    //  IMPORTANT: Remove or disable this route before going live.
    // ══════════════════════════════════════════════════════

    /**
     * The success page asks this once the guest is back from the bank. It works on a LIVE site too: it never believes the
     * browser, it asks SSLCommerz whether this payment really was made (and for how much) and confirms only then.
     * POST /portal/booking/confirm-payment
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $request->validate(['tran_id' => 'required|string|max:64']);

        $tranId  = (string) $request->input('tran_id');
        $group   = BookingGroup::where('group_ref', $tranId)->first();
        $booking = $group ? null : Booking::where('booking_number', $tranId)->first();
        $subject = $group ?? $booking;

        if (!$subject) {
            return $this->sendError('Booking not found.', [], 404);
        }

        if ($subject->payment_status === 'paid') {
            return $this->sendSuccess(['status' => 'paid'], 'Payment confirmed.');
        }

        if ($real = $this->confirmRealPayment($tranId, $request)) {
            return $real;
        }

        // not paid (yet): the page waits a little and asks again; a payment SSLCommerz knows of is also picked up within 5 minutes
        return $this->sendError('We have not received the bank\'s confirmation yet.', ['pending' => true], 409);
    }

    public function confirm(Request $request): JsonResponse
    {
        // Only allow in local/development environment
        if (!app()->isLocal()) {
            return $this->sendError('Not available in production.', [], 403);
        }

        $request->validate(['tran_id' => 'required|string']);

        $tranId = $request->tran_id;

        Log::info('[LocalConfirm] Manually confirming payment', ['tran_id' => $tranId]);

        // If the guest really paid on the SSLCommerz sandbox page, take that real payment — it carries the
        // bank's own reference, which a refund needs — instead of the made-up LOCAL-TEST one below.
        if ($real = $this->confirmRealPayment($tranId, $request)) {
            return $real;
        }

        // Check if it's a group ref or booking number
        $group = BookingGroup::where('group_ref', $tranId)->first();

        if ($group) {
            return $this->confirmGroup($group, $request->user());
        }

        $booking = Booking::where('booking_number', $tranId)
            ->with('room')
            ->first();

        if (!$booking) {
            return $this->sendError('Booking not found for tran_id: ' . $tranId, [], 404);
        }

        return $this->confirmSingle($booking, $request->user());
    }

    /**
     * A booking/group that was paid for on SSLCommerz: ask the gateway, verify it, and confirm it exactly as the
     * real callback (IPN) would. Returns null when there is no real payment, so the dev shortcut carries on.
     */
    public function confirmRealPayment(string $tranId, Request $request): ?JsonResponse
    {
        $group   = BookingGroup::where('group_ref', $tranId)->first();
        $booking = $group ? null : Booking::where('booking_number', $tranId)->first();
        $subject = $group ?? $booking;

        if (!$subject || $subject->payment_status === 'paid') {
            return null;
        }

        $paid = app(SslCommerzPaymentLookup::class)->findPaid($tranId);

        if (!$paid) {
            return null;
        }

        $expected = (float) ($group ? $group->total_amount : $booking->amount);

        if (abs($paid['amount'] - $expected) > 1) {
            Log::warning('[LocalConfirm] SSLCommerz payment amount does not match the booking', [
                'tran_id' => $tranId, 'paid' => $paid['amount'], 'expected' => $expected,
            ]);

            return null;
        }

        $response = app(PaymentController::class)->paymentSuccess($request->merge([
            'tran_id'      => $tranId,
            'val_id'       => $paid['val_id'],
            'bank_tran_id' => $paid['bank_tran_id'],
            'amount'       => $paid['amount'],
        ]));

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        Log::info('[LocalConfirm] Confirmed with the real SSLCommerz payment', [
            'tran_id' => $tranId, 'bank_tran_id' => $paid['bank_tran_id'],
        ]);

        return $group
            ? $this->sendSuccess(['message' => 'Group payment confirmed.', 'group_ref' => $group->group_ref, 'status' => 'paid'])
            : $this->sendSuccess(['message' => 'Booking confirmed.', 'booking_number' => $booking->booking_number, 'status' => 'paid']);
    }

    private function confirmGroup(BookingGroup $group, $user): JsonResponse
    {
        if ($group->payment_status === 'paid') {
            return $this->sendSuccess(['message' => 'Already confirmed.', 'status' => 'paid']);
        }

        DB::transaction(function () use ($group) {
            $group->load('bookings.room.property');

            foreach ($group->bookings as $booking) {
                if (in_array($booking->payment_status, ['paid', 'refunded'], true) || $booking->status === 'refunded') continue;

                $booking->update([
                    'status'         => 'approved',
                    'payment_status' => 'paid',
                ]);

                if ($booking->room) {
                    $booking->room->update(['status' => 'Booked']);
                }

                // Try to create transaction — skip if table structure differs
                try {
                    Transaction::create([
                        'booking_id'            => $booking->booking_number,
                        'user_id'               => $booking->user_id,
                        'amount'                => $booking->amount,
                        'payment_method'        => 'SSLComm',
                        'transaction_reference' => 'LOCAL-TEST-' . now()->timestamp,
                        'status'                => 'completed',
                    ]);
                } catch (\Exception $te) {
                    Log::warning('Transaction create skipped: ' . $te->getMessage());
                }

                try {
                    $owner = $booking->room?->property?->user ?? null;
                    $owner?->notify(new PaymentConfirmedNotification($booking, 'owner'));
                } catch (\Exception $ne) {
                    Log::warning('Owner notify skipped: ' . $ne->getMessage());
                }
            }

            $group->update(['payment_status' => 'paid']);

            try {
                $firstBooking = $group->bookings->first();
                $firstBooking?->user?->notify(new PaymentConfirmedNotification($firstBooking, 'guest'));
            } catch (\Exception $ne) {
                Log::warning('Guest notify skipped: ' . $ne->getMessage());
            }
        });

        // One email for the whole group, same as the real payment path
        BookingConfirmationMailer::send($group->fresh());

        return $this->sendSuccess([
            'message'   => 'Group payment confirmed.',
            'group_ref' => $group->group_ref,
            'status'    => 'paid',
        ]);
    }

    private function confirmSingle(Booking $booking, $user): JsonResponse
    {
        if ($booking->payment_status === 'paid') {
            return $this->sendSuccess(['message' => 'Already confirmed.', 'status' => 'paid']);
        }

        if ($booking->payment_status === 'refunded' || $booking->status === 'refunded') {
            return $this->sendSuccess(['message' => 'Already processed.', 'status' => 'refunded']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status'         => 'approved',
                'payment_status' => 'paid',
            ]);

            if ($booking->room) {
                $booking->room->update(['status' => 'Booked']);
            }

            // Same as the real IPN path: a paid bid booking completes its bid
            if ($booking->room_request_id) {
                RoomRequest::where('id', $booking->room_request_id)->update(['status' => 'Done']);
            }

            try {
                Transaction::create([
                    'booking_id'            => $booking->booking_number,
                    'user_id'               => $booking->user_id,
                    'amount'                => $booking->amount,
                    'payment_method'        => 'SSLComm',
                    'transaction_reference' => 'LOCAL-TEST-' . now()->timestamp,
                    'status'                => 'completed',
                ]);
            } catch (\Exception $te) {
                Log::warning('Transaction create skipped: ' . $te->getMessage());
            }

            try {
                $booking->user?->notify(new PaymentConfirmedNotification($booking, 'guest'));
                $owner = $booking->room?->property?->user ?? null;
                $owner?->notify(new PaymentConfirmedNotification($booking, 'owner'));
            } catch (\Exception $ne) {
                Log::warning('Notify skipped: ' . $ne->getMessage());
            }
        });

        // Same email the real payment path sends
        BookingConfirmationMailer::send($booking->fresh());

        return $this->sendSuccess([
            'message'        => 'Booking confirmed.',
            'booking_number' => $booking->booking_number,
            'status'         => 'paid',
        ]);
    }
}
