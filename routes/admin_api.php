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
use App\Http\Controllers\API\ChatController;
use Illuminate\Support\Facades\Route;

// 'staff': only the admin and property owners get in at all; a guest account (anyone can sign up) gets a 403 on every route here
Route::middleware(['auth:sanctum', 'staff'])->prefix('admin')->group(function () {

    Route::prefix('chat')->controller(ChatController::class)->group(function () {
        Route::get('/conversations', 'index');
        Route::get('/conversations/{conversation}/messages', 'messages');
        Route::post('/conversations/{conversation}/messages', 'send')->middleware('throttle:30,1');
        Route::post('/conversations/{conversation}/read', 'read');
        Route::get('/conversations/{conversation}/requests', 'specialRequests');
        Route::post('/conversations/{conversation}/internal-notes', 'internalNote');
        Route::post('/conversations/{conversation}/status', 'setStatus');
        Route::post('/conversations/{conversation}/requests/{specialRequest}/respond', 'respondSpecialRequest');
    });

    /*----------------------------------------------------------
    | ACL
    ----------------------------------------------------------*/
    Route::prefix('acl')->middleware('admin.only')->group(function () {
        Route::apiResource('users',       UserController::class      )->except(['create', 'show', 'edit']);
        Route::apiResource('roles',       RoleController::class      )->except(['create', 'show', 'edit']);
        Route::apiResource('permissions', PermissionController::class)->except(['create', 'show', 'edit']);

        Route::get('permissions/all', [PermissionController::class, 'all']);
        Route::get('roles/all',       [RoleController::class,       'all']);
    });

    /*----------------------------------------------------------
    | Location
    ----------------------------------------------------------*/
    Route::prefix('location')->middleware('admin.write')->group(function () {
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
    Route::apiResource('review-categories', ReviewCategoryController::class)->except(['create', 'show', 'edit'])->middleware('admin.write');
    Route::get('review-categories/all', [ReviewCategoryController::class, 'all']);

    Route::apiResource('reviews', ReviewController::class)->except(['create', 'show', 'edit'])->middleware('admin.write');
    Route::get('reviews/all', [ReviewController::class, 'all']);

    Route::prefix('review-submissions')
        ->controller(\App\Http\Controllers\API\Admin\Review\ReviewSubmissionController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/stats', 'stats');
            Route::get('/{submission}', 'show');
            Route::post('/{submission}/reply', 'reply');
            Route::put('/{submission}/status', 'updateStatus');
        });

    /*----------------------------------------------------------
    | Property rules
    ----------------------------------------------------------*/
    Route::apiResource('property-rules', PropertyRuleController::class)->except(['create', 'show', 'edit'])->middleware('admin.write');
    Route::get('property-rules/all', [PropertyRuleController::class, 'all']);

    /*----------------------------------------------------------
    | Properties
    ----------------------------------------------------------*/
    // Listing, adding, editing and deleting properties here is the admin's job; an owner manages their own property under property settings
    Route::apiResource('properties', PropertyController::class)->except(['create', 'show', 'edit'])->middleware('admin.only');
    Route::get('properties/all',                          [PropertyController::class, 'all']);
    Route::get('property/attributes',                     [PropertyController::class, 'propertyAttributes']);
    Route::put('properties/{property}/status',            [PropertyController::class, 'propertyAction'])->middleware('admin.only');
    Route::put('properties/{property}/verified',          [PropertyController::class, 'setVerified']);
    Route::get('property/{property}/details',             [PropertyController::class, 'details']);

    Route::get('property/requests',                       [PropertyRequestController::class, 'index']);
    Route::put('property/requests/{id}/update',           [PropertyRequestController::class, 'updateStatus']);
    Route::get('property/requests/{id}/verification',      [PropertyRequestController::class, 'verification']);
    Route::get('property/requests/{id}/verification/file/{kind}', [PropertyRequestController::class, 'verificationFile']);
    Route::put('property/requests/{id}/verification/{target}', [PropertyRequestController::class, 'verificationDecision']);

    Route::apiResource('property-categories', PropertyCategoryController::class)->except(['create', 'show', 'edit'])->middleware('admin.write');
    Route::get('property-categories/all', [PropertyCategoryController::class, 'all']);

    Route::put('properties/{property}/properties-setting', [PropertySettingController::class, 'update']);

    // VAT and service charge: platform rates (admin) and each property's own default (owner or admin)
    Route::get('settings/tax',  [\App\Http\Controllers\API\Admin\Property\TaxRateController::class, 'platform']);
    Route::put('settings/tax',  [\App\Http\Controllers\API\Admin\Property\TaxRateController::class, 'updatePlatform']);

    // money: commission and payout rules (platform admin only)
    Route::prefix('money')->controller(\App\Http\Controllers\API\Admin\Money\MoneySettingsController::class)->group(function () {
        Route::get('settings',               'show');
        Route::put('settings',               'update');
        Route::post('settings/confirm',      'confirm');
        Route::get('properties/{property}',  'property');
        Route::put('properties/{property}',  'updateProperty');
    });
    Route::prefix('money')->controller(\App\Http\Controllers\API\Admin\Money\MoneyReportController::class)->group(function () {
        Route::get('overview',                'overview');
        Route::get('vendors',                 'vendors');
        Route::get('vendors/{vendor}',        'vendor')->whereNumber('vendor');
        Route::get('bookings/{number}',       'booking')->whereNumber('number');
        Route::post('bookings/{number}/review', 'review')->whereNumber('number');
        Route::get('lines',                   'lines');
    });

    // the legal pages the admin edits
    Route::get('legal',         [\App\Http\Controllers\API\Admin\Legal\LegalDocumentController::class, 'index']);
    Route::get('legal/{slug}',  [\App\Http\Controllers\API\Admin\Legal\LegalDocumentController::class, 'show']);
    Route::put('legal/{slug}',  [\App\Http\Controllers\API\Admin\Legal\LegalDocumentController::class, 'update']);
    Route::get('properties/{property}/tax-rates', [\App\Http\Controllers\API\Admin\Property\TaxRateController::class, 'property']);
    Route::put('properties/{property}/tax-rates', [\App\Http\Controllers\API\Admin\Property\TaxRateController::class, 'updateProperty']);

    // Refund policy: set by the property owner only (admin can view)
    Route::get('properties/{property}/refund-policy', [\App\Http\Controllers\API\Admin\Property\PropertyRefundPolicyController::class, 'show']);
    Route::put('properties/{property}/refund-policy', [\App\Http\Controllers\API\Admin\Property\PropertyRefundPolicyController::class, 'update']);

    /*----------------------------------------------------------
    | Rooms
    ----------------------------------------------------------*/
    Route::apiResource('rooms', RoomController::class)->except(['create', 'show', 'edit']);
    Route::get('rooms/all',              [RoomController::class, 'all']);
    Route::get('rooms/{room}/details',   [RoomController::class, 'showDetails']);
    Route::post('rooms/{room}/duplicate', [RoomController::class, 'duplicate']);
    Route::get('rooms/usage',            [RoomController::class, 'usage']);
    Route::post('rooms/bulk-delete',     [RoomController::class, 'bulkDestroy']);
    Route::post('rooms/bulk-update',     [RoomController::class, 'bulkUpdate']);

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
    Route::post('refunds/{refund:refund_number}/check-status', [RefundController::class, 'checkStatus',]);
    Route::post('refunds/{refund:refund_number}/reject', [RefundController::class, 'reject',]);

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
