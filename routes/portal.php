<?php

/*----------------- Public Routes -----------------*/

use App\Http\Controllers\Portal\Booking\BookingController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\PropertyController;
use App\Http\Controllers\Portal\Request\SearchController;
use App\Http\Controllers\Portal\RoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->as('portal.')->group(function () {

    /*-- Home Routes --*/
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('room/details/{room}/{slug}', [RoomController::class, 'show'])
        ->name('room.details');
    Route::get('place/{place}/properties/{slug}', [PropertyController::class, 'place_property'])
        ->name('place.property.show');

    Route::get('/search', [SearchController::class, 'search'])
        ->name('property.search');

    Route::get('/property/{property}/{slug}', [PropertyController::class, 'property_Details'])
        ->name('property.details');

    Route::get('/properties', [PropertyController::class, 'all_properties'])
        ->name('properties.index');

    Route::get('/payment/{room}/{slug}', [BookingController::class, 'index'])->name('payment.index');

    Route::post('/booking/{room}', [BookingController::class, 'booking'])->name('booking.store');


    // Booking routes
    Route::post('/booking/{room}', [BookingController::class, 'booking'])->name('booking.store');
    Route::delete('/booking/cancel/{bookingId}', [BookingController::class, 'cancel'])->name('booking.cancel');

    /*-- property-add Routes --*/
    Route::get('/property-add', [HomeController::class, 'propertyAdd'])
        ->name('property-add');

    /*-- property Routes --*/
    Route::get('/property', [HomeController::class, 'property'])
        ->name('property');

    /*-- requested-waiting Routes --*/
    Route::get('/requested-waiting', [HomeController::class, 'requestedWaiting'])
        ->name('requested-waiting');

    /*-- single-hotel Routes --*/
    Route::get('/single-hotel', [HomeController::class, 'singleHotel'])
        ->name('single-hotel');
});
/*----------------- Public Routes -----------------*/