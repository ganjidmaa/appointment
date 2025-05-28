<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Settings;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class SettingsController extends Controller
{   
    //CALENDAR VIEW TYPE VALUES
    //calendar_view_type: 'standart', 'timeline' 
    public function show()
    {
        $this->authorize('update', Settings::class);

        $settings = Settings::find(1);
        $settings_arr = $settings->attributesToArray();

        $path = env('APP_URL') . '/'.env("APP_PUBLIC_STORAGE");
        $image = [
            'name' => $settings->logo,
            'path' => $settings->logo,
            'preview' => $path . '/' . $settings->logo,
        ];

        $settings_data = [
            ...$settings_arr,
            'logo' => $settings->logo ? [$image] : [],
            'logo_url' => $settings->logo ? $path . '/' . $settings->logo : '',
            'has_service_type' => $settings->has_service_type == 1 ? true : false,
            'has_branch' => $settings->has_branch == 1 ? true : false,
            'appointment_email_to_user' => $settings->appointment_email_to_user == 1 ? true : false,
        ];
        $status = 200;

        $response['data'] = $settings_data;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function showPublic()
    {
        $settings = Settings::find(1);
        $settings_arr = $settings->attributesToArray();

        $path = env('APP_URL') . '/'.env("APP_PUBLIC_STORAGE");
        $image = [
            'name' => $settings->logo,
            'path' => $settings->logo,
            'preview' => $path . '/' . $settings->logo,
        ];

        $settings_data = [
            ...$settings_arr,
            'logo' => $settings->logo ? [$image] : [],
            'logo_url' => $settings->logo ? $path . '/' . $settings->logo : '',
            'has_service_type' => $settings->has_service_type == 1 ? true : false,
            'has_branch' => $settings->has_branch == 1 ? true : false,
            'appointment_email_to_user' => $settings->appointment_email_to_user == 1 ? true : false,
        ];
        unset($settings_data['qpay_token']);
        unset($settings_data['qpay_merchant_id']);
        $payment_methods_data = [];
        $payment_methods = PaymentMethod::select('id', 'name', 'slug', 'active')->get();
        foreach($payment_methods as $payment_method) {
            if($payment_method->slug == 'qpay' && $settings->use_qpay == 0){
                continue;
            }
            $payment_method_arr = $payment_method->attributesToArray();
            $payment_method_arr = [
                ...$payment_method_arr,
                'active' => $payment_method->active == 1 ? true : false,
            ];
            
            $payment_methods_data[] = $payment_method_arr;
        }
        $response['payment_methods'] = $payment_methods_data;
        $response['settings'] = $settings_data;

        $status = 200;


        return response($response);
    }

    public function update(Request $request)
    {
        $this->authorize('update', Settings::class);

        $settings = Settings::find(1);
        if(!$settings)
            $settings = new Settings();
        $settings->company_name = $request->settings['company_name'];
        $settings->phone = $request->settings['phone'];
        $settings->start_time = $request->settings['start_time'];
        $settings->end_time = $request->settings['end_time'];
        $settings->lunch_start_time = $request->settings['lunch_start_time'];
        $settings->lunch_end_time = $request->settings['lunch_end_time'];
        $settings->slot_duration = $request->settings['slot_duration'] ? $request->settings['slot_duration'] : '30';
        $settings->limit_date_usage = $request->settings['limit_date_usage'];
        $settings->has_branch = $request->settings['has_branch'];
        $settings->default_duration = $request->settings['default_duration'];
        $settings->email = $request->settings['email'];
        $settings->fb_url = $request->settings['fb_url'];
        $settings->insta_url = $request->settings['insta_url'];
        $settings->appointment_email_to_user = $request->settings['appointment_email_to_user'];
        $settings->address = $request->settings['address'];
        $settings->business_days = $request->settings['business_days'];
        $settings->monthly_sms_reminder_txt = $request->settings['monthly_sms_reminder_txt'];
        $settings->daily_sms_reminder_txt = $request->settings['daily_sms_reminder_txt'];
        $settings->monthly_sms_reminder_months = $request->settings['monthly_sms_reminder_months'];
        $settings->daily_sms_reminder_minutes = $request->settings['daily_sms_reminder_minutes'];
        $settings->online_booking_sms_text = $request->settings['online_booking_sms_text'];
        $settings->online_booking_email_info = $request->settings['online_booking_email_info'];
        $settings->save();

        $settings_arr = $settings->attributesToArray();
        $settings_data = [
            ...$settings_arr,
            'has_service_type' => $settings->has_service_type == 1 ? true : false,
            'has_branch' => $settings->has_branch == 1 ? true : false,
        ];

        if ($request->settings['file']) {
            $image_name = $this->base64ToFile($request->settings['file']);
            $settings->logo = $image_name;
            $settings->save();
        }

        $methods = $request->paymentMethods;
        foreach($methods as $method) {
            $payment_method = PaymentMethod::find($method['id']);
            $payment_method->active = $method['active'];
            $payment_method->save();
        }

        $status = 200;

        $response['data'] = $settings;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function base64ToFile($encoded_file)
    {
        $image_64 = $encoded_file;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $image_name = Str::random(10) . '.' . $extension;
        Storage::disk('public')->put($image_name, base64_decode($image));

        return $image_name;
    }

}
