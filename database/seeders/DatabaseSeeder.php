<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            ProvincesSeeder::class,
            SoumDistrictsSeeder::class,
            RolesSeeder::class,
            SettingsSeeder::class,
            BookingSettingsSeeder::class,
            ServiceTypesSeeder::class,
            BranchSeeder::class,
            PaymentMethodsSeeder::class,
            AppOptionSeeder::class,
        ]);
    }
}
