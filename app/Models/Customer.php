<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public function address()
    {
        return $this->belongsTo(Address::class);
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
