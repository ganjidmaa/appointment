<?php

namespace App\Console\Commands;

use App\Http\Controllers\QpayController;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class clearInvalidAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-invalid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $invalid_appointments = Appointment::where('validated', 0)->where('is_online_booking', 1)->whereRaw('created_at < NOW() - INTERVAL 30 MINUTE AND created_at > NOW() - INTERVAL 35 MINUTE')->get();
        $qpay_controller = new QpayController;
        foreach ($invalid_appointments as $key => $appointment) {
            $validated = false;
            foreach ($appointment->qpay_invoices as $key => $invoice) {
                $data = ['invoice_id' => $invoice->invoice_id];
                $request = Request::create('/', 'POST', $data);
                $result = $qpay_controller->qpayCheck($request);
                if($result->getData()->success == 1){
                    $validated = true;
                    $appointment->validated = 1;
                    if($appointment->status == 'booked'){
                        $appointment->status = 'confirmed';
                    }
                    $appointment->save();
                }
            }
            if($validated == false){
                foreach ($appointment->events as $key => $event) {
                    $event->delete();
                }
                $appointment->delete();
            }
        }
        return Command::SUCCESS;
    }
}
