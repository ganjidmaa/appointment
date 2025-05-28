<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function addressName()
    {
        return $this->address->province->name . ', ' . $this->address->soumDistrict->name . ', ' . $this->address->street;
    }

    public function age() {
        $register = trim($this->registerno);
        if (mb_strlen($register) != 10) {
            return 0;
        }
        $year = mb_substr($register, 2, 2);
        $month = mb_substr($register, 4, 2);
        $day = mb_substr($register, 6, 2);
        $today = Carbon::today();
        $age = 0;
        $bday = "";
        if ($day < "01" || $day > "31") {
            return 0; // Invalid day
        }

        // if ($month[0] >= "2") {
        //     $lyear = "20".$year;
        //     $sd = (intval($month[0]) - 2 + intval($month[1]));
        //     $bday = $lyear."-".strval($sd)."-".$day;
        // } else {
        //     $lyear = "19".$year;
        //     $bday = $lyear."-".$month."-".$day;
        // }
        if ($month[0] >= "2") {
            $lyear = "20" . $year;
            $sd = (intval($month[0]) - 2 + intval($month[1]));
    
            if ($sd < 1 || $sd > 12) {
                return 0; // Ensure valid month
            }
    
            $bday = $lyear . "-" . str_pad($sd, 2, "0", STR_PAD_LEFT) . "-" . $day;
        } else {
            $lyear = "19" . $year;
    
            if ($month < "01" || $month > "12") {
                return 0; // Ensure valid month
            }
    
            $bday = $lyear . "-" . $month . "-" . $day;
        }
        if($bday) {
            $birthDate = Carbon::createFromFormat('Y-m-d', $bday);
            $age = $today->year - $birthDate->year;
            $m = $today->month - $birthDate->month;

            if ($m < 0 || ($m === 0 && $today->day < $birthDate->day)) {
                $age--;
            }
        }

        return $age;
    }

    public function gender() {
        $gender = $this->gender;
        if ($gender == 0) {
            return "эрэгтэй";
        } else if ($gender == 1) {
            return "эмэгтэй";
        }
        return "";
    }

    public function appointmentsWithTrashed() 
    {
        return $this->hasMany(Appointment::class)->withTrashed()->orderBy('event_date', 'desc');
    }

    public function appointments() 
    {
        return $this->hasMany(Appointment::class);
    }

    public function noShowAppointments() 
    {
        return $this->hasMany(Appointment::class)
            ->where('status', '=', 'no_show');
    }

    public function cancelledAppointments() 
    {
        return $this->hasMany(Appointment::class)
            ->withTrashed()
            ->where('status', '=', 'cancelled');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
