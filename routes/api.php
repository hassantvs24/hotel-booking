<?php

use App\Http\Controllers\API\Portal\Auth\LoginController;
use App\Http\Controllers\API\Portal\Auth\RegisterController;
use App\Http\Controllers\API\Portal\Auth\SocialiteController;
use App\Http\Controllers\API\Portal\BookingController;
use App\Http\Controllers\API\Portal\HomeController;
use App\Http\Controllers\API\Portal\PropertyController;
use App\Http\Controllers\API\Portal\RequestController;
use App\Http\Controllers\API\Portal\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*----------------- Portal API -----------------*/
Route::prefix('portal')->group(function () {
    Route::get('home', [HomeController::class, 'index']);
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{place}/place', [PropertyController::class, 'placeWiseProperties']);
        Route::get('/{property}/details', [PropertyController::class, 'details']);
        Route::get('/{property}/available-rooms', [PropertyController::class, 'availableRooms']);
    });

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
    });

    Route::get('/room/{room}/payment', [BookingController::class, 'paymentDetails']);
    Route::post('/booking', [BookingController::class, 'booking']);

    Route::prefix('request')->group(function () {
        Route::post("/", [RequestController::class, 'index']);
        Route::get("/properties", [RequestController::class, 'property_list']);
    });
});


/*----------------- Auth API -----------------*/
Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialiteController::class, 'redirectToProvider']);
    Route::get('/{provider}/callback', [SocialiteController::class, 'handleProviderCallback']);

    Route::post('register', [RegisterController::class, 'register']);
    Route::post('login', [LoginController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout']);
});
/*----------------- Auth API -----------------*/
