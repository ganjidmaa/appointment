<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Coupon extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => 'boolean',
        'is_all_services' => 'boolean',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? env("APP_URL") . '/' . env("APP_PUBLIC_STORAGE") . '/user_images/' . $value : '',
        );
    }

}
