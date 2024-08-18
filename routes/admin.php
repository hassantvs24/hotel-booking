<?php

use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\Booking\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Facility\FacilityController;
use App\Http\Controllers\Admin\Facility\SubFacilityController;
use App\Http\Controllers\Admin\Place\CityController;
use App\Http\Controllers\Admin\Place\CountryController;
use App\Http\Controllers\Admin\Place\PlaceController;
use App\Http\Controllers\Admin\Place\StateController;
use App\Http\Controllers\Admin\Property\PropertyCategoryController;
use App\Http\Controllers\Admin\Property\PropertyController;
use App\Http\Controllers\Admin\Property\PropertyRuleController;
use App\Http\Controllers\Admin\Review\ReviewCategoryController;
use App\Http\Controllers\Admin\Review\ReviewController;
use App\Http\Controllers\Admin\Room\BedTypeController;
use App\Http\Controllers\Admin\Room\PriceTypeController;
use App\Http\Controllers\Admin\Room\RoomController;
use App\Http\Controllers\Admin\Room\RoomTypeController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\Surrounding\SurroundingController;
use App\Http\Controllers\Admin\Surrounding\SurroundingPlaceController;
use Illuminate\Support\Facades\Route;

/*----------------- Admin Routes -----------------*/

Route::prefix('admin')->as('admin.')->middleware(['auth'])->group(function () {

    /*-- Dashboard Routes --*/
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*-- ACL Routes --*/
    Route::prefix('acl')->as('acl.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
    });

    /*-- Setting Routes --*/
    Route::resource('settings', SettingController::class);

    /*-- Place Routes --*/
    Route::prefix('places')->as('places.')->group(function () {

        Route::controller(PlaceController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{place}/edit', 'edit')->name('edit');
            Route::put('{place}', 'update')->name('update');
            Route::delete('destroy/{place}', 'destroy')->name('destroy');
        });

        Route::resource('cities', CityController::class);
        Route::resource('states', StateController::class);
        Route::resource('countries', CountryController::class);
    });

    /*-- Facilities Routes --*/
    Route::prefix('facilities')->as('facilities.')->group(function () {

        Route::controller(FacilityController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{facility}/edit', 'edit')->name('edit');
            Route::put('{facility}', 'update')->name('update');
            Route::delete('destroy/{facility}', 'destroy')->name('destroy');
        });

        Route::resource('sub-facilities', SubFacilityController::class)
            ->parameters(['sub-facilities' => 'subFacility']);
    });

    /*-- Surrounding Routes --*/
    Route::prefix('surroundings')->as('surroundings.')->group(function () {
        Route::controller(SurroundingController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{surrounding}/edit', 'edit')->name('edit');
            Route::put('{surrounding}', 'update')->name('update');
            Route::delete('destroy/{surrounding}', 'destroy')->name('destroy');
        });

        Route::resource('surrounding-places', SurroundingPlaceController::class)
            ->parameters(['surrounding-places' => 'surroundingPlace']);
    });

    /*-- Property Routes --*/
    Route::prefix('properties')->as('properties.')->group(function () {
        Route::resource('categories', PropertyCategoryController::class)
            ->parameters(['categories' => 'propertyCategory']);
        Route::resource('rules', PropertyRuleController::class)
            ->parameters(['rules' => 'propertyRule']);

        Route::controller(PropertyController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{property}/edit', 'edit')->name('edit');
            Route::put('{property}', 'update')->name('update');
            Route::delete('destroy/{property}', 'destroy')->name('destroy');
        });
    });

    /*-- Room Routes --*/
    Route::prefix('rooms')->as('rooms.')->group(function () {

        Route::resource('room-types', RoomTypeController::class)
            ->parameters(['room-types' => 'roomType']);
        Route::resource('bed-types', BedTypeController::class)
            ->parameters(['bed-types' => 'bedType']);
        Route::resource('price-types', PriceTypeController::class)
            ->parameters(['price-types' => 'priceType']);

        Route::controller(RoomController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{room}/edit', 'edit')->name('edit');
            Route::put('{room}', 'update')->name('update');
            Route::delete('destroy/{room}', 'destroy')->name('destroy');
        });
    });

    /*-- Booking Routes --*/
    Route::prefix('bookings')->as('bookings.')->group(function () {

        Route::controller(BookingController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{booking}/edit', 'edit')->name('edit');
            Route::put('{booking}', 'update')->name('update');
            Route::delete('destroy/{booking}', 'destroy')->name('destroy');
        });
    });
    
    
    /*-- Review Routes --*/
    Route::prefix('reviews')->as('reviews.')->group(function () {
        Route::resource('categories', ReviewCategoryController::class)
            ->parameters(['categories' => 'reviewCategory']);
        Route::controller(ReviewController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('', 'store')->name('store');
            Route::get('{review}/edit', 'edit')->name('edit');
            Route::put('{review}', 'update')->name('update');
            Route::delete('destroy/{review}', 'destroy')->name('destroy');
        });
    });
});
/*----------------- Admin Routes -----------------*/
