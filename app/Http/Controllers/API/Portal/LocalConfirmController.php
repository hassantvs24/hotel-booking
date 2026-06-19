<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\Transaction;
use App\Notifications\Booking\PaymentConfirmedNotification;
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

    public function confirm(Request $request): JsonResponse
    {
        // Only allow in local/development environment
        if (!app()->isLocal()) {
            return $this->sendError('Not available in production.', [], 403);
        }

        $request->validate(['tran_id' => 'required|string']);

        $tranId = $request->tran_id;

        Log::info('[LocalConfirm] Manually confirming payment', ['tran_id' => $tranId]);

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

    private function confirmGroup(BookingGroup $group, $user): JsonResponse
    {
        if ($group->payment_status === 'paid') {
            return $this->sendSuccess(['message' => 'Already confirmed.', 'status' => 'paid']);
        }

        DB::transaction(function () use ($group) {
            $group->load('bookings.room.property');

            foreach ($group->bookings as $booking) {
                if ($booking->payment_status === 'paid') continue;

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

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status'         => 'approved',
                'payment_status' => 'paid',
            ]);

            if ($booking->room) {
                $booking->room->update(['status' => 'Booked']);
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

        return $this->sendSuccess([
            'message'        => 'Booking confirmed.',
            'booking_number' => $booking->booking_number,
            'status'         => 'paid',
        ]);
    }
}
