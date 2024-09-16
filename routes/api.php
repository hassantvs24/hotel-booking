<?php

use App\Http\Controllers\API\Admin\Booking\BookingRequestController;
use App\Http\Controllers\API\Admin\Facility\FacilityController;
use App\Http\Controllers\API\Admin\Facility\SubFacilityController;
use App\Http\Controllers\API\Admin\Property\PropertyCategoryController;
use App\Http\Controllers\API\Admin\Surrounding\SurroundingController;
use App\Http\Controllers\API\Admin\ACL\PermissionController;
use App\Http\Controllers\API\Admin\ACL\RoleController;
use App\Http\Controllers\API\Admin\ACL\UserController;
use App\Http\Controllers\API\Admin\Booking\BookingController as BookingBookingController;
use App\Http\Controllers\API\Admin\Booking\RoomRequestController as AdminRoomRequestController;
use App\Http\Controllers\API\Admin\Location\CityController;
use App\Http\Controllers\API\Admin\Location\CountryController;
use App\Http\Controllers\API\Admin\Location\PlaceController;
use App\Http\Controllers\API\Admin\Location\StateController;
use App\Http\Controllers\API\Admin\Property\PropertyRuleController;
use App\Http\Controllers\API\Admin\Property\PropertyController as AdminPropertyController;
use App\Http\Controllers\API\Admin\Review\ReviewCategoryController;
use App\Http\Controllers\API\Admin\Review\ReviewController;
use App\Http\Controllers\API\Admin\Room\BedTypeController;
use App\Http\Controllers\API\Admin\Room\PriceTypeController;
use App\Http\Controllers\API\Admin\Room\RoomTypeController;
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
use App\Http\Controllers\API\Portal\Vendor\VendorController;
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
    Route::prefix('room')->middleware('auth:sanctum')->group(function () {
        Route::post('/request', [RoomRequestController::class, 'roomRequest']);
        Route::get('/request-notification', [RoomRequestController::class, 'roomRequestNotification']);
        Route::get('/request-response/{propertyId}', [RoomRequestController::class, 'roomResponselist']);
    });

    Route::prefix('request')->middleware('auth:sanctum')->group(function () {
        Route::post("/", [RequestController::class, 'index']);
        Route::get("/properties", [RequestController::class, 'property_list']);
        Route::get('/fetchtimer',[RequestController::class, 'fetchTimer']);
    });

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/getAllPropertyOption', [VendorController::class, 'allPropertyOption']);
        Route::post('/property/store', [VendorController::class, 'store']);
        Route::get('/property/allproperties', [VendorController::class, 'allproperties']);
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

    Route::apiResource('properties', AdminPropertyController::class)->except(['create', 'show', 'edit']);
    Route::get('properties/all', [AdminPropertyController::class, 'all']);
    Route::put('properties/{property}/status', [AdminPropertyController::class, 'propertyAction']);

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

    Route::apiResource('room-request',AdminRoomRequestController::class)->except(['create','show','edit']);
    Route::put('room-request/update/{id}',[AdminRoomRequestController::class, 'updateStatus']);
    
    Route::apiResource('bookings',BookingBookingController::class)->except(['create','show','edit']);

});
