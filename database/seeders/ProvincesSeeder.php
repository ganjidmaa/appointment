<?php

namespace Database\Seeders;

use App\Models\Province;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use DB;

class ProvincesSeeder extends Seeder
{
    public function run(Generator $faker)
    {
        DB::insert(
            "INSERT INTO provinces (id, name) VALUES
            (1, 'Улаанбаатар'),
            (2, 'Архангай'),
            (3, 'Баянхонгор'),
            (4, 'Баян-Өлгий'),
            (5, 'Булган'),
            (6, 'Дархан-Уул'),
            (7, 'Дорнод'),
            (8, 'Дорноговь'),
            (9, 'Дундговь'),
            (10, 'Завхан'),
            (11, 'Говь-Алтай'),
            (12, 'Говьсүмбэр'),
            (13, 'Хэнтий'),
            (14, 'Ховд'),
            (15, 'Хөвсгөл'),
            (16, 'Өмнөговь'),
            (17, 'Орхон'),
            (18, 'Өвөрхангай'),
            (19, 'Сэлэнгэ'),
            (20, 'Сүхбаатар'),
            (21, 'Төв'),
            (22, 'Увс');"
            );
    }

}
