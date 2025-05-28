<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Event;
use App\Models\Customer;
use App\Models\mobileOtp;
use App\Models\SmsHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Settings;

class MobileOtpController extends Controller
{
    public function getConfirmCode()
    {
        $randomid = mt_rand(1000,9999);
        return $randomid;
    }

    public function sendConfirmCode(Request $request)
    {
        $data['statusCode'] = 500;
        $data['status'] = 'Алдаа гарлаа.';

        if(!$request->mobile)
        {
            return response()->json($data);
        }

        $mobile_user = Customer::where('phone', $request->mobile)->orWhere('phone2', $request->mobile)->first();
        if(!$mobile_user)
        {
            $data['statusCode'] = 202;
            $data['status'] = 'Бүртгэлгүй дугаар байна.';

            return response()->json($data);
        }

        $now = Carbon::now();
        $confirm_code = $this->getConfirmCode();

        $mobile_user_tel = mobileOtp::where('mobile', $request->mobile)->first();

        if($mobile_user_tel)
        {
            $last_send_time = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d H:i', strtotime($mobile_user_tel->updated_at)));

            if($now->diffInMinutes($last_send_time) < 5)
            {
                if($mobile_user_tel->counter < 3)
                {
                    $mobile_user_tel->mobile = $request->mobile;
                    $mobile_user_tel->confirm_code = (string)$confirm_code;;
                    $mobile_user_tel->counter = $mobile_user_tel->counter+1;
                    $mobile_user_tel->status = 0;
                    $mobile_user_tel->save();

                    $sms = $this->sendSms( $request->mobile, $confirm_code);

                    $data['statusCode'] = 200;
                    $data['status'] = 'Амжилттай.';
                }
                else
                {
                    $mobile_user_tel->status = 0;
                    $mobile_user_tel->save();

                    $data['statusCode'] = 201;
                    $data['status'] = 'Баталгаажуулах код 5 минутийн дараа дахин авна уу.';
                }
            }
            elseif($now->diffInMinutes($last_send_time) >= 5)
            {
                $mobile_user_tel->mobile = $request->mobile;
                $mobile_user_tel->confirm_code = (string)$confirm_code;;
                $mobile_user_tel->counter = 1;
                $mobile_user_tel->status = 0;
                $mobile_user_tel->save();

                $sms = $this->sendSms( $request->mobile, $confirm_code);

                $data['statusCode'] = 200;
                $data['status'] = 'Амжилттай.';
            }
        }
        else
        {
            $mobile_user_tel = new mobileOtp();
            $mobile_user_tel->mobile = $request->mobile;
            $mobile_user_tel->confirm_code = (string)$confirm_code;;
            $mobile_user_tel->counter = $mobile_user_tel->counter+1;
            $mobile_user_tel->save();

            $sms = $this->sendSms($request->mobile, $confirm_code);

            $data['statusCode'] = 200;
            $data['status'] = 'Амжилттай.';
        }

        return response()->json($data);
    }

    public function confirm(Request $request)
    {
        $data['statusCode'] = 500;
        $data['status'] = 'Алдаа гарлаа.';

        if(!$request->mobile || !$request->get('code'))
        {
            return response()->json($data);
        }

        $mobile_tel = mobileOtp::where('mobile', $request->get('mobile'))->first();
        $user_sent_code = $request->get('code');
        $today = Carbon::now()->format('Y-m-d');

        $appointments = Appointment::select('appointments.*', 'customers.firstname', 'customers.lastname', 'customers.phone')
            ->leftJoin('customers', 'customers.id', 'appointments.customer_id')
            ->where('customers.phone', $request->get('mobile')) 
            ->where('event_date', '>=', $today)
            ->get();

        $apt_datas = [];
        foreach($appointments as $appointment) {
            $events = $appointment->events;
            $event_data = [];
            $start_time = '';
            $end_time = '';
            foreach($events as $key => $event) {

                if($key == 0) {
                    $start_time = $event->start_time;
                }
                if(count($events) == $key+1) {
                    $end_time = $event->end_time;
                }

                $event_data[] = [
                    'service_name' => $event->service ? $event->service->name : 'Үйлчилгээ сонгогдоогүй',
                    'user_name' => $event->user ? $event->user->firstname : ' ',
                ];
            }

            $apt_datas[] = [
                'appointment_id' => $appointment->id,
                'customer_name' => $appointment->lastname.' '.$appointment->firstname,
                'phone' => $appointment->phone,
                'event_date' => $appointment->event_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'events' => $event_data
            ];
        }

        if($mobile_tel)
        {
            if($user_sent_code == $mobile_tel->confirm_code)
            {
                $mobile_tel->status = 1;
                $mobile_tel->save();

                $data['status'] = 'Амжилттай.';
                $data['appointments'] = $apt_datas;
                $data['statusCode'] = 200;
            }
            else
            {
                $data['statusCode'] = 201;
                $data['appointments'] = [];
                $data['status'] = 'Баталгаажуулах код буруу байна.';
            }
        }

        return response()->json($data);
    }

    public function sendSms($mobile, $random_num)
    {
        $settings = Settings::first();
        $msg =  $settings->company_name.' Hereglegchiin batalgaajuulah code: '.$random_num;

        $customer_name = 'mandal';

        $message = new SmsHistory();
        $message->tel = $mobile;
        $message->appointment_id = 0;
        $message->type = 3;
        $message->msg = $msg;
        $message->save();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://mohsms.ubisol.mn/sms/send?mobile=' .$message->tel. '&desc=' . urlencode($msg));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $result_json = json_decode($result);

        $message->status = $result_json->status;
        $message->result = $result_json->result_str;
        $message->save();

        if($message->status == 1) {
            $settings->sms_count = $settings->sms_count + 1;
            $settings->save();
        }
    }

    public function cancelEvent(Request $request)
    {
        $appointment = Appointment::find($request->id);
        if(!$appointment)
        {
            $data['status'] = 'Тухайн цагийн захиалга олдсонгүй.';
            $data['statusCode'] = 201;
            return $data;
        }
        $appointment->status = $request->status;
        $appointment->cancellation_type = 'user_self_online';
        $appointment->save();

        $events = $appointment->events;
        foreach ($events as $event) {
            $event->delete();
        }
        $appointment->delete();

        $data['status'] = 'Амжилттай.';
        $data['statusCode'] = 200;

        return $data;
    }


}
