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
