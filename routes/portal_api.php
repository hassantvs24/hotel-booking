<?php

/*------------------------------------------
| Portal API Routes
------------------------------------------*/

use App\Http\Controllers\API\Portal\Booking\CartController;
use App\Http\Controllers\API\Portal\BookingController;
//use App\Http\Controllers\API\Portal\CartController;
use App\Http\Controllers\API\Portal\Property\FilterController;
use App\Http\Controllers\API\Portal\Property\SearchController;

use App\Http\Controllers\API\Portal\HomeController;
use App\Http\Controllers\API\Portal\PropertyController;
use App\Http\Controllers\API\Portal\RequestController;
use App\Http\Controllers\API\Portal\RoomRequestController;
use App\Http\Controllers\API\Portal\Vendor\PropertyRequestController;
use App\Http\Controllers\API\Portal\Vendor\VendorController;
use Illuminate\Support\Facades\Route;

/*----------------- Portal API -----------------*/

Route::prefix('portal')->group(function () {

    Route::get('home', [HomeController::class, 'index']);
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{place}/place', [PropertyController::class, 'placeWiseProperties']);
        Route::get('/{property}/details', [PropertyController::class, 'details']);
        Route::get('/{property}/available-rooms', [PropertyController::class, 'availableRooms']);
        Route::get('/{property}/other-rooms', [PropertyController::class, 'otherRooms']);
        Route::get('/{property}/booking-request-check', [PropertyController::class, 'bookingRoomCheck']);
        Route::get('/{property}/booking-date-check', [PropertyController::class, 'checkBookedDate']);
    });

    Route::prefix('search')->group(function () {
        Route::get('/',[SearchController::class, 'search']);
    });

    Route::prefix('filter')->group(function () {
        Route::get('/', [FilterController::class, 'getFilters']);
        Route::get('/search-properties', [FilterController::class, 'getFilteredProperties']);
        Route::get('/request-properties', [FilterController::class, 'getFilteredPropertiesByRequest'])->middleware('auth:sanctum');
    });

    Route::get('/room/{room}/payment', [BookingController::class, 'paymentDetails']);

    Route::prefix('booking')->middleware('auth:sanctum')->group(function () {
        Route::post('/', [BookingController::class, 'bookingStore']);
        Route::post('/pay-now', [BookingController::class, 'bookNow']);
        Route::get('/check/{room}/room', [BookingController::class, 'bookingCheck']);
        //Route::get('/cart-list', [BookingController::class, 'cartList']);
        //Route::get('/details', [BookingController::class, 'bookingDetails']);
        Route::post('/retry-payment', [BookingController::class, 'tryToPayAgain']);

        // ── New routes ──
        Route::post('/store', [BookingController::class, 'bookingStore']);
        Route::post('/checkout', [BookingController::class, 'checkout']);
        Route::post('/pay-group', [BookingController::class, 'payGroup']);
        Route::get('/my-bookings', [BookingController::class, 'myBookings']);

        Route::get('/details', [BookingController::class, 'bookingDetails']);
        Route::post('/retry-payment', [BookingController::class, 'tryToPayAgain']);
    });

    // ── Cart routes
    Route::prefix('cart')
        ->middleware('auth:sanctum')
        ->controller(CartController::class)
        ->group(function () {
        Route::get('/', 'index');
        Route::post('/add', 'store');
        Route::delete('/{id}', 'delete');
        Route::delete('/', 'clearCart');
    });

    /*----------------- property request -----------------*/
    Route::prefix('property-request')->group(function () {
        Route::post('/store', [PropertyRequestController::class, 'store']);
    });

    // room request
    Route::prefix('room')->middleware('auth:sanctum')->group(function () {
        Route::post('/request', [RoomRequestController::class, 'roomRequest']);
        Route::get('/request-notification', [RoomRequestController::class, 'roomRequestNotification']);
        Route::delete('request-notification-remove/{propertyId}', [RoomRequestController::class, 'removeNotification']);
        Route::get('/request-response/{propertyId}', [RoomRequestController::class, 'roomResponselist']);
    });

    Route::prefix('request')->middleware('auth:sanctum')->group(function () {
        Route::post("/", [RequestController::class, 'index']);
        Route::get("/properties", [RequestController::class, 'property_list']);
        Route::get('/fetchtimer', [RequestController::class, 'fetchTimer']);
    });

    // ── Room request bid routes (new) ──
    Route::prefix('room-request')->middleware('auth:sanctum')->group(function () {
        Route::post('/store', [RoomRequestController::class, 'store']);
        Route::get('/bid-count/{roomId}', [RoomRequestController::class, 'bidCount']);
        Route::get('/my-bids', [RoomRequestController::class, 'myBids']);
        Route::get('/incoming', [RoomRequestController::class, 'incoming']);
        Route::post('/{id}/accept', [RoomRequestController::class, 'accept']);
        Route::post('/{id}/counter', [RoomRequestController::class, 'counter']);
        Route::post('/{id}/accept-counter', [RoomRequestController::class, 'acceptCounter']);
        Route::post('/{id}/decline', [RoomRequestController::class, 'decline']);
        Route::post('/{id}/pay', [RoomRequestController::class, 'payBid']);
        Route::delete('/notification/{propertyId}', [RoomRequestController::class, 'removeNotification']);
    });

    // Location Routes
    Route::prefix('location')->group(function () {
        Route::get('/suggestions', [\App\Http\Controllers\API\Portal\Location\LocationController::class, 'suggestions']);
        Route::post('/resolve-place', [\App\Http\Controllers\API\Portal\Location\LocationController::class, 'resolvePlace']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/getAllPropertyOption', [VendorController::class, 'allPropertyOption']);
        Route::post('/property/store', [VendorController::class, 'store']);
        Route::get('/property/request-properties', [VendorController::class, 'requestProperties']);
        Route::get('/reg-info',[VendorController::class, 'getRegInfo']);
    });
});

/*----------------- Payment Callbacks (public — no auth) -----------------*/
Route::post('/payment/success', [BookingController::class, 'paymentSuccess'])->name('payment.success');
Route::post('/payment/fail',    [BookingController::class, 'paymentFail'])->name('payment.fail');
Route::post('/payment/cancel',  [BookingController::class, 'paymentCancel'])->name('payment.cancel');
/*----------------- Portal API -----------------*/
