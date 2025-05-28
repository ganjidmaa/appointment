<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\Settings;
use App\Models\Customer;

use Illuminate\Support\Facades\Log;

class OnlineBookingEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $appointment_id;
    public $appointment;

    public function __construct($appointment_id)
    {
        $appointment = Appointment::find($appointment_id);
        $this->appointment = $appointment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')),
            subject: env('MAIL_FROM_SUBJECT'),
        );
    }

    public function content(): Content
    {
        $settings = Settings::find(1);
        $email_info = $settings->online_booking_email_info;
        $customer = Customer::find($this->appointment->customer_id);
        $customer_name = converCyrToLat($customer->firstname);
        $company = converCyrToLat($settings->company_name);

        $events = $this->appointment->events;
        $app_date = date('Y-m-d', strtotime($events[0]->start_time));
        $app_time = date('H:i', strtotime($events[0]->start_time));
        
        // $app_date = Carbon::parse($events[0]->start_time)->format('Y-m-d');
        // $app_time = Carbon::parse($events[0]->start_time)->format('H:i');
        
        $msg = str_replace('$customer', $customer_name, $email_info);
        // $msg = str_replace('$user', $user, $msg);
        $msg = str_replace('$company', $company, $msg);
        $msg = str_replace('$date', $app_date, $msg);
        $msg = str_replace('$time', $app_time, $msg);
        $msg = str_replace('$tel', $settings->telno, $msg);
        $msg = str_replace('$branch', $this->appointment->branch->name, $msg);

        return new Content(
            view: 'emails.online_booking_email',
            with: [
                'appointment' => $this->appointment,
                'email_info' => $msg,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
