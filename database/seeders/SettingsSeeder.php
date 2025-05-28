<?php

namespace Database\Seeders;
use App\Models\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::create([
            'company_name'          => 'UBPOINT',
            'phone'                 => '',
            'logo'                  => '',
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'slot_duration'         => '30',
            'limit_date_usage'      => now(),
            'monthly_sms_reminder_txt'  => 'Sain baina uu, $customer tanii dawtan ireltiin uzlegiin tsag $date -iin uduriin $time tsagaas tul ta tsagtaa ireerei. $hospital, Utas:$tel',
            'daily_sms_reminder_txt'    => 'Erhem uilchluulegch $customer ta $hospital -t $date $time tsag avsan baina. Manai emnelgiig songon uichluuldegt bayrlalaa. $tel',
            'online_booking_sms_text'   => 'Erhem uilchluulegch $customer tanii $company -t $date $time tsag amjilttai batalgaajilaa. Manai saloniig songon uichluuldegt bayrlalaa. $tel'
        ]);
    }
}
