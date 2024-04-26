<?php

declare(strict_types=1);


use App\Http\Controllers\AuthController;
use App\Http\Middleware\ValidateTenantUser;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Modules\Activity\Http\Controllers\ActivityController;
use Modules\Activity\Http\Controllers\ActivityMessageController;
use Modules\Activity\Http\Controllers\ActivityPrerequisitesController;
use Modules\Activity\Http\Controllers\ActivityPriceTierController;
use Modules\Activity\Http\Controllers\ActivityPricingInfoController;
use Modules\Activity\Http\Controllers\ActivityScheduleController;
use Modules\Activity\Http\Controllers\ActivityScheduleGroupController;
use Modules\Activity\Http\Controllers\ActivitySEOController;
use Modules\Activity\Http\Controllers\BookingSettingController;
use Modules\Activity\Http\Controllers\ConfirmMessageController;
use Modules\Booking\Http\Controllers\BookingController;
use Modules\Booking\Http\Controllers\ParticipantController;
use Modules\BookingProcess\Http\Controllers\BookingProcessActivityController;
use Modules\BookingProcess\Http\Controllers\BookingProcessController;
use Modules\BookingProcess\Http\Controllers\BookingProcessCustomerController;
use Modules\BookingProcess\Http\Controllers\BookingProcessDetailsController;
use Modules\BookingProcess\Http\Controllers\BookingProcessRescheduleController;
use Modules\BookingProcess\Http\Controllers\BookingProcessScheduleController;
use Modules\BookingProcess\Http\Controllers\BookingProcessStaffController;
use Modules\BookingProcess\Http\Controllers\TenantDetailsController;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Course\Http\Controllers\CourseController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Itinerary\Http\Controllers\ItineraryController;
use Modules\Location\Http\Controllers\LocationController;
use Modules\Product\Http\Controllers\ProductActivityController;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\ProductPricingInfoController;
use Modules\Product\Http\Controllers\ProductStockController;
use Modules\Schedule\Http\Controllers\ScheduleController;
use Modules\Setting\Http\Controllers\SettingController;
use Modules\Staff\Http\Controllers\StaffController;
use Modules\Widget\Http\Controllers\WidgetController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Modules\Core\Http\Controllers\PaymentController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

//use App\Http\Middleware\ValidateRequestDomain;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/
Route::middleware([
    'auth:sanctum',
//    InitializeTenancyByDomain::class,
//    PreventAccessFromCentralDomains::class,
    ValidateTenantUser::class,
])->prefix('/api/')->group(function () {
    //group prefix | Activity
    //Activity routes
    Route::apiResource('/activity', ActivityController::class);
    Route::get('/activity_id_and_name', [ActivityController::class, 'activityInfo']);
    //activity update route
    Route::post('/activity/update/{activity}', [ActivityController::class, 'update']);
    Route::group(['prefix' => 'activity/{activity}'], static function () {
        Route::apiResource('/pricing_info', ActivityPricingInfoController::class);
        //group prefix | Activity Schedule
        Route::apiResource('/schedule', ActivityScheduleController::class);
        Route::group(['prefix' => '/schedule'], static function () {
            Route::post('/draft', [ActivityScheduleController::class, 'save']);
            Route::put('/draft_update/{schedule}', [ActivityScheduleController::class, 'updateDraft']);
            Route::post('/reschedule/{schedule}', [ActivityScheduleController::class, 'reschedule']);
            Route::get('/next_schedule/{schedule}', [ActivityScheduleController::class, 'getNextSchedule']);
            Route::get('/previous_schedule/{schedule}', [ActivityScheduleController::class, 'getPreviousSchedule']);
        });
        //group prefix | Activity Schedule group
        Route::apiResource('/schedule_group', ActivityScheduleGroupController::class);
        Route::group(['prefix' => '/schedule_group'], static function () {
            Route::post('/draft', [ActivityScheduleGroupController::class, 'save']);
            Route::put('/draft_update/{schedule_group}', [ActivityScheduleGroupController::class, 'updateDraft']);
            Route::post('/reschedule/{schedule_group}', [ActivityScheduleGroupController::class, 'reschedule']);
            Route::post('/count', [ActivityScheduleGroupController::class, 'getScheduleCount']);
        });
        //activity price tiers routes
        Route::apiResource('/price_tier', ActivityPriceTierController::class);
        //activity schedule messages routes
        Route::apiResource('/message', ActivityMessageController::class);
        Route::post('/message/{message_id}', [ActivityMessageController::class, 'update']);
        //confirmation message routes
        Route::apiResource('/confirm_message', ConfirmMessageController::class);
        //activity prerequisites routes
        Route::apiResource('/prerequisite', ActivityPrerequisitesController::class);
        Route::get('/prerequisites/all', [ActivityPrerequisitesController::class,'view']);
        //activity seo routes
        Route::get('/seo_get', [ActivitySEOController::class, 'index']);
        Route::apiResource('/seo', ActivitySEOController::class);
        Route::post('/seo/{seo_id}', [ActivitySEOController::class, 'update']);

        //select only id and name in price tier
        Route::get('/price_tier_id_and_name', [ActivityPriceTierController::class, 'priceInfo']);
        Route::get('/all_price_tiers', [ActivityPriceTierController::class, 'showAll']);

        //booking setting
        Route::apiResource('/booking_setting', BookingSettingController::class);
    });

    //schedule filter
    Route::post('/schedule_filter', [ScheduleController::class, 'scheduleFilter']);

    //group prefix | Product
    //product routes
    Route::apiResource('/product', ProductController::class);
    Route::group(['prefix' => '/product/{product}'], static function () {
        //product pricing info routes
        Route::apiResource('/pricing_info', ProductPricingInfoController::class);
        //product stock routes
        Route::apiResource('/stock', ProductStockController::class);
    });
    Route::post('product/update/{product}', [ProductController::class, 'update']);
    //product activity routes
    Route::post('product/activity/{product}', [ProductActivityController::class, 'create']);
    Route::put('product/activity/{product}', [ProductActivityController::class, 'update']);

    //staff routes
    Route::apiResource('/staff', StaffController::class);
    Route::group(['prefix' => '/staffs'], static function () {
        Route::get('/staff_assign', [StaffController::class, 'staffInfo']);
        Route::get('/favour', [StaffController::class, 'favouriteMember']);
        Route::get('/schedule', [StaffController::class, 'viewSchedule']);
        Route::get('/all_schedule', [StaffController::class, 'viewScheduleList']);
        Route::post('/add_schedule/{staff}', [StaffController::class, 'addSchedule']);
        Route::get('/show_schedule/{staff}', [StaffController::class, 'showSchedule']);
        Route::delete('/delete_schedule/{staff}/{schedule}', [StaffController::class, 'deleteSchedule']);
        Route::post('/update_staff/{staff}', [StaffController::class, 'update']);
        Route::group(['prefix' => '/check_staff'], static function () {
            Route::get(
                '/schedule/{staff}/{date}/{start_time}/{end_time}/{schedule}',
                [StaffController::class, 'checkStaffAvailability']
            );
            Route::get(
                '/schedule/{staff}/{date}/{start_time}/{end_time}',
                [StaffController::class, 'checkStaffAvailability']
            );
            Route::get(
                '/schedule_group/{staff}/{from_date}/{to_date}/{start_time}/{end_time}/{days}',
                [StaffController::class, 'checkStaffAvailabilityInRange']
            );
            //route added
        });
    });
    //course routes
    Route::apiResource('/course', CourseController::class);
    //group prefix | booking
    Route::apiResource('/booking', BookingController::class);
    Route::group(['prefix' => '/booking'], static function () {
        Route::put('/cancel/{booking}', [BookingController::class, 'cancelRefund']);
        Route::post('/reschedule/{booking}', [BookingController::class, 'reschedule']);
    });
    Route::apiResource('/booking/{booking}/participant', ParticipantController::class);

    //group prefix | Schedule (activity assign manually by user)
    Route::apiResource('/schedule', ScheduleController::class);
    Route::group(['prefix' => '/schedule'], static function () {
        Route::post('/draft', [ScheduleController::class, 'save']);
        Route::put('/draft_update/{schedule}', [ScheduleController::class, 'draftUpdate']);
        Route::post('/reschedule/{schedule}', [ScheduleController::class, 'reschedule']);
        Route::get('/bookings/{schedule}', [ScheduleController::class, 'getBookingDetails']);
        Route::get('/all', [ScheduleController::class, 'getAllSchedules']);
    });

    //customer routes
    Route::apiResource('/customer', CustomerController::class);
    Route::get('/customer/get-activity/{schedule}', [CustomerController::class,'getActivity']);
    Route::group(['prefix' => '/customer/{customer}'], static function () {
        Route::get('/bookings', [CustomerController::class, 'bookings']);
    });

    //itinerary routes
    Route::apiResource('/itinerary', ItineraryController::class);

    //widget routes
    Route::apiResource('/widgets', WidgetController::class);

    //get current schedules
    Route::get("/today's-schedules", [CoreController::class, 'index']);

    //settings
    Route::apiResource('/settings', SettingController::class);
    Route::post('/settings/update/{id}', [SettingController::class, 'update']);

    Route::get('/setting/get-tenet-data', [SettingController::class, 'getTenantData']);

    //locations
    Route::apiResource('/locations', LocationController::class);
    Route::get('/locations_enabled', [LocationController::class, 'showEnabled']);

    //auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/set-payment', [PaymentController::class, 'setPayment'])->name('set.payment');

    Route::get('/set-payment-success/{tenantId}/{token}', [PaymentController::class, 'setPaymentSuccess']);



});

Route::prefix('/api')->group(function () {
    Route::get('/get_widget_data/{widget_id}/{tenant}', [CoreController::class, 'getWidgetData']);
});

Route::group(['prefix' => config('sanctum.prefix', '/api/sanctum')], static function () {
    Route::get('/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
});

Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/register', [AuthController::class, 'register']);
Route::post('/api/forgot_password', [AuthController::class, 'forgotPassword']);
Route::post('/api/reset_password', [AuthController::class, 'resetPassword']);

Route::post('/api/verify_code', [AuthController::class, 'verifyCode']);
Route::get('/api/email_verify', [AuthController::class, 'emailVerify']);


//booking process web routes
Route::middleware([
    'web',
//    InitializeTenancyByDomain::class,
//    PreventAccessFromCentralDomains::class,
])->prefix('/api')->group(function () {
    Route::prefix('booking-process')->group(static function () {
        Route::get('/{tenant}/booking/payment-success', [BookingProcessController::class, 'paymentSuccess']);
        Route::get('{tenant}/booking/update-booking-confirmation/{intentId}/', [BookingProcessController::class, 'updateBookingConfirmation']);
        Route::apiResource('/{tenant}/activity', BookingProcessActivityController::class);
        Route::apiResource('{tenant}/booking', BookingProcessController::class);
        Route::group(['prefix' => '{tenant}/booking'], static function () {
            Route::delete('/cancel_refund/{booking_id}', [BookingProcessController::class, 'cancelRefund']);
        });

        // Booking reschedule login
        Route::post('/{tenant}/booking-reschedule-login', [BookingProcessRescheduleController::class, 'login']);

        Route::apiResource('/{tenant}/booking_details', BookingProcessDetailsController::class);

        Route::group(['prefix' => '/{tenant}/instructors'], static function () {
            // Show instructors
            Route::get('/', [BookingProcessStaffController::class, 'index']);
            // Get schedules and schedule groups details according to instructor
            Route::get('/{instructor}/schedules', [BookingProcessStaffController::class, 'staffSchedules']);
        });

//        Route::apiResource('/schedule/{tenant}', BookingProcessScheduleController::class);

        Route::get('/{tenant}/schedule/{id}/{month}/{staffId}', [BookingProcessScheduleController::class, 'getSelectedScheduleForGivenMonth']);
        Route::get('/{tenant}/schedule-week/{id}/{start_date}/{staffId}', [BookingProcessScheduleController::class, 'getSelectedScheduleForGivenWeek']);
        Route::get('/{tenant}/schedule-date/{id}/{date}/{staffId}', [BookingProcessScheduleController::class, 'getSelectedScheduleForGivenDate']);


        Route::resource('/{tenant}/customer', BookingProcessCustomerController::class);

        Route::get('/get-tenant-details/{tenant}', [TenantDetailsController::class, 'index']);

        Route::get('/get-price-details/{tenant}/{scheduleId}/{activityId}/{month}/{slotCount}', [BookingProcessScheduleController::class, 'getPriceDetails']);

        Route::get('/get-selected-date-schedules/{tenant}/{activityId}/{date}/{staffId}', [BookingProcessScheduleController::class, 'getSelectedDateSchedules']);

        Route::get('get-staff-by-activity/{tenant}/{id}', [BookingProcessActivityController::class, 'getActivityStaff']);

        Route::get('/{tenant}/activity-products/{activityId}', [BookingProcessActivityController::class, 'getActivityProductsById']);

        Route::post('/{tenant}/validate-booking-reference', [BookingProcessRescheduleController::class, 'validateBookingReference']);

        Route::post('/{tenant}/fetch-booked-data/{reference}', [BookingProcessRescheduleController::class, 'fetchUpdatedBookedData']);

        Route::get('/{tenant}/cancel-booking-request/{bookingReference}', [BookingProcessRescheduleController::class, 'cancelBookingRequest']);

        Route::get('/{tenant}/get-booking-date-details/{bookingReference}', [BookingProcessRescheduleController::class, 'getBookingDateDetails']);
        Route::get('/{tenant}/get-your-details/{bookingReference}', [BookingProcessRescheduleController::class, 'getYourDetails']);
        Route::post('/{tenant}/update-your-detail/{bookingReference}',[BookingProcessRescheduleController::class,'updateYourDetails']);

        Route::post('/{tenant}/change-booking-date/{bookingReference}', [BookingProcessRescheduleController::class, 'changeBookingDate']);
    });
});



