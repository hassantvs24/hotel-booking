<?php

use App\Http\Controllers\Portal\HomeController;

use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*----------------- Public Routes -----------------*/
Route::prefix('')->as('portal.')->group(function () {

    /*-- Home Routes --*/
    Route::get('/', [HomeController::class, 'index'])
        ->name('home');

    /*-- Non-requested Routes --*/
    Route::get('/non-requested', [HomeController::class, 'nonRequested'])
        ->name('non-requested');

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


/*----------------- Admin Routes -----------------*/

Route::prefix('admin')->as('admin.')->group(function () {

    /*-- Dashboard Routes --*/
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*-- ACL Routes --*/
    Route::prefix('acl')->as('acl.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
    });

});
/*----------------- Admin Routes -----------------*/
