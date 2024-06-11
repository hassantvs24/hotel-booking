<?php

use App\Http\Controllers\Portal\HomeController;

use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*----------------- Public Routes -----------------*/
Route::prefix('')->as('portal.')->group(function () {

    Route::get('/', [HomeController::class, 'index'])
        ->name('home');

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