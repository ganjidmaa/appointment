<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewAppointmentBooked extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'), 'XOLBOOC SYSTEM')
            ->view('emails.new_appointment_booked')
            ->with(['data' => $this->data]);
    }
}
