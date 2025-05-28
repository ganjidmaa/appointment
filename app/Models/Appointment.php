<?php

namespace App\Models;

use App\Notifications\NotificationCreate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;
    protected static function booted(): void
    {
        static::created(function (Appointment $appointment) {
            $context = $appointment->event_date;
            $users = request()->user() ? User::whereNot('id', request()->user()->id)->get() : [];
            foreach ($users as $user) {
                $user->notify(new NotificationCreate($context));
            }
        });
    }
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    
    public function eventsWithTrashed()
    {
        return $this->hasMany(Event::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'appointment_id')->where('state', '!=', 'voided');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function statusName($status_prop)
    {
        $status_name = '';
        foreach(config('global.statuses') as $status) {
            foreach($status['value'] as $value) {
                if($value == $status_prop)
                    $status_name = $status['name']; 
            }
        }

        return $status_name;
    }

    public function qpay_invoices()
    {
        return $this->hasMany(QpayInvoice::class);
    }

    public function discountInvoice()
    {
        return $this->hasOne(InvoiceDiscount::class, 'invoice_id', 'id');
    }

}
