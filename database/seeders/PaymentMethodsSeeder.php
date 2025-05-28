<?php

namespace Database\Seeders;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentMethod::insert([
            ['name' => 'Карт', 'slug' => 'card', 'active' => true],
            ['name' => 'QPAY', 'slug' => 'qpay', 'active' => true],
            ['name' => 'Мобайл', 'slug' => 'mobile', 'active' => true],
            ['name' => 'Бэлэн', 'slug' => 'cash', 'active' => true],
            ['name' => 'Бартер', 'slug' => 'barter', 'active' => true],
            ['name' => 'Купон', 'slug' => 'coupon', 'active' => true],
            ['name' => 'Хөнгөлөлт', 'slug' => 'discount', 'active' => false],
            ['name' => 'Гишүүнчлэл', 'slug' => 'membership', 'active' => false],
        ]);
    }
}
