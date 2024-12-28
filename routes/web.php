<?php

use App\Http\Controllers\API\Payment\SslCommerzPaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// test mail

Route::get('test-mail', function () {
    \Illuminate\Support\Facades\Mail::to('example@gmail.com')->send(new \App\Mail\TestMail());
    return 'Mail sent';
});

Route::get('/', static fn() => response('', 200));

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
// SSLCOMMERZ Start
Route::get('/checkout1', [SslCommerzPaymentController::class, 'exampleEasyCheckout']);
// Route::get('/checkout2', [SslCommerzPaymentController::class, 'exampleHostedCheckout']);

Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
Route::post('/pay-via-ajax', [SslCommerzPaymentController::class, 'payViaAjax']);

Route::post('/success', [SslCommerzPaymentController::class, 'success']);
Route::post('/fail', [SslCommerzPaymentController::class, 'fail']);
Route::post('/cancel', [SslCommerzPaymentController::class, 'cancel']);

Route::post('/ipn', [SslCommerzPaymentController::class, 'ipn']);
//SSLCOMMERZ END

Route::prefix('payments')->name('payment.')->group(function () {

    Route::post('/success', function (\Illuminate\Http\Request $request) {

        $originalResponse = $request->all();

        $payments = new \App\Abstract\Payouts\SSLComm\Payments(\App\Abstract\Payouts\SSLComm\SSLCommSession::create([
            'store_id'       => 'hotel674dd7e831e76',
            'store_password' => 'hotel674dd7e831e76@ssl',
            'success_url'    => route('payment.success'),
            'fail_url'       => route('payment.fail'),
            'cancel_url'     => route('payment.cancel'),
            'currency'       => 'BDT',
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

        return [
            'original_response'   => $originalResponse,
            'validation_response' => $validated,
            'validation_id'       => \Illuminate\Support\Arr::get($originalResponse, 'val_id'),
        ];

    })->name('success');

    Route::post('/fail', function (\Illuminate\Http\Request $request) {
        return $request->all();
    })->name('fail');

    Route::post('/cancel', function (\Illuminate\Http\Request $request) {
        return $request->all();
    })->name('cancel');
});
