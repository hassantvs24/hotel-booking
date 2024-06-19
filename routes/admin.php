<?php
use App\Http\Controllers\Admin\ACL\PermissionController;
use App\Http\Controllers\Admin\ACL\RoleController;
use App\Http\Controllers\Admin\ACL\UserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

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