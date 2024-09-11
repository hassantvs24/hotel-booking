<?php

use App\Http\Controllers\Admin\Facility\SubFacilityController as FacilitySubFacilityController;
use App\Http\Controllers\API\Admin\Facility\FacilityController;
use App\Http\Controllers\API\Admin\Facility\SubFacilityController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingController;
use App\Http\Controllers\API\Admin\ACL\PermissionController;
use App\Http\Controllers\API\Admin\ACL\RoleController;
use App\Http\Controllers\API\Admin\ACL\UserController;
use App\Http\Controllers\API\Admin\Location\CityController;
use App\Http\Controllers\API\Admin\Location\CountryController;
use App\Http\Controllers\API\Admin\Location\PlaceController;
use App\Http\Controllers\API\Admin\Location\StateController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingPlaceController;
use App\Http\Controllers\API\Portal\Auth\LoginController;
use App\Http\Controllers\API\Portal\Auth\ProfileController;
use App\Http\Controllers\API\Portal\Auth\RegisterController;
use App\Http\Controllers\API\Portal\Auth\SocialiteController;
use App\Http\Controllers\API\Portal\BookingController;
use App\Http\Controllers\API\Portal\HomeController;
use App\Http\Controllers\API\Portal\PropertyController;
use App\Http\Controllers\API\Portal\RequestController;
use App\Http\Controllers\API\Portal\RoomRequestController;
use App\Http\Controllers\API\Portal\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user()->load(['profile']);
    return $user;
})->middleware('auth:sanctum');

/*----------------- Portal API -----------------*/
Route::prefix('portal')->group(function () {
    Route::get('home', [HomeController::class, 'index']);
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{place}/place', [PropertyController::class, 'placeWiseProperties']);
        Route::get('/{property}/details', [PropertyController::class, 'details']);
        Route::get('/{property}/available-rooms', [PropertyController::class, 'availableRooms']);
        Route::get('/{property}/booking-request-check', [PropertyController::class, 'bookingRoomCheck']);
    });

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
    });

    Route::get('/room/{room}/payment', [BookingController::class, 'paymentDetails']);
    Route::post('/booking', [BookingController::class, 'booking']);

    // room request
    Route::prefix('room')->group(function () {
        Route::post('/request', [RoomRequestController::class, 'roomRequest']);
    });

    Route::prefix('request')->group(function () {
        Route::post("/", [RequestController::class, 'index']);
        Route::get("/properties", [RequestController::class, 'property_list']);
    });
});


/*----------------- Auth API -----------------*/
Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialiteController::class, 'redirectToProvider']);
    Route::get('/{provider}/callback', [SocialiteController::class, 'handleProviderCallback']);

    Route::post('register', [RegisterController::class, 'register']);
    Route::post('login', [LoginController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout']);

    Route::prefix('profile/update')->middleware(['auth:sanctum'])->group(function () {
        Route::post('personal-info', [ProfileController::class, 'updatePersonalInfo']);
        Route::post('preference', [ProfileController::class, 'updatePreferenceInfo']);
    });
});

/*----------------- Auth API -----------------*/

Route::middleware('auth:sanctum')->prefix('admin')->group(function() {
    /*------------------- ACL -------------------*/
    Route::prefix('acl')->group(function() {
        Route::apiResource('users', UserController::class)->except(['create', 'show']);
        Route::apiResource('roles', RoleController::class)->except(['create', 'show']);
        Route::apiResource('permissions', PermissionController::class)->except(['create', 'show']);


        Route::get('permissions/all', [PermissionController::class, 'all']);
        Route::get('roles/all', [RoleController::class, 'all']);
    });
    /*------------------- ACL -------------------*/

    Route::prefix('location')->group(function() {
        Route::apiResource('countries', CountryController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('states', StateController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('cities', CityController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('places', PlaceController::class)->except(['create', 'show', 'edit']);

        Route::get('countries/all', [CountryController::class, 'all']);
        Route::get('states/all', [StateController::class, 'all']);
        Route::get('cities/all', [CityController::class, 'all']);
    });

    Route::apiResource('surroundings', SurroundingController::class)->except(['create', 'show', 'edit']);
    Route::get('surroundings/all', [SurroundingController::class, 'all']);

    Route::apiResource('facilities', FacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('facilities/all', [FacilityController::class, 'all']);
   
    Route::apiResource('sub-facilities', SubFacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('sub-facilities/all',[SubFacilityController::class,'all']);
    
    Route::apiResource('surrounding-places', SurroundingPlaceController::class)->except(['create', 'show', 'edit']);
    Route::get('surrounding-places/all', [SurroundingPlaceController::class, 'all']);
});
