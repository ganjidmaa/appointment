<?php

namespace Database\Seeders;

use App\Models\Role;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = DB::table('roles')->insert([
            ['name' => 'administrator'],
            ['name' => 'reception'],
            ['name' => 'user'],
            ['name' => 'accountant'],
        ]);
    }
}
