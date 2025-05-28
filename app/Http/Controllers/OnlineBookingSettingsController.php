<?php

namespace App\Http\Controllers;

use App\Models\OnlineBookingSettings;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OnlineBookingSettingsController extends Controller
{
    public function show()
    {
        $booking_settings = OnlineBookingSettings::find(1);
        $settings_arr = $booking_settings->attributesToArray();

        $path = env("APP_URL") . '/'.env("APP_PUBLIC_STORAGE");
        $image = [
            'name' => $booking_settings->image,
            'path' => $booking_settings->image,
            'preview' => $path . '/' . $booking_settings->image,
        ];

        $formatted_data = [
            ...$settings_arr,
            'choose_user' => $booking_settings->choose_user == 1 ? true : false,
            'choose_qpay' => $booking_settings->choose_qpay == 1 ? true : false,
            'choose_autoDiscard' =>$booking_settings->choose_autoDiscard == 1 ? true : false,
            'image' => $booking_settings->image ? [$image] : [],
            'image_url' => $booking_settings->image ? $path . '/' . $booking_settings->image : '',
        ];

        $status = 200;

        $response['data'] = $formatted_data;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function update(Request $request)
    {
        $this->authorize('update', Settings::class);

        $booking_settings = OnlineBookingSettings::find(1);
        if(!$booking_settings)
            $booking_settings = new OnlineBookingSettings();
        $booking_settings->choose_user = $request->choose_user;
        $booking_settings->choose_qpay = $request->choose_qpay;
        $booking_settings->choose_autoDiscard = $request->choose_autoDiscard;
        $booking_settings->validate_amount = $request->validate_amount;
        $booking_settings->about = $request->about;
        $booking_settings->important_info = $request->important_info;
        $booking_settings->location = $request->location;
        $booking_settings->save();

        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $booking_settings->image = $image_name;
            $booking_settings->save();
        }

        $status = 200;

        $response['data'] = $booking_settings;
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
