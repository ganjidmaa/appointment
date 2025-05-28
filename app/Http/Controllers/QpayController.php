<?php

namespace App\Http\Controllers;

use App\Events\QpayPaid;
use App\Jobs\OnlineBookingSms;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\QpayInvoice;
use App\Models\MembershipType;
use App\Models\InvoiceDiscount;
use App\Models\Settings;
use App\Models\CouponCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Mail\OnlineBookingEmail;
use Illuminate\Support\Facades\Mail;

class QpayController extends Controller
{
    public function echoAuth(Request $request)
    {
        $digest = 'sha256';
        $secret = env('PUSHER_APP_SECRET');
        $string_to_sign = $request->socket_id . ':' . $request->channel_name;

        $signature = hash_hmac($digest, $string_to_sign, $secret);

        $auth = env('PUSHER_APP_KEY').":$signature";
        // Return the authentication response
        return response()->json([
            'auth' => $auth,
        ]);
    }
    
    public function createInvoice(Request $request)
    {
        $client = new Client([
            'base_uri' => 'https://quickqr.qpay.mn',
        ]);

        $settings = Settings::find(1);
        if ($settings->use_qpay == 0) {
            return response()->json(null);
        }
        $qpay_invoice = new QpayInvoice;
        $qpay_invoice->amount = $request->amount;
        $qpay_invoice->desc = $request->desc;
        $qpay_invoice->appointment_id = $request->appointment_id;
        $qpay_invoice->save();

        $send_data = [
            "merchant_id" => $settings->qpay_merchant_id,
            // "branch_code" => $request->branch_name,
            "amount" => (int) $request->amount,
            "currency" => "MNT",
            "customer_name" => "TDB",
            "customer_logo" => "",
            "callback_url" => env('APP_URL') . '/api/qpay/hook/' . $qpay_invoice->id,
            "description" => $request->desc,
            "mcc_code" => $settings->mcc_code,
            "bank_accounts" => [
                [
                    "account_bank_code" => $settings->bank_code,
                    "account_number" => $settings->account_number,
                    "account_name" => $settings->account_holder,
                    "is_default" => true
                ]
            ]
        ];

        $response = $client->request('POST', '/v2/invoice', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings->qpay_token,
            ],
            'body' => json_encode($send_data),
            'http_errors' => true,
        ]);
        $result = json_decode($response->getBody());
        $qpay_invoice->invoice_id = $result->id;
        $qpay_invoice->save();
        $return_data = [
            'qr_image' => $result->qr_image,
            'invoice_id' => $result->id,
        ];
        return $return_data;
    }

    public function qpayHook($id)
    {
        $qpay_invoice = QpayInvoice::find($id);
        $send_data = [
            "invoice_id" => $qpay_invoice->invoice_id,
        ];
        $client = new Client([
            'base_uri' => 'https://quickqr.qpay.mn',
        ]);
        $settings = Settings::find(1);
        $response = $client->request("POST", '/v2/payment/check', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings->qpay_token,
            ],
            'body' => json_encode($send_data)
        ]);
        $result = json_decode($response->getBody());
        Log::info('Hook progress____________');
        Log::info($response->getBody());
        if ($result->invoice_status == 'PAID') {
            $email = '';
            if ($qpay_invoice->is_success == 0) {
                $success = false;
                try {
                    DB::beginTransaction();
                    $appointment = $qpay_invoice->appointment;
                    $is_dispatch = $this->createInvoices($appointment, $qpay_invoice);
                    $success = true;
                } catch (Exception $error) {
                    Log::info($error);
                }
                if ($success = true) {
                    DB::commit();
                }
            }

            if($is_dispatch)
                $this->handleEvents($qpay_invoice, $appointment);
            
            $qpay_invoice->is_success = 1;
            $qpay_invoice->save();
        }
        return response()->json([]);
    }

    public function qpayCheck(Request $request)
    {
        $qpay_invoice = QpayInvoice::where('invoice_id', $request->invoice_id)->first();
        $send_data = [
            "invoice_id" => $request->invoice_id,
        ];
        $client = new Client([
            'base_uri' => 'https://quickqr.qpay.mn',
        ]);
        $settings = Settings::find(1);
        $response = $client->request("POST", '/v2/payment/check', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings->qpay_token,
            ],
            'body' => json_encode($send_data),
            'http_errors' => false,
        ]);
        $result = json_decode($response->getBody());
        Log::info('Check progress ____________');
        Log::info($response->getBody());

        $appointment = $qpay_invoice->appointment;
        if (isset($result->invoice_status) && $result->invoice_status == 'PAID') {
            $email = '';
            if ($qpay_invoice->is_success == 0) {
                $success = false;
                try {
                    DB::beginTransaction();
                    $is_dispatch = $this->createInvoices($appointment, $qpay_invoice);
                    $success = true;
                } catch (Exception $error) {
                    Log::info($error);
                }
                if ($success = true) {
                    DB::commit();
                }
            }
            
            if($is_dispatch)
                $this->handleEvents($qpay_invoice, $appointment);

            $qpay_invoice->is_success = 1;
            $qpay_invoice->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function handleEvents($qpay_invoice=null, $appointment) {     
        if($qpay_invoice)
            QpayPaid::dispatch($qpay_invoice->invoice_id);

        $email = $appointment->customer->email;
        
        if($appointment->is_online_booking == 1) {
            OnlineBookingSms::dispatch($appointment->id)->delay(now()->addMinutes(1));
            $email && Mail::to($email)->send(new OnlineBookingEmail($appointment->id));
        }
    }

    public function createInvoices($appointment, $qpay_invoice) {
        if ($appointment->invoice) {
            $is_dispatch = false;
            $main_invoice = $appointment->invoice;
        } else {
            $is_dispatch = true;
            $payment = $appointment->events->sum('price');
            $main_invoice = new Invoice();
            $main_invoice->customer_id = $appointment->customer_id;
            $main_invoice->user_id = 0;
            $main_invoice->appointment_id = $appointment->id;
            $main_invoice->payment = $payment;
            $main_invoice->payable = ($payment - $qpay_invoice->amount);
            $main_invoice->paid = $qpay_invoice->amount;
            $main_invoice->discount_amount = 0;
            $main_invoice->state = 'unpaid';
            $main_invoice->save();
        
            $payment = new Payment();
            $payment->invoice_id = $main_invoice->id;
            $payment->user_id = 0;
            $payment->type = 'qpay';
            $payment->amount = $qpay_invoice->amount;
            $payment->bank_account_id = 0;
            $payment->coupon_id = 0;
            $payment->desc = $qpay_invoice->desc;
            $payment->save();
            
            $appointment->validated = true;
            if($appointment->status == 'booked'){
                $appointment->status = 'confirmed';
            }

            $appointment->save();
        } 
        return $is_dispatch;
    }

    public function createCouponPayment(Request $request) {
        $success = false;
        $response['data'] = [];
        $response['payload'] = 201;
        
        try {
            DB::beginTransaction();
            $appointment_id = $request->appointment_id;
            $coupon_amount = $request->coupon_amount;
            $coupon_id = $request->coupon_id;
            $code = $request->code;
            $is_fully_covered_by_coupon = false;

            $coupon_code = CouponCode::where('code', $code)->where('coupon_id', $coupon_id)->first();
            if (!$coupon_code || $coupon_code->status != 'valid') {
                $response['data'] = [];
                $response['payload'] = 201;
                return response($response);
            }

            $appointment = Appointment::find($appointment_id);
            if ($appointment->invoice) {
                $main_invoice = $appointment->invoice;
                $main_invoice->paid = $main_invoice->paid + $coupon_amount;
                $main_invoice->payable = $main_invoice->payable - $coupon_amount;
                $is_dispatch = false;
            } else {
                $is_dispatch = true;

                $payment = $appointment->events->sum('price');
                $main_invoice = new Invoice();
                $main_invoice->customer_id = $appointment->customer_id;
                $main_invoice->user_id = 0;
                $main_invoice->appointment_id = $appointment->id;
                $main_invoice->payment = $payment;
                $main_invoice->payable = $payment - $coupon_amount;
                $main_invoice->paid = $coupon_amount;
                $main_invoice->discount_amount = 0;
                $main_invoice->state = 'unpaid';
            } 
            $main_invoice->save();

            $payment = new Payment();
            $payment->invoice_id = $main_invoice->id;
            $payment->user_id = 0;
            $payment->type = 'coupon';
            $payment->amount = $coupon_amount;
            $payment->bank_account_id = 0;
            $payment->coupon_id = $coupon_code->id;
            $payment->desc = '';
            $payment->save();

            $total_redeemed = $coupon_code->redeemed + $coupon_amount;
            $coupon_code->redeemed = $total_redeemed;
            $coupon_code->usage_count = $coupon_code->usage_count + 1;
            if(($coupon_code->type == 'personal' && $total_redeemed == $coupon_code->value) || ($coupon_code->type == 'mass' && $coupon_code->usage_count >= $coupon_code->coupon->limit_number)) 
                $coupon_code->status = 'redeemed';
            $coupon_code->save();
            
            $appointment->validated = true;
            if($appointment->status == 'booked') {
                $appointment->status = 'confirmed';
            }
            $appointment->save();

            $success = true;
            if($is_dispatch)
                $this->handleEvents(null, $appointment);
            $response['data'] = $appointment;
            $response['payload'] = 200;
        } catch (Exception $error) {
            Log::info($error);
        }
        if ($success = true) {
            DB::commit();
        }

        return response($response);
    }


    public function testCheck() {
        $appointment = Appointment::find(77);
        $email = 'doganjaa@gmail.com';
        $email && Mail::to($email)->send(new OnlineBookingEmail($appointment->id));
    }
}
