<?php

namespace Database\Seeders;
use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Branch::create([
            'name'                  => 'Үндсэн салбар',
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'slot_duration'         => '30',
        ]);
    }
}
