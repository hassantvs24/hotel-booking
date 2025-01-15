<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PaymentController extends BaseController
{
    public function success(Request $request)
    {
        $originalResponse = $request->all();

        $payments = new \App\Abstract\Payouts\SSLComm\Payments(\App\Abstract\Payouts\SSLComm\SSLCommSession::create([
            'store_id' => 'hotel674dd7e831e76',
            'store_password' => 'hotel674dd7e831e76@ssl',
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            'currency' => 'BDT',
        ]));

        $validated = $payments->validate(\Illuminate\Support\Arr::get($originalResponse, 'val_id'));

        $dataToStore = \Illuminate\Support\Arr::only($validated, [
            'val_id',
            'status',
            'amount',
            'bank_tran_id',
            'card_no',
            'card_ref_id',
            'risk_title',
            'tran_date'
        ]);

        $booking = Booking::where('booking_number', $validated['tran_id'])->first();
        if ($booking) {
            $transaction = $booking->transaction()->updateOrCreate(
                [
                    'booking_id' => $booking->booking_number,
                    'user_id' => $booking->user_id,
                ],
                [
                    'booking_id' => $booking->booking_number,
                    'user_id' => $booking->user_id,
                    'amount' => $booking->amount,
                    'meta' => serialize($validated),
                    'payment_method' => $validated['card_type'],
                    'transaction_reference' => $validated['val_id'],
                    'status' => 'completed',
                    'notes' => null,
                ]
            );

            $updateBooking = Booking::where('booking_number', $transaction->booking_id)->first();
            $updateBooking->update([
                'status' => 'approved',
                'payment_status' => 'paid'
            ]);
        }
        $redirectUrl = config('app.frontend_url') . '/success?ref=' . $originalResponse['tran_id'];

        return Redirect::away($redirectUrl);
    }

    public function fail(Request $request)
    {
        $originalResponse = $request->all();

        $booking = Booking::where('booking_number', $originalResponse['tran_id'])->first();

        if ($booking) {
            $booking->update([
                'status' => 'pending',
                'payment_status' => 'failed'
            ]);
        }

        $redirectUrl = config('app.frontend_url') . '/failed?ref=' . $originalResponse['tran_id'];
        return Redirect::away($redirectUrl);
    }

    public function cancel(Request $request)
    {
        $originalResponse = $request->all();

        $booking = Booking::where('booking_number', $originalResponse['tran_id'])->first();

        if ($booking) {
            $booking->update([
                'status' => 'pending',
                'payment_status' => 'failed'
            ]);
        }

        $redirectUrl = config('app.frontend_url') . '/failed?ref=' . $originalResponse['tran_id'];
        return Redirect::away($redirectUrl);
    }
}
