<?php

use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Facility\FacilityController;
use App\Http\Controllers\Admin\Facility\SubFacilityController;
use App\Http\Controllers\Admin\Place\CountryController;
use App\Http\Controllers\Admin\Place\CityController;
use App\Http\Controllers\Admin\Place\PlaceController;
use App\Http\Controllers\Admin\Place\StateController;
use App\Http\Controllers\Admin\Property\PropertyCategoryController;
use App\Http\Controllers\Admin\Property\PropertyRuleController;
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

        Route::resource('sub-facilities',
            SubFacilityController::class)->parameters(['sub-facilities' => 'subFacility']);
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
    });
});
/*----------------- Admin Routes -----------------*/
