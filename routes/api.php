<?php

use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\QpayController;
use App\Http\Controllers\ShiftController;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CouponsController;
use App\Http\Controllers\CouponCodesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DiscountsController;
use App\Http\Controllers\MembershipsController;
use App\Http\Controllers\MembershipTypesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MobileOtpController;
use App\Http\Controllers\ResourcesController;
use App\Http\Controllers\ServiceCategoriesController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SoumDistrictsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\OnlineBookingSettingsController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\BranchesController;
use App\Http\Controllers\BankAccountsController;
use App\Http\Controllers\ServiceMethodController;
use App\Models\Appointment;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/verify_token', function (Request $request) {
        return [...$request->user()->attributesToArray(), 'role' => $request->user()->role->name];
    });
    Route::post('/validate_system_overdue', [AuthenticationController::class, 'validateSystemOverdue']);

    Route::get('users/query', [UsersController::class, 'index']);
    Route::put('user', [UsersController::class, 'store']);
    Route::get('user/{id}', [UsersController::class, 'show']);
    Route::post('user/{id}', [UsersController::class, 'update']);
    Route::delete('user/{id}', [UsersController::class, 'destroy']);
    Route::post('user/{id}/update_email', [UsersController::class, 'updateEmail']);
    Route::post('user/{id}/update_password', [UsersController::class, 'updatePassword']);
    Route::post('user/', [UsersController::class, 'updatePassword']);

    Route::prefix('master_data')->group(function () {
        Route::get('resources', [ResourcesController::class, 'getResources']);
    });

    Route::get('customers/query', [CustomersController::class, 'index']);
    Route::put('customer', [CustomersController::class, 'store']);
    Route::get('customer/{id}', [CustomersController::class, 'show']);
    Route::post('customer/{id}', [CustomersController::class, 'update']);
    Route::delete('customer/{id}', [CustomersController::class, 'destroy']);
    Route::get('customer/get_appointments_data/{id}', [CustomersController::class, 'getAppointmentHistory']);
    Route::get('customer/get_payments_data/{id}', [CustomersController::class, 'getPaymentHistory']);
    Route::get('customer/get_membership_data/{id}', [CustomersController::class, 'getMembership']);
    Route::post('customer/remove_member/{id}', [CustomersController::class, 'removeMember']);
    Route::post('customer/member/add_customer', [CustomersController::class, 'addMember']);
    Route::post('customer/images/add_image', [CustomersController::class, 'addImage']);
    Route::get('customer/get_customer_images/{id}', [CustomersController::class, 'getImages']);
    Route::get('customer/delete_customer_image/{id}', [CustomersController::class, 'deleteImage']);

    Route::get('services/query', [ServicesController::class, 'index']);
    Route::put('service', [ServicesController::class, 'store']);
    Route::get('service/{id}', [ServicesController::class, 'show']);
    Route::post('service/{id}', [ServicesController::class, 'update']);
    Route::delete('service/{id}', [ServicesController::class, 'destroy']);

    Route::get('service_method/query', [ServiceMethodController::class, 'index']);
    Route::get('service_method/{id}', [ServiceMethodController::class, 'getServiceMethod']);
    Route::delete('service_method/{id}', [ServiceMethodController::class, 'deleteServiceMethod']);
    Route::post('service_method', [ServiceMethodController::class, 'updateOrCreate']);

    Route::prefix('service')->group(function () {
        Route::put('category', [ServicesController::class, 'storeCategory']);
        Route::get('category/{id}', [ServicesController::class, 'showCategory']);
        Route::post('category/{id}', [ServicesController::class, 'updateCategory']);
        Route::delete('category/{id}', [ServicesController::class, 'destroyCategory']);
    });

    Route::get('resources/query', [ResourcesController::class, 'index']);
    Route::put('resource', [ResourcesController::class, 'store']);
    Route::get('resource/{id}', [ResourcesController::class, 'show']);
    Route::post('resource/{id}', [ResourcesController::class, 'update']);
    Route::delete('resource/{id}', [ResourcesController::class, 'destroy']);

    Route::prefix('appointment')->group(function () {
        Route::post('events', [AppointmentsController::class, 'index']);
        Route::get('users', [AppointmentsController::class, 'userResources']);
        Route::put('event', [AppointmentsController::class, 'store']);
        Route::get('event/{id}', [AppointmentsController::class, 'show']);
        Route::post('event/{id}', [AppointmentsController::class, 'changeEvent']);
        Route::post('item', [AppointmentsController::class, 'update']);
        Route::get('master_data', [AppointmentsController::class, 'getMasterData']);
        Route::post('change_status/{id}', [AppointmentsController::class, 'changeStatus']);
        Route::post('change_treatment_status/{id}', [AppointmentsController::class, 'changeTreatmentStatus']);
        Route::post('change_desc/{id}', [AppointmentsController::class, 'changeDesc']);
        Route::post('cancel/{id}', [AppointmentsController::class, 'cancelEvent']);
        Route::post('payment/{id}', [AppointmentsController::class, 'createPayment']);
        Route::get('get_payment_details/{id}', [AppointmentsController::class, 'getPaymentDetails']);
        Route::post('void_payment/{id}', [AppointmentsController::class, 'voidPayment']);
        Route::post('coupon_codes/detail', [CouponCodesController::class, 'show']);
        Route::get('payment_methods', [AppointmentsController::class, 'getPaymentMethods']);
        Route::put('update_payment_methods', [AppointmentsController::class, 'updatePaymentMethods']);
    });
    Route::get('health_condition/{id}', [AppointmentsController::class, 'getHealthCondition']);
    Route::get('health_condition/print/{id}', [AppointmentsController::class, 'printHealthCondition']);
    Route::post('health_condition', [AppointmentsController::class, 'updateHealthCondition']);

    Route::get('discounts/query', [DiscountsController::class, 'index']);
    Route::get('discounts/master_data', [DiscountsController::class, 'getMasterData']);
    Route::put('discount', [DiscountsController::class, 'store']);
    Route::get('discount/{id}', [DiscountsController::class, 'show']);
    Route::post('discount/{id}', [DiscountsController::class, 'update']);
    Route::delete('discount/{id}', [DiscountsController::class, 'destroy']);

    Route::get('coupons/query', [CouponsController::class, 'index']);
    Route::get('coupons/master_data', [CouponsController::class, 'getMasterData']);
    Route::put('coupon', [CouponsController::class, 'store']);
    Route::get('coupon/{id}', [CouponsController::class, 'show']);
    Route::post('coupon/{id}', [CouponsController::class, 'update']);
    Route::delete('coupon/{id}', [CouponsController::class, 'destroy']);

    Route::get('coupon_codes/query', [CouponCodesController::class, 'index']);
    Route::get('coupon_codes/master_data', [CouponCodesController::class, 'getMasterData']);
    Route::put('coupon_codes', [CouponCodesController::class, 'store']);
    Route::post('coupon_codes/detail', [CouponCodesController::class, 'show']);
    Route::delete('coupon_codes/{id}', [CouponCodesController::class, 'destroy']);
    Route::post('coupon_codes', [CouponCodesController::class, 'update']);


    Route::get('membership_types/query', [MembershipTypesController::class, 'index']);
    Route::put('membership_type', [MembershipTypesController::class, 'store']);
    Route::get('membership_type/{id}', [MembershipTypesController::class, 'show']);
    Route::post('membership_type/{id}', [MembershipTypesController::class, 'update']);
    Route::delete('membership_type/{id}', [MembershipTypesController::class, 'destroy']);

    Route::get('memberships/query', [MembershipsController::class, 'index']);
    Route::get('memberships/master_data', [MembershipsController::class, 'getMasterData']);
    Route::get('membership/{id}/{type}', [MembershipsController::class, 'show']);
    Route::put('membership', [MembershipsController::class, 'store']);
    Route::post('membership/{id}', [MembershipsController::class, 'update']);
    Route::delete('membership/{id}', [MembershipsController::class, 'destroy']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::post('settings', [SettingsController::class, 'update']);

    Route::post('settings/online-booking', [OnlineBookingSettingsController::class, 'update']);

    Route::post('dashboard', [ReportsController::class, 'getDashboardData']);
    Route::post('details/events/query', [ReportsController::class, 'eventDetailsReport']);
    Route::get('details/statuses', function () {
        return config('global.statuses');
    });
    Route::post('/sms-history/query', [ReportsController::class, 'smsHistory']);

    Route::post('report/general_report', [ReportsController::class, 'generalReport']);
    Route::post('report/income_report_by_days', [ReportsController::class, 'incomeReportByDays']);
    Route::post('report/income_report_by_users', [ReportsController::class, 'incomeReportByUsers']);
    Route::post('report/attendance_report_by_users', [ReportsController::class, 'attendanceReportByUsers']);
    Route::post('report/attendance_report_by_services', [ReportsController::class, 'attendanceReportByServices']);
    Route::post('report/attendance_report_by_rush_hours', [ReportsController::class, 'attendanceReportByRushHours']);
    Route::post('report/customer_report_by_frequency', [ReportsController::class, 'customerReportByFrequency']);
    Route::post('report/get_coupon_codes', [ReportsController::class, 'couponCodeDownload']);

    Route::get('bank_accounts/query', [BankAccountsController::class, 'index']);
    Route::put('bank_account', [BankAccountsController::class, 'store']);
    Route::get('bank_account/{id}', [BankAccountsController::class, 'show']);
    Route::post('bank_account/{id}', [BankAccountsController::class, 'update']);
    Route::delete('bank_account/{id}', [BankAccountsController::class, 'destroy']);

    Route::get('shift/index', [ShiftController::class, 'index']);
    Route::put('shift/store_data', [ShiftController::class, 'store']);
    Route::put('shift/store_time_off', [ShiftController::class, 'storeTimeOff']);

});

Route::prefix('qpay')->group(function () {
    Route::post('invoice', [QpayController::class, 'createInvoice']);
    Route::post('check', [QpayController::class, 'qpayCheck']);
    Route::post('coupon_payment', [QpayController::class, 'createCouponPayment']);
});
Route::post('socket/auth', [QpayController::class, 'echoAuth']);

Route::post('/login', [AuthenticationController::class, 'login']);
Route::post('/forgot_password', [AuthenticationController:: class, 'forgotPassword']);
Route::get('settings_public', [SettingsController::class, 'showPublic']);
Route::get('settings/online-booking', [OnlineBookingSettingsController::class, 'show']);

Route::prefix('online_booking')->group(function () {
    Route::get('master_data', [OnlineBookingController::class, 'getMasterData']);
    Route::post('appointment', [OnlineBookingController::class, 'onlineBooking']);
    Route::get('master_data', [OnlineBookingController::class, 'getMasterData']);
    Route::post('branch_users', [OnlineBookingController::class, 'getBranchUsers']);
    Route::post('available_days', [OnlineBookingController::class, 'getAvailableDates']);
    Route::post('available_hours', [OnlineBookingController::class, 'getAvailableHours']);
    Route::get('branch_services/{branch_id}', [OnlineBookingController::class, 'getBranchServices']);
    Route::post('check_membership', [OnlineBookingController::class, 'checkMembership']);
});

Route::get('qpay/hook/{id}', [QpayController::class, 'qpayHook']);
Route::get('branches/query', [BranchesController::class, 'index']);
Route::put('branch', [BranchesController::class, 'store']);
Route::get('branch/{id}', [BranchesController::class, 'show']);
Route::post('branch/{id}', [BranchesController::class, 'update']);
Route::delete('branch/{id}', [BranchesController::class, 'destroy']);

Route::post('mobile/get_code', [MobileOtpController::class, 'sendConfirmCode']);
Route::post('mobile/confirm_code', [MobileOtpController::class, 'confirm']);
Route::post('mobile/cancel_event', [MobileOtpController::class, 'cancelEvent']);

Route::post('/send_email', [ApiController::class, 'postFromSite']);
Route::post('/test_check', [QpayController::class, 'testCheck']);


