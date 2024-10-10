<?php

/*------------------------------------------
| Portal API Routes
------------------------------------------*/

use App\Http\Controllers\API\Portal\BookingController;
use App\Http\Controllers\API\Portal\HomeController;
use App\Http\Controllers\API\Portal\PropertyController;
use App\Http\Controllers\API\Portal\RequestController;
use App\Http\Controllers\API\Portal\RoomRequestController;
use App\Http\Controllers\API\Portal\SearchController;
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
        Route::get('/{property}/booking-request-check', [PropertyController::class, 'bookingRoomCheck']);
    });

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
    });

    Route::get('/room/{room}/payment', [BookingController::class, 'paymentDetails']);
    Route::post('/booking', [BookingController::class, 'booking']);

    // room request
    Route::prefix('room')->middleware('auth:sanctum')->group(function () {
        Route::post('/request', [RoomRequestController::class, 'roomRequest']);
        Route::get('/request-notification', [RoomRequestController::class, 'roomRequestNotification']);
        Route::delete('request-notification-remove/{propertyId}',[RoomRequestController::class, 'removeNotification']);
        Route::get('/request-response/{propertyId}', [RoomRequestController::class, 'roomResponselist']);
    });

    Route::prefix('request')->middleware('auth:sanctum')->group(function () {
        Route::post("/", [RequestController::class, 'index']);
        Route::get("/properties", [RequestController::class, 'property_list']);
        Route::get('/fetchtimer',[RequestController::class, 'fetchTimer']);
    });

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/getAllPropertyOption', [VendorController::class, 'allPropertyOption']);
        Route::post('/property/store', [VendorController::class, 'store']);
        Route::get('/property/allproperties', [VendorController::class, 'allproperties']);
    });
});
/*----------------- Portal API -----------------*/
