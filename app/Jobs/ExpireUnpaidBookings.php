<?php

namespace App\Jobs;

use App\Http\Controllers\API\Portal\LocalConfirmController;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Services\Refund\SslCommerzPaymentLookup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidBookings implements ShouldQueue
{
    use Dispatchable, Queueable;

    private const TIMEOUT_MINUTES = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subMinutes(self::TIMEOUT_MINUTES);

        $expired = Booking::where('status', 'reserved')
            ->whereIn('payment_status', ['pending', 'failed'])   // never a paid or refunded booking
            ->where('created_at', '<', $cutoff)
            ->with('room')
            ->get();

        foreach ($expired as $booking) {
            // Look at it as it is now: confirming one booking of a cart confirms the others, and a callback may have just arrived
            $booking = $booking->fresh(['room']);

            if (!$booking || $booking->status !== 'reserved' || !in_array($booking->payment_status, ['pending', 'failed'], true)) {
                continue;
            }

            // The guest may have paid and then never come back to the site (closed the tab, lost signal). Never cancel a
            // booking that was really paid: ask SSLCommerz first, and confirm it instead.
            if ($this->wasPaidAfterAll($booking)) {
                continue;
            }

            $released = DB::transaction(function () use ($booking) {
                // a payment that arrives at this very moment wins: only a booking still unpaid is cancelled
                $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

                if (!$locked || $locked->status !== 'reserved' || !in_array($locked->payment_status, ['pending', 'failed'], true)) {
                    return;
                }

                $booking->update([
                    'status'         => 'cancelled',
                    'payment_status' => 'cancelled',   // the column has no "expired" value; this is what a cancelled booking gets everywhere else
                ]);

                // Only release the room if no OTHER active booking holds it
                $stillHeld = Booking::where('room_id', $booking->room_id)
                    ->whereIn('status', ['reserved', 'approved'])
                    ->where('id', '!=', $booking->id)
                    ->exists();

                if (!$stillHeld && $booking->room) {
                    $booking->room->update(['status' => 'Available']);
                }

                return true;
            });

            if (!$released) {
                continue;   // it was paid at this very moment: nothing was cancelled
            }

            Log::info('Expired unpaid booking released', [
                'booking_number' => $booking->booking_number,
                'room_id'        => $booking->room_id,
            ]);

            // the guest, the hotel and the admin hear of it (only a bell: it can never undo or stop the clean-up)
            app(\App\Services\Notify\BookingAlerts::class)->released($booking, self::TIMEOUT_MINUTES);
        }
    }

    /**
     * True when the booking is not to be cancelled now: SSLCommerz says it was paid (and it is confirmed here), or
     * SSLCommerz could not be asked (try again at the next run; after a few hours it is let go).
     */
    private function wasPaidAfterAll(Booking $booking): bool
    {
        $reference = $booking->booking_group_id
            ? BookingGroup::where('id', $booking->booking_group_id)->value('group_ref')
            : $booking->booking_number;

        if (!$reference) {
            return false;
        }

        $lookup = app(SslCommerzPaymentLookup::class);
        $paid   = $lookup->findPaid((string) $reference);

        if (!$paid) {
            return $lookup->failed && $booking->created_at->gt(Carbon::now()->subHours(3));
        }

        $confirmed = app(LocalConfirmController::class)->confirmRealPayment((string) $reference, Request::create('/expire', 'POST'));

        if (!$confirmed) {
            Log::critical('Guest paid on SSLCommerz but the booking could not be confirmed (amount differs?) - check by hand before it is cancelled', [
                'booking_number' => $booking->booking_number, 'reference' => $reference, 'paid' => $paid['amount'],
            ]);
        }

        return (bool) $confirmed;
    }
}
