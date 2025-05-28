<?php

namespace App\Http\Controllers;

use App\Models\OnlineBookingSettings;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineBookingSettingsController extends Controller
{
    public function show()
    {
        $booking_settings = OnlineBookingSettings::find(1);
        $settings_arr = $booking_settings->attributesToArray();

        $path = env("APP_URL") . '/'.env("APP_PUBLIC_STORAGE");
        $image_names = json_decode($booking_settings->image);
        $image = [];
        if($image_names) {
            foreach ($image_names as $key => $image_name) {
                $image[] = [
                    'name' => $image_name,
                    'path' => $image_name,
                    'preview' => $path . '/' . $image_name,
                ];
            }
        }
        else {
            $image[] = [
                'name' => $booking_settings->image,
                'path' => $booking_settings->image,
                'preview' => $path . '/' . $booking_settings->image,
            ];
        }

        $formatted_data = [
            ...$settings_arr,
            'choose_user' => $booking_settings->choose_user === 1,
            'choose_qpay' => $booking_settings->choose_qpay === 1,
            'group_booking' => $booking_settings->group_booking === 1,
            'choose_autoDiscard' =>$booking_settings->choose_autoDiscard === 1,
            'image' => $booking_settings->image ? $image : [],
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
        $booking_settings->group_booking = $request->group_booking;
        $booking_settings->group_booking_limit = $request->group_booking_limit;
        $booking_settings->theme_color = $request->theme_color;
        $booking_settings->theme_selection = $request->theme_selection;
        $booking_settings->save();

        if ($request->file) {
            $image_names = $this->base64ToFile($request->file);
            // $booking_settings->image = $image_name;
            $booking_settings->image = json_encode($image_names);
            $booking_settings->save();
        }

        $status = 200;

        $response['data'] = $booking_settings;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function base64ToFile($encoded_files)
    {
        $file_names = [];
        foreach ($encoded_files as $key => $encoded_file) {
            $image_64 = $encoded_file;
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $image_name = Str::random(10) . '.' . $extension;
            Storage::disk('public')->put($image_name, base64_decode($image));

            $file_names[] = $image_name;
        }
        return $file_names;
    }
}
