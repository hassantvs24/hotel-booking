<?php

use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Place\CountryController;
use App\Http\Controllers\Admin\Place\CityController;
use App\Http\Controllers\Admin\Place\PlaceController;
use App\Http\Controllers\Admin\Place\StateController;
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
            Route::get('{id}/edit', 'edit')->name('edit');
            Route::put('{id}', 'update')->name('update');
            Route::delete('destroy/{id}', 'destroy')->name('destroy');
        });

        Route::resource('cities', CityController::class);
        Route::resource('states', StateController::class);
        Route::resource('countries', CountryController::class);
    });
});
/*----------------- Admin Routes -----------------*/
