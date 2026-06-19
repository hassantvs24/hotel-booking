<?php

namespace App\Jobs;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
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
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<', $cutoff)
            ->with('room')
            ->get();

        foreach ($expired as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status'         => 'cancelled',
                    'payment_status' => 'expired',
                ]);

                // Only release the room if no OTHER active booking holds it
                $stillHeld = Booking::where('room_id', $booking->room_id)
                    ->whereIn('status', ['reserved', 'approved'])
                    ->where('id', '!=', $booking->id)
                    ->exists();

                if (!$stillHeld && $booking->room) {
                    $booking->room->update(['status' => 'Available']);
                }
            });

            Log::info('Expired unpaid booking released', [
                'booking_number' => $booking->booking_number,
                'room_id'        => $booking->room_id,
            ]);
        }
    }
}
