<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDiscount extends Model
{
    use HasFactory, SoftDeletes;

    public function membershipType() {
        return $this->belongsTo(MembershipType::class)->withTrashed();
    }
}
