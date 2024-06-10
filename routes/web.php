<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard.index');
});


/*----------------- Admin Routes -----------------*/

Route::prefix('admin')->as('admin.')->group(function () {

    /*-- Dashboard Routes --*/
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

});
/*----------------- Admin Routes -----------------*/