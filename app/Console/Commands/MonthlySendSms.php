<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Settings;
use App\Models\SmsHistory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlySendSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:sendsms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send sms';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('monthly sms');
        $settings = Settings::find(1);
        $msg = '';

        if($settings->sms_count < $settings->sms_limit) {
            $monthly_sms_remind_months = $settings->monthly_sms_reminder_months;
            $monthly_sms_remind_txt = $settings->monthly_sms_reminder_txt;

            if($monthly_sms_remind_months > 0) {
                $today = date("Y-m-d H:i:s", strtotime('+8 hours'));

                $appointments = DB::table('appointments')
                    ->select('appointments.id', 'appointments.customer_id', 'eventss.appointment_start_time','appointments.branch_id', 'branches.name AS branch_name')
                    ->leftJoin('branches', 'branches.id', '=', 'appointments.branch_id')
                    ->leftJoin(DB::raw('(SELECT MIN(start_time) AS appointment_start_time, appointment_id FROM events GROUP BY appointment_id) AS eventss'), 
                        'eventss.appointment_id', '=', 'appointments.id')
                    ->whereIn('appointments.status', ['booked', 'confirmed'])
                    ->where('appointments.validated', 1)
                    ->whereRaw('TIMESTAMPDIFF(DAY, ?, eventss.appointment_start_time) = ?', [$today, $monthly_sms_remind_months])
                    ->get();

                foreach ($appointments as $appointment)
                {
                    if($settings->sms_count >= $settings->sms_limit) {
                        Log::info('Sms limit finished !!!');
                        break;
                    }

                    $customer = Customer::find($appointment->customer_id);

                    $customer_name = $this->converCyrToLat($customer->firstname);
                    //$user = User::find($appointment->user_id);
                    //$doctor = $this->converCyrToLat($user->firstname);
                    $hospital = $this->converCyrToLat($settings->company_name);

                    $app_date = date('m/d', strtotime($appointment->appointment_start_time));
                    $app_time = date('H:i', strtotime($appointment->appointment_start_time));

                    $msg = str_replace('$customer', $customer_name, $daily_sms_temp);
                    //$msg = str_replace('$doctor', $doctor, $msg);
                    $msg = str_replace('$hospital', $hospital, $msg);
                    $msg = str_replace('$date', $app_date, $msg);
                    $msg = str_replace('$time', $app_time, $msg);
                    $msg = str_replace('$tel', $settings->telno, $msg);
                    $msg = str_replace('$branch', $appointment->branch_name, $msg);

                    $message = new SmsHistory();
                    $message->tel = $customer->phone;
                    $message->appointment_id = $appointment->id;
                    $message->type = 0;
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
        return Command::SUCCESS;
    }

}
