<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QpayInvoice extends Model
{
    use HasFactory;

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
