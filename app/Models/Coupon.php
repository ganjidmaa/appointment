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

   
}
