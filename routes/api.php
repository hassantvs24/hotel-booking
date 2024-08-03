<?php

use App\Http\Controllers\API\Portal\HomeController;
use App\Http\Controllers\API\Portal\PropertyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*----------------- Portal API -----------------*/
Route::prefix('portal')->group(function () {
    Route::get('home', [HomeController::class, 'index']);
    Route::get('properties', [PropertyController::class, 'index']);
});
