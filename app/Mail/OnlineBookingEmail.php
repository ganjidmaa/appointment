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
        $user = User::find($this->appointment->user_id);
        $doctor = $this->converCyrToLat($user->firstname);
        $hospital = $this->converCyrToLat($settings->company_name);
        
        // $app_date = date('m/d', strtotime($this->appointment->appointment_start_time));
        // $app_time = date('H:i', strtotime($this->appointment->appointment_start_time));
        $app_date = Carbon::parse($this->appointment->to_date)->format('Y-m-d');
        $app_time = Carbon::parse($this->appointment->to_date)->format('H:i');
        
        $msg = str_replace('$customer', $customer_name, $email_info);
        $msg = str_replace('$doctor', $doctor, $msg);
        $msg = str_replace('$hospital', $hospital, $msg);
        $msg = str_replace('$date', $app_date, $msg);
        $msg = str_replace('$time', $app_time, $msg);
        $msg = str_replace('$tel', $settings->phone, $msg);
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
