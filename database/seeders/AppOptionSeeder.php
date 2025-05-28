<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Service::create([
            'name' => 'Захиалгын онош',
            'category_id' => 0,
            'is_category' => 1,
            'is_app_option' => 1,

            'price' => '',
            'status' => 1,
            'duration' => 0,
        ]);
        Service::create([
            'name' => 'Үзлэг',
            'category_id' => 1,
            'is_category' => 0,
            'is_app_option' => 1,
            'price' => '',
            'status' => 1,
            'duration' => 0,
        ]);
    }
}
