<?php

namespace App\Models;

use App\Notifications\EventsChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class)->withTrashed();
    }
    protected static function booted(): void
    {
        static::saved(function (Event $event) {
            $data = [];
            $text_color = '#F5F8FA';
            $text_dark_color = '#3F4254';

            $event_colors = [
                '#E4E6EF' => 'booked',
                '#FFC700' => 'confirmed',
                '#009EF7' => 'showed',
                '#7239EA' => 'started',
                '#F1416B' => 'no_show',
                '#50CD89' => 'completed',
                '#708090' => 'time_block'
            ];
            $status_name = in_array($event->appointment->status, ['part_paid', 'unpaid']) ? 'completed' : $event->appointment->status;
            $data['id'] = $event->event_id;
            $data['appointment_id'] = $event->appointment_id;
            $data['title'] = $event->customer ? $event->customer->firstname : 'Ажиллахгүй цаг';
            $data['cust_phone'] = $event->customer ? $event->customer->phone : '';
            $data['service_name'] = $event->service_id > 0 ? $event->service->name : '';
            $data['start'] = $event->start_time;
            $data['end'] = $event->end_time;
            $data['status'] = $status_name;
            $data['resourceId'] = $event->event_user_id;
            $data['textColor'] = $status_name === 'booked' ? $text_dark_color : $text_color;
            $data['color'] = array_search($status_name, $event_colors);
            $data['validated'] = $event->appointment->validated == 1 ? true : false;
            $data['className'] = 'fw-bolder';
            $data['editable'] = $status_name == 'completed' ? false : true;
            $data['display'] = $event->event_branch_id;
            $data['event_date'] = $event->appointment->event_date;
            $data['branch_id'] = $event->appointment->branch_id;

            $users = request()->user() ? User::whereNot('id', request()->user()->id)->get() : [];
            foreach ($users as $user) {
                $user->notify(new EventsChanged($data));
            }
        });
    }
}
