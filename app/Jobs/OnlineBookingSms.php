<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Settings;
use App\Models\SmsHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class OnlineBookingSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment_id;

    public function __construct($appointment_id)
    {
        $this->appointment_id = $appointment_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('online booking sms');
        $settings = Settings::find(1);
        $msg = '';

        if($settings->sms_count < $settings->sms_limit) {
            $online_booking_sms_text = $settings->online_booking_sms_text;
            $appointment = DB::table('appointments')
                ->select('appointments.id', 'appointments.customer_id', 'eventss.appointment_start_time','appointments.branch_id', 'branches.name AS branch_name')
                ->leftJoin('branches', 'branches.id', '=', 'appointments.branch_id')
                ->leftJoin(DB::raw('(SELECT MIN(start_time) AS appointment_start_time, appointment_id FROM events GROUP BY appointment_id) AS eventss'), 
                    'eventss.appointment_id', '=', 'appointments.id')
                ->whereIn('appointments.status', ['booked', 'confirmed'])
                ->where('appointments.validated', 1)->where('appointments.id', $this->appointment_id)
                ->first();

            if($online_booking_sms_text != '') {
                $customer = Customer::find($appointment->customer_id);
                $customer_name = converCyrToLat($customer->firstname);
                // $user = User::find($appointment->event->user_id);
                // $user = converCyrToLat($user->firstname);
                $company = converCyrToLat($settings->company_name);
                
                $app_date = date('m/d', strtotime($appointment->appointment_start_time));
                $app_time = date('H:i', strtotime($appointment->appointment_start_time));
                
                $msg = str_replace('$customer', $customer_name, $online_booking_sms_text);
                // $msg = str_replace('$user', $user, $msg);
                $msg = str_replace('$company', $company, $msg);
                $msg = str_replace('$date', $app_date, $msg);
                $msg = str_replace('$time', $app_time, $msg);
                $msg = str_replace('$tel', $settings->telno, $msg);
                $msg = str_replace('$branch', $appointment->branch_name, $msg);

                
                $message = new SmsHistory();
                $message->tel = $customer->phone;
                $message->appointment_id = $appointment->id;
                $message->type = 1;
                $message->msg = $msg;
                $message->save();

                $result_json = sendSmsApi($customer->phone, $msg);
                $message->status = $result_json->status;
                $message->result = $result_json->result_str;
                $message->save();

                if($message->status == 1) {
                    $settings->sms_count = $settings->sms_count + 1;
                    $settings->save();
                }
            }
        }   
    }
}
