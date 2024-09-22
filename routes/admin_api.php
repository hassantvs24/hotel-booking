<?php

use App\Http\Controllers\API\Admin\ACL\PermissionController;
use App\Http\Controllers\API\Admin\ACL\RoleController;
use App\Http\Controllers\API\Admin\ACL\UserController;
use App\Http\Controllers\API\Admin\Booking\BookingController;
use App\Http\Controllers\API\Admin\Booking\BookingRequestController;
use App\Http\Controllers\API\Admin\Booking\RoomRequestController;
use App\Http\Controllers\API\Admin\Facility\FacilityController;
use App\Http\Controllers\API\Admin\Facility\SubFacilityController;
use App\Http\Controllers\API\Admin\Location\CityController;
use App\Http\Controllers\API\Admin\Location\CountryController;
use App\Http\Controllers\API\Admin\Location\PlaceController;
use App\Http\Controllers\API\Admin\Location\StateController;
use App\Http\Controllers\API\Admin\Property\PropertyCategoryController;
use App\Http\Controllers\API\Admin\Property\PropertyController;
use App\Http\Controllers\API\Admin\Property\PropertyRuleController;
use App\Http\Controllers\API\Admin\Review\ReviewCategoryController;
use App\Http\Controllers\API\Admin\Review\ReviewController;
use App\Http\Controllers\API\Admin\Room\BedTypeController;
use App\Http\Controllers\API\Admin\Room\PriceTypeController;
use App\Http\Controllers\API\Admin\Room\RoomTypeController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingPlaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    /*------------------- ACL -------------------*/
    Route::prefix('acl')->group(function () {
        Route::apiResource('users', UserController::class)->except(['create', 'show']);
        Route::apiResource('roles', RoleController::class)->except(['create', 'show']);
        Route::apiResource('permissions', PermissionController::class)->except(['create', 'show']);


        Route::get('permissions/all', [PermissionController::class, 'all']);
        Route::get('roles/all', [RoleController::class, 'all']);
    });
    /*------------------- ACL -------------------*/

    Route::prefix('location')->group(function () {
        Route::apiResource('countries', CountryController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('states', StateController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('cities', CityController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('places', PlaceController::class)->except(['create', 'show', 'edit']);

        Route::get('countries/all', [CountryController::class, 'all']);
        Route::get('states/all', [StateController::class, 'all']);
        Route::get('cities/all', [CityController::class, 'all']);
        Route::get('places/all', [PlaceController::class, 'all']);
    });

    Route::apiResource('surroundings', SurroundingController::class)->except(['create', 'show', 'edit']);
    Route::get('surroundings/all', [SurroundingController::class, 'all']);

    Route::apiResource('facilities', FacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('facilities/all', [FacilityController::class, 'all']);

    Route::apiResource('sub-facilities', SubFacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('sub-facilities/all', [SubFacilityController::class, 'all']);

    Route::apiResource('surrounding-places', SurroundingPlaceController::class)->except(['create', 'show', 'edit']);
    Route::get('surrounding-places/all', [SurroundingPlaceController::class, 'all']);

    Route::apiResource('review-categories', ReviewCategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('review-categories/all', [ReviewCategoryController::class, 'all']);

    Route::apiResource('reviews', ReviewController::class)->except(['create', 'show', 'edit']);
    Route::get('reviews/all', [ReviewController::class, 'all']);

    Route::apiResource('property-rules', PropertyRuleController::class)->except(['create', 'show', 'edit']);
    Route::get('property-rules/all', [PropertyRuleController::class, 'all']);

    Route::apiResource('properties', PropertyController::class)->except(['create', 'show', 'edit']);
    Route::get('properties/all', [PropertyController::class, 'all']);
    Route::put('properties/{property}/status', [PropertyController::class, 'propertyAction']);

    Route::apiResource('property-categories', PropertyCategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('property-categories/all', [PropertyCategoryController::class, 'all']);

    Route::apiResource('room-types',RoomTypeController::class)->except(['create', 'show', 'edit']);
    Route::get('room-types/all', [RoomTypeController::class, 'all']);

    Route::apiResource('bed-types',BedTypeController::class)->except(['create', 'show', 'edit']);
    Route::get('bed-types/all', [BedTypeController::class, 'all']);

    Route::apiResource('price-types',PriceTypeController::class)->except(['create', 'show', 'edit']);
    Route::get('price-types/all', [PriceTypeController::class, 'all']);

    Route::apiResource('booking-request',BookingRequestController::class)->except(['create','show','edit']);
    Route::get('booking-request/all',[BookingRequestController::class, 'all']);

    Route::put('booking-request/update/{id}',[BookingRequestController::class, 'updateStatus']);

    Route::apiResource('room-request',RoomRequestController::class)->except(['create','show','edit']);
    Route::put('room-request/update/{id}',[RoomRequestController::class, 'updateStatus']);

    Route::apiResource('bookings',BookingController::class)->except(['create','show','edit']);

});
