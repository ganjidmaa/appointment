<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCondition extends Model
{
    use HasFactory;
    protected $fillable = ['farsightedness', 'Ph', 'with_glasses','nearsightedness','air_tonometer','CCT','go_scope','eye_movement','refraction','cranial_angle','color','pathological_discharge','tear_path','eye_recesses','eyelids','mucus','sclera','cornea','sought_camera','rainbow_cover','pupil','RAPD','crystal','glass','eye_disk','CDR','A_V','S_H','K_W','S_S','reticulated' ,'yallow_dot','outside','distance_R','distance_L','near_R','near_L'];
    protected $attributes = [
        'eye_movement' => '{
            "OD": "Full",
            "OS": "Full"
        }',
        'cranial_angle' => '{
            "OD": "Orthophoria",
            "OS": "Orthophoria"
        }',
        'color' => '{
            "OD": "Trichromat",
            "OS": "Trichromat"
        }',
        'pathological_discharge' => '{
            "OD": "None",
            "OS": "None"
        }',
        'tear_path' => '{
            "OD": "Free",
            "OS": "Free"
        }',
        'eye_recesses' => '{
            "OD": "WNL",
            "OS": "WNL"
        }',
        'eyelids' => '{
            "OD": "WNL",
            "OS": "WNL"
        }',
        'mucus' => '{
            "OD": "Quiet",
            "OS": "Quiet"
        }',
        'sclera' => '{
            "OD": "Quiet",
            "OS": "Quiet"
        }',
        'cornea' => '{
            "OD": "Clear",
            "OS": "Clear"
        }',
        'sought_camera' => '{
            "OD": "Deep / Quiet",
            "OS": "Deep / Quiet"
        }',
        'rainbow_cover' => '{
            "OD": "WNL",
            "OS": "WNL"
        }',
        'pupil' => '{
            "OD": "6-3mm RRR",
            "OS": "6-3mm RRR"
        }',
        'RAPD' => '{
            "OD": "-)VE",
            "OS": "-)VE"
        }',
        'crystal' => '{
            "OD": "Clear",
            "OS": "Clear"
        }',
        'glass' => '{
            "OD": "Clear",
            "OS": "Clear"
        }',
        'CDR' => '{
            "OD": "0.3x0.4",
            "OS": "0.3x0.4"
        }',
        'A_V' => '{
            "OD": "2:3",
            "OS": "2:3"
        }',
        'reticulated' => '{
            "OD": "Attached",
            "OS": "Attached"
        }',
        'yallow_dot' => '{
            "OD": "FR + pink sharp",
            "OS": "FR + pink sharp"
        }',
        'outside' => '{
            "OD": "Normal",
            "OS": "Normal"
        }',
    ];
}
