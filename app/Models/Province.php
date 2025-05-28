<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    public static function getProvinces()
    {
        $provinces = self::select('id as value', 'name as label')->get();

        return $provinces;
    }
}
