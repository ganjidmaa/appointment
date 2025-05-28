<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCondition extends Model
{
    use HasFactory;
    protected $fillable = ['farsightedness', 'Ph', 'with_glasses','nearsightedness','air_tonometer','CCT','go_scope','eye_movement','refraction','cranial_angle','color','pathological_discharge','tear_path','eye_recesses','eyelids','mucus','sclera','cornea','sought_camera','rainbow_cover','pupil','RAPD','crystal','glass','eye_disk','CDR','A_V','S_H','K_W','S_S','reticulated' ,'yallow_dot','outside','distance_R','distance_L','near_R','near_L'];
}
