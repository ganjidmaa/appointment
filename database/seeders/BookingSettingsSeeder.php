<?php

namespace Database\Seeders;
use App\Models\OnlineBookingSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        OnlineBookingSettings::create([
            'choose_user'           => false,
            'about'                 => 'Бид өөрсдийн эмчилгээ үйлчилгээндээ Европын арьс гоо заслын эмч нарын холбоонд хүлээн зөвшөөрөгдсөн, чанарын өндөр стандарт бүхий бүтээгдэхүүн тоногтөхөөрөмжийг 
                                        албан ёсны эрхтэйгээр нэвтрүүлдэг тул эмчилгээ, үйлчилгээ, бүтээгдэхүүний өндөр жишгийг Монголдоо тогтоон ажиллаж байна.
                                        Манай эмнэлэгийн арьс гоо засал, шүдний эмч нар нь ОУ-ын арьс гоо заслын мэргэших сургалтанд хамрагдсан, туршлагатай, өндөр 
                                        ур чадвартай эмч нараар багаа бүрдүүлсэн.',
            'important_info'        => 'Та цагтаа амжиж ирээгүй тохиолдолд бид дараагийн үйлчлүүлэгчээ авах бөгөөд хэрэв хоцорч ирсэн бол нөхөж үйлчлүүлэх боломжгүйг анхаарна уу.',
            'location'              => 'Улаанбаатар',
            'image'                 => 'salon-image.png'
        ]);
    }
}
