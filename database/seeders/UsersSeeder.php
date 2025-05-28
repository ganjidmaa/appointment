<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserInfo;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    public function run(Generator $faker)
    {
        $demoUser = User::create([
            'firstname'        => 'Админ',
            'lastname'         => '',
            'email'             => 'admin@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => now(),
            'phone'             => '90008000',
            'status'             => 'active',
            'registerno'        => 'ЦУ90101203',
            'role_id'           => 1
        ]);
        
        DB::table('users')->insert([
            'email'             => 'beautician@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => now(),
            'lastname'          => '.',
            'firstname'         => 'Гоо сайханч',
            'registerno'        => 'АА99999999',
            'phone'             => '99999999',
            'status'            => 'active',
            'role_id'           => 3
        ]);
    }
}
