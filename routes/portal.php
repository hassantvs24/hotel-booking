<?php

/*----------------- Public Routes -----------------*/

use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\HotelController;
use App\Http\Controllers\Portal\PropertyController;
use App\Http\Controllers\Portal\RoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->as('portal.')->group(function () {

    /*-- Home Routes --*/
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('room/details/{room}/{slug}', [RoomController::class, 'show'])
        ->name('room.details');


    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/hotels', [HotelController::class, 'index'])->name('hotels.index');

    /*-- Payment Routes --*/
    Route::get('/payment', [HomeController::class, 'payment'])
        ->name('payment');

    /*-- property-add Routes --*/
    Route::get('/property-add', [HomeController::class, 'propertyAdd'])
        ->name('property-add');

    /*-- property Routes --*/
    Route::get('/property', [HomeController::class, 'property'])
        ->name('property');

    /*-- requested-waiting Routes --*/
    Route::get('/requested-waiting', [HomeController::class, 'requestedWaiting'])
        ->name('requested-waiting');

    /*-- requested Routes --*/
    Route::get('/requested', [HomeController::class, 'requested'])
        ->name('requested');


    /*-- single-hotel-non-requested Routes --*/
    Route::get('/single-hotel-non-requested', [HomeController::class, 'singleHotelnonRequested'])
        ->name('single-hotel-non-requested');


    /*-- single-hotel Routes --*/
    Route::get('/single-hotel', [HomeController::class, 'singleHotel'])
        ->name('single-hotel');
});
/*----------------- Public Routes -----------------*/