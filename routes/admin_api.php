<?php

use App\Http\Controllers\API\Admin\ACL\PermissionController;
use App\Http\Controllers\API\Admin\ACL\RoleController;
use App\Http\Controllers\API\Admin\ACL\UserController;
use App\Http\Controllers\API\Admin\Booking\BookingController;
use App\Http\Controllers\API\Admin\Booking\BookingRequestController;
use App\Http\Controllers\API\Admin\Booking\RefundController;
use App\Http\Controllers\API\Admin\Booking\RoomRequestController;
use App\Http\Controllers\API\Admin\Dashboard\DashboardController;
use App\Http\Controllers\API\Admin\Location\CityController;
use App\Http\Controllers\API\Admin\Location\CountryController;
use App\Http\Controllers\API\Admin\Location\PlaceController;
use App\Http\Controllers\API\Admin\Location\StateController;
use App\Http\Controllers\API\Admin\Property\Facility\FacilityController;
use App\Http\Controllers\API\Admin\Property\Facility\SubFacilityController;
use App\Http\Controllers\API\Admin\Property\PropertyCategoryController;
use App\Http\Controllers\API\Admin\Property\PropertyController;
use App\Http\Controllers\API\Admin\Property\PropertyRequestController;
use App\Http\Controllers\API\Admin\Property\PropertyRuleController;
use App\Http\Controllers\API\Admin\Property\PropertySettingController;
use App\Http\Controllers\API\Admin\Property\Room\BedTypeController;
use App\Http\Controllers\API\Admin\Property\Room\PriceTypeController;
use App\Http\Controllers\API\Admin\Property\Room\RoomController;
use App\Http\Controllers\API\Admin\Property\Room\RoomTypeController;
use App\Http\Controllers\API\Admin\Review\ReviewCategoryController;
use App\Http\Controllers\API\Admin\Review\ReviewController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingPlaceController;
use App\Http\Controllers\API\Admin\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    /*----------------------------------------------------------
    | ACL
    ----------------------------------------------------------*/
    Route::prefix('acl')->group(function () {
        Route::apiResource('users',       UserController::class      )->except(['create', 'show', 'edit']);
        Route::apiResource('roles',       RoleController::class      )->except(['create', 'show', 'edit']);
        Route::apiResource('permissions', PermissionController::class)->except(['create', 'show', 'edit']);

        Route::get('permissions/all', [PermissionController::class, 'all']);
        Route::get('roles/all',       [RoleController::class,       'all']);
    });

    /*----------------------------------------------------------
    | Location
    ----------------------------------------------------------*/
    Route::prefix('location')->group(function () {
        Route::apiResource('countries', CountryController::class)->except(['create', 'show', 'edit']);
        Route::apiResource('states',    StateController::class  )->except(['create', 'show', 'edit']);
        Route::apiResource('cities',    CityController::class   )->except(['create', 'show', 'edit']);
        Route::apiResource('places',    PlaceController::class  )->except(['create', 'show', 'edit']);

        Route::get('countries/all', [CountryController::class, 'all']);
        Route::get('states/all',    [StateController::class,   'all']);
        Route::get('cities/all',    [CityController::class,    'all']);
        Route::get('places/all',    [PlaceController::class,   'all']);
    });

    /*----------------------------------------------------------
    | Surroundings
    ----------------------------------------------------------*/
    Route::apiResource('surroundings', SurroundingController::class)->except(['create', 'show', 'edit']);
    Route::get('surroundings/all', [SurroundingController::class, 'all']);

    Route::apiResource('surrounding-places', SurroundingPlaceController::class)->except(['create', 'show', 'edit']);
    Route::get('surrounding-places/all', [SurroundingPlaceController::class, 'all']);

    /*----------------------------------------------------------
    | Facilities
    ----------------------------------------------------------*/
    Route::apiResource('facilities', FacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('facilities/all', [FacilityController::class, 'all']);

    Route::apiResource('sub-facilities', SubFacilityController::class)->except(['create', 'show', 'edit']);
    Route::get('sub-facilities/all', [SubFacilityController::class, 'all']);

    /*----------------------------------------------------------
    | Reviews
    ----------------------------------------------------------*/
    Route::apiResource('review-categories', ReviewCategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('review-categories/all', [ReviewCategoryController::class, 'all']);

    Route::apiResource('reviews', ReviewController::class)->except(['create', 'show', 'edit']);
    Route::get('reviews/all', [ReviewController::class, 'all']);

    /*----------------------------------------------------------
    | Property rules
    ----------------------------------------------------------*/
    Route::apiResource('property-rules', PropertyRuleController::class)->except(['create', 'show', 'edit']);
    Route::get('property-rules/all', [PropertyRuleController::class, 'all']);

    /*----------------------------------------------------------
    | Properties
    ----------------------------------------------------------*/
    Route::apiResource('properties', PropertyController::class)->except(['create', 'show', 'edit']);
    Route::get('properties/all',                          [PropertyController::class, 'all']);
    Route::get('property/attributes',                     [PropertyController::class, 'propertyAttributes']);
    Route::put('properties/{property}/status',            [PropertyController::class, 'propertyAction']);
    Route::get('property/{property}/details',             [PropertyController::class, 'details']);

    Route::get('property/requests',                       [PropertyRequestController::class, 'index']);
    Route::put('property/requests/{id}/update',           [PropertyRequestController::class, 'updateStatus']);

    Route::apiResource('property-categories', PropertyCategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('property-categories/all', [PropertyCategoryController::class, 'all']);

    Route::put('properties/{property}/properties-setting', [PropertySettingController::class, 'update']);

    /*----------------------------------------------------------
    | Rooms
    ----------------------------------------------------------*/
    Route::apiResource('rooms', RoomController::class)->except(['create', 'show', 'edit']);
    Route::get('rooms/all',              [RoomController::class, 'all']);
    Route::get('rooms/{room}/details',   [RoomController::class, 'showDetails']);

    Route::apiResource('room-types',  RoomTypeController::class )->except(['create', 'show', 'edit']);
    Route::get('room-types/all',  [RoomTypeController::class,  'all']);

    Route::apiResource('bed-types',   BedTypeController::class  )->except(['create', 'show', 'edit']);
    Route::get('bed-types/all',   [BedTypeController::class,   'all']);

    Route::apiResource('price-types', PriceTypeController::class)->except(['create', 'show', 'edit']);
    Route::get('price-types/all', [PriceTypeController::class, 'all']);

    /*----------------------------------------------------------
    | Bookings
    ----------------------------------------------------------*/
    Route::get('bookings/stats',        [BookingController::class, 'stats']);
    Route::get('booking-check',         [BookingController::class, 'bookingCheck']);
    Route::apiResource('bookings', BookingController::class)->except(['create', 'show', 'edit']);
    Route::get('bookings/{id}/show',    [BookingController::class, 'show']);
    Route::put('bookings/{id}/update',  [BookingController::class, 'updateStatus']);

    // ── Transactions ──────────────────────────────────────────────
    Route::get('transactions',        [\App\Http\Controllers\API\Admin\Booking\TransactionController::class, 'index']);
    Route::get('transactions/stats',  [\App\Http\Controllers\API\Admin\Booking\TransactionController::class, 'stats']);

    /*
|--------------------------------------------------------------------------
| Refund requests
|--------------------------------------------------------------------------
*/

    Route::get('refunds', [RefundController::class, 'index',]);
    Route::get('refunds/stats', [RefundController::class, 'stats',]);
    Route::get('refunds/{refund:refund_number}', [RefundController::class, 'show',]);
    Route::post('refunds/{refund:refund_number}/approve', [RefundController::class, 'approve',]);

    /*----------------------------------------------------------
    | Booking requests
    ----------------------------------------------------------*/
    Route::get('booking-request/all',           [BookingRequestController::class, 'all']);
    Route::put('booking-request/{id}/update',   [BookingRequestController::class, 'updateStatus']);
    Route::apiResource('booking-request', BookingRequestController::class)->except(['create', 'show', 'edit']);

    /*----------------------------------------------------------
    | Room requests (Bids)
    ----------------------------------------------------------*/
    Route::get('room-request/stats',            [RoomRequestController::class, 'stats']);
    Route::get('room-request/{id}/show',        [RoomRequestController::class, 'show']);
    Route::put('room-request/update/{id}',      [RoomRequestController::class, 'updateStatus']);
    Route::apiResource('room-request', RoomRequestController::class)->except(['create', 'show', 'edit']);

    /*----------------------------------------------------------
    | Notifications
    ----------------------------------------------------------*/
    Route::prefix('notifications')->group(function () {
        Route::get('/',           [NotificationController::class, 'index']);
        Route::put('{id}/read',   [NotificationController::class, 'markRead']);
        Route::put('read-all',    [NotificationController::class, 'markAllRead']);
    });

    /*----------------------------------------------------------
    | Dashboard
    ----------------------------------------------------------*/
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
    });
});
