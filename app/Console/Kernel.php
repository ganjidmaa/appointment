<?php

namespace App\Console;

use App\Models\OnlineBookingSettings;
use App\Models\Settings;

use Illuminate\Console\Scheduling\Schedule;
use Gecche\Multidomain\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('couponValidation:daily')->daily();
        $settings = Settings::find(1);
        $online_settings = OnlineBookingSettings::find(1);
        if($online_settings->choose_autoDiscard == 1 && $online_settings->choose_qpay == 1){
            $schedule->command('app:clear-invalid')->cron('* * * * *');
        }
        if($settings->use_qpay == 1){
            $schedule->command('app:qpay-token-refresh')->daily();
        }
        if($settings->sms_send ==  1){
            if($settings->daily_sms_reminder_minutes > 0)
                $schedule->command('daily:sendsms')->cron('* * * * *')->withoutOverlapping();

            if($settings->monthly_sms_reminder_months > 0)
                $schedule->command('monthly:sendsms')->dailyAt('2:00');
        }
    }


    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
