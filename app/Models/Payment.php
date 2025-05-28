<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public function couponCode()
    {
        return $this->belongsTo(CouponCode::class, 'coupon_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
