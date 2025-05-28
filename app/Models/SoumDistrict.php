<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoumDistrict extends Model
{
    use HasFactory;

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public static function getDistricts()
    {
        $soum_districts = self::select('id', 'name', 'province_id')->orderBy('province_id', 'asc')->get();
        $prev_id = 0;
        $datas = [];
        $data = [];
        foreach ($soum_districts as $soum_district) {
            $province_id = $soum_district->province_id;

            if ($province_id != $prev_id) {
                $prev_id ? $datas[] = $data : null;
                $data = [];
                $prev_id = $province_id;
                $data['label'] = $soum_district->province->name;
                $data['value'] = $province_id;
            }

            $option['value'] = $soum_district->id;
            $option['label'] = $soum_district->name;

            $data['options'][] = $option;
        }

        return $datas;
    }
}
