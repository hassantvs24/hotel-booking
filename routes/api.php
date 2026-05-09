<?php

//use App\Http\Controllers\API\Payment\SslCommerzPaymentController;
use App\Http\Controllers\API\Portal\Auth\LoginController;
use App\Http\Controllers\API\Portal\Auth\ProfileController;
use App\Http\Controllers\API\Portal\Auth\RegisterController;
use App\Http\Controllers\API\Portal\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [ProfileController::class, 'me'])->middleware('auth:sanctum');

/*----------------- Auth API -----------------*/
Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialiteController::class, 'redirectToProvider']);
    Route::get('/{provider}/callback', [SocialiteController::class, 'handleProviderCallback']);

    Route::post('register', [RegisterController::class, 'register']);
    Route::post('login', [LoginController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout']);

    Route::prefix('profile/update')->middleware(['auth:sanctum'])->group(function () {
        Route::post('personal-info', [ProfileController::class, 'updatePersonalInfo']);
        Route::post('preference', [ProfileController::class, 'updatePreferenceInfo']);
    });
});

/*----------------- Auth API -----------------*/

// SSLCOMMERZ Start
//Route::get('/checkout1', [SslCommerzPaymentController::class, 'exampleEasyCheckout']);
// Route::get('/checkout2', [SslCommerzPaymentController::class, 'exampleHostedCheckout']);

//Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
//Route::post('/pay-via-ajax', [SslCommerzPaymentController::class, 'payViaAjax']);

//Route::post('/success', [SslCommerzPaymentController::class, 'success']);
//Route::post('/fail', [SslCommerzPaymentController::class, 'fail']);
//Route::post('/cancel', [SslCommerzPaymentController::class, 'cancel']);

//Route::post('/ipn', [SslCommerzPaymentController::class, 'ipn']);
//Route::post('/ipn', [SslCommerzPaymentController::class, 'ipn']);
//SSLCOMMERZ END

