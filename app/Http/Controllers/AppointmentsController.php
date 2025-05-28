<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Event;
use App\Models\Customer;
use App\Models\Resource;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\CouponCode;
use App\Models\CouponService;
use App\Models\Settings;
use App\Models\Branch;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\InvoiceDiscount;
use App\Mail\NewAppointmentBooked;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;

class AppointmentsController extends Controller
{

    public function index(Request $request)
    {
        $settings = Settings::find(1);
        $condition_1 = '1=1';
        $condition_2 = '1';
        
        if($request->type && $request->res_id > 0) {
            if($request->type === 'user') {
                if(Gate::allows('onlyAppointmentsUserSelf', User::class)) {
                    $condition_1 = 'events.user_id = '.$request->user()->id;
                    $condition_2 = 'users.id = '.$request->user()->id;
                }
                else {
                    $condition_1 = 'events.user_id = '.$request->res_id;
                    $condition_2 = 'users.id = '.$request->res_id;
                }
            }
            elseif($request->type === 'resource')
                $condition_1 = 'resource_id = '.$request->res_id;
        }

        $events = Event::select('*', 'events.id as event_id', 'appointments.id as appointment_id', 'events.user_id as event_user_id', 'appointments.branch_id as event_branch_id')
            ->whereBetween('start_time', [$request->startDate, $request->endDate])
            ->leftJoin('users', 'users.id', 'events.user_id')
            ->leftJoin('appointments','appointments.id','=','events.appointment_id')
            ->whereRaw($condition_1)
            ->get();

        $data = [];
        $datas = [];

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

        foreach ($events as $event) {
            if(!$event->appointment) continue;
            $status_name = in_array($event->appointment->status, ['part_paid', 'unpaid']) ? 'completed' : $event->appointment->status;
            $data['id'] = $request->branch_id === $event->event_branch_id ? $event->event_id : 0;
            $data['appointment_id'] = $request->branch_id === $event->event_branch_id ? $event->appointment_id : 0;
            $data['title'] = $event->customer ? $event->customer->firstname : 'Ажиллахгүй цаг';
            $data['cust_phone'] = $event->customer ? $event->customer->phone : '';
            $data['service_name'] = $event->service_id > 0 ? $event->service->name : '';
            $data['start'] = $event->start_time;
            $data['end'] = $event->end_time;
            $data['status'] = $status_name;
            $data['resourceId'] = $request->type === 'resource' ? $event->resource_id : $event->event_user_id;
            $data['textColor'] = $status_name === 'booked' ? $text_dark_color : $text_color;
            $data['color'] = array_search($status_name, $event_colors);
            $data['validated'] = $event->appointment->validated == 1 ? true : false;
            $data['className'] = 'fw-bolder';
            $data['editable'] = $status_name == 'completed' ? false : true;
            $data['display'] = $request->branch_id === $event->event_branch_id ? 'normal':'background';

            $datas[] = $data;
        }

        $settings = Settings::find(1);
        $branch = '';
        if ($settings->has_branch) {
            $branch = Branch::where('id', $request->branch_id)->first();
        }

        // get lunch time
        $lunch_start_time = $branch && $branch->lunch_start_time ? $branch->lunch_start_time : $settings->lunch_start_time;
        $lunch_end_time = $branch && $branch->lunch_end_time ? $branch->lunch_end_time : $settings->lunch_end_time;
        

        // get shifts
        $shifts = Shift::whereRaw(sql: '(start_date <= "'.date('Y-m-d', strtotime($request->startDate)).'" and end_date >= "'.date('Y-m-d', strtotime($request->startDate)).'")')->get();
        $resource_shift = [];
    
        $users = User::select('users.id as value', 'firstname as label', 'users.id', 'firstname as title', 'roles.name as role_name', 'branch_id')
                        ->leftJoin('roles', 'roles.id', 'users.role_id')
                        ->where('status', 'active')
                        ->where('roles.name', 'user')
                        ->whereRaw($condition_2)
                        ->get();

        $day_name = strtolower(Carbon::parse($request->startDate)->format('l'));

        foreach ($users as $user) { 
            if($lunch_start_time && $lunch_end_time) { 
                $data['id'] = 0;
                $data['appointment_id'] = 0;
                $data['title'] = 'Цайны цаг';
                $data['cust_phone'] = '';
                $data['service_name'] = '';
                $data['start'] = Carbon::parse($request->startDate)->format('Y-m-d').' '.$lunch_start_time.':00';
                $data['end'] = Carbon::parse($request->startDate)->format('Y-m-d').' '.$lunch_end_time.':00';
                $data['status'] = 'booked';
                $data['resourceId'] = $user->value;
                $data['textColor'] = $text_color;
                $data['color'] = array_search('booked', $event_colors);
                $data['validated'] = false;
                $data['className'] = 'fw-bolder';
                $data['editable'] = true;
                $data['display'] = 'background';
                $data['eventAllow'] = false;
                $datas[] = $data;
            }

            $daysOfWeek = [];
            $start_time = $end_time = '';
            $user_has_shift = false;
            foreach ($shifts as $shift) {
                if($shift->user_id == $user->value) {
                    $user_has_shift = true;
                    $shift_week_data = json_decode($shift->shift_data);
                    foreach ($shift_week_data as $key => $shift_week) {
                        if($key == $day_name && $shift_week->enabled == true) {
                            $daysOfWeek[] = $shift_week->dayIndex;
                            $start_time = $shift_week->start;
                            $end_time = $shift_week->end;
                        }
                    }
                    
                    $businessHours = ['daysOfWeek' => $daysOfWeek, 'startTime' => $start_time, 'endTime' => $end_time];   
                }
            }

            if(!$user_has_shift) { 
                $start_time = $branch && $branch->start_time ? $branch->start_time : $settings->start_time;
                $end_time = $branch && $branch->end_time ? $branch->end_time : $settings->end_time;   
                $business_days = $branch && $branch->business_days ? $branch->business_days : $settings->business_days;

                $day_index = strtolower(Carbon::parse($request->startDate)->weekday());
                if(strpos($business_days, $day_index) !== false)
                    $daysOfWeek = [$day_index];

                $businessHours = ['daysOfWeek' => $daysOfWeek, 'startTime' => $start_time, 'endTime' => $end_time];
            }

            $resource_shift[] = [
                'id' => $user->value, 
                'title' => $user->title, 
                'branch_id' => $user->branch_id, 
                'role_name' => $user->role_name, 
                'value' => $user->value, 
                'label' => $user->label,
                'businessHours' => $businessHours
            ];
        }

        $warning = $this->systemOverdueWarning();
        $payload['message'] = $warning;
        $payload['status'] = 200;

        $result['events'] = $datas;
        $result['shifts'] = $resource_shift;

        $response['data'] = $result;
        $response['payload'] = $payload;

        return response($response);
    }

    public function systemOverdueWarning() {
        $settings = Settings::find(1);
        $limit_date = new Carbon($settings->limit_date_usage);
        $today = Carbon::today();
        $warning_text = '';
        $interval = $limit_date->diffInDays($today);
        
        if($limit_date->greaterThanOrEqualTo($today) && $interval < 10) {
            $warning_text = 'Үйлчилгээний хугацаа дуусахад '.$interval.' хоног үлдсэн байна. 7500-4000, 86086036 дугаарт холбогдож хугацаагаа сунгуулна уу.';
        }

        if($today->greaterThan($limit_date)) {
            throw ValidationException::withMessages([
                'Үйлчилгээний хугацаа хэтэрсэн байна. 7500-4000, 86086036 дугаарт холбогдож хугацаагаа сунгуулна уу.'
            ]);
        }
        
        return $warning_text;
    }

    public function userResources()
    {
        $users = User::selectRaw('id, firstname as title')->get();

        $response['data'] = $users;
        $response['payload'] = ['status' => 200];

        return response($response);
    }

    public function getMasterData()
    {   
        $services = Service::selectRaw('id, id as value, CASE WHEN code != "" THEN CONCAT(name, " - ", code) ELSE name END as label, duration, allow_resources, 
                price, category_id, type, code')
            ->with('resources')
            ->whereRaw('services.status = 1 and services.is_category = 0')
            ->get();
        $service_categories = Service::select('id', 'name')
            ->whereRaw('services.is_category = 1')
            ->get();   
        $service_types = ServiceType::select('id as value', 'name as label')->get(); 
        $resources = Resource::select('id as value', 'name as label', 'id', 'name as title')
            ->where('status', '=', '1')
            ->get();
        $users = User::select('users.id as value', 'firstname as label', 'users.id', 'firstname as title', 'roles.name as role_name', 'branch_id')
                        ->leftJoin('roles', 'roles.id', 'users.role_id')
                        ->where('status', 'active')
                        ->where('roles.name', 'user')
                        ->get();

        $branches = Branch::select('*', 'id as value', 'name as label')->get();
        $bank_accounts = BankAccount::select('id', 'id as value', 'name as label', 'account_number')->get();
        
        $datas['services'] = $services;
        $datas['serviceTypes'] = $service_types;
        $datas['serviceCategories'] = $service_categories;
        $datas['resources'] = $resources;
        $datas['users'] = $users;
        $datas['branches'] = $branches;
        $datas['bankAccounts'] = $bank_accounts;

        return response($datas);
    }

    public function store(Request $request)
    {
        $item_values = $request->item_values;

        $appointment = new Appointment();
        $appointment->event_date = $request->event_date;
        if($request->is_time_block == false){
            $appointment->customer_id = $request->customer_id;
            $appointment->desc = $request->desc;
            if (count($item_values) == 1 && $item_values[0]['service_id'] == 0) {
                $appointment->is_serviceless = true;
            }
            $appointment->branch_id = $request->branch_id;
            $appointment->save();
            foreach ($item_values as $item) {
                $item = (object) $item;
                $event = new Event();
                $event->appointment_id = $appointment->id;
                $event->customer_id = $request->customer_id;
                $event->resource_id = $item->resource_id;
                $event->service_id = $item->service_id;
                $event->user_id = $item->user_id;
                $event->duration = $item->duration;
                $event->start_time = $request->event_date . ' ' . $item->start_time;
                $event->end_time = $request->event_date . ' ' . $item->end_time;
                $event->price = str_replace(',', '', $item->price);
                $event->save();
            }
        }else{
            $appointment->customer_id = 0;
            $appointment->desc = '';
            $appointment->is_serviceless = true;
            $appointment->branch_id = $request->branch_id;
            $appointment->status = 'time_block';
            $appointment->save();
            foreach ($item_values as $item) {
                $item = (object) $item;
                $event = new Event();
                $event->appointment_id = $appointment->id;
                $event->customer_id = 0;
                $event->resource_id = 0;
                $event->service_id = 0;
                $event->user_id = $item->user_id;
                $event->duration = 60;
                $event->start_time = $request->event_date . ' ' . $item->start_time;
                $event->end_time = $request->event_date . ' ' . $item->end_time;
                $event->price = 0;
                $event->save();
            }
        }
        
        $response['data'] = $appointment;
        $response['payload'] = [];

        // TOOK TOO MUCH TIME SO LATER USE QUEUE OR SOME OTHER STUFF IN IT 
        
        // $settings = Settings::find(1);
        // if ($settings->appointment_email_to_user && $appointment->status !== 'time_block')   
        //     $this->appointmentBookedMail($appointment);

        return response($response);
    }

    public function eventParse($appointment)
    {
        $start_datetime = '';
        $event_parsed_datas = [];
        $event_price = 0;
        if(!$appointment->is_serviceless) {
            $events = $appointment->events;
            foreach ($events as $key => $event) {
                $event_arr = $event->attributesToArray();
                $event_parsed_data = [
                    ...$event_arr,
                    'start_time' => date("H:i", strtotime($event->start_time)),
                    'end_time' => date("H:i", strtotime($event->end_time)),
                    'allow_resources' => $event->service_id > 0 ? $event->service->allow_resources : false,
                    'price' => $event->price,
                    'service_name' => $event->service_id > 0 ? $event->service->name : '',
                    'service_type_name' => $event->service_id > 0 ? $event->service->typeTable->name : '',
                    'resource_name' => $event->resource ? $event->resource->name : '',
                    'user_name' => $event->user->firstname,
                ];
                $key == 0 ? $start_datetime = $event->start_time : null;
                $event_price += $event->price;
                $event_parsed_datas[] = $event_parsed_data;
            }
        }

        $left_payment = $appointment->customer?->left_payment ? $appointment->customer->left_payment : 0;
        $invoice = $appointment->invoice ? 
            [...$appointment->invoice->attributesToArray(), 'stored_left_payment' => $left_payment, 'membership_name' => $appointment->discountInvoice?->membership_type_id ? $appointment->discountInvoice?->membershipType?->title : ''] : 
            ['id' => '', 'payment' => $event_price, 'stored_left_payment' => $left_payment, 'paid' => 0, 'discount_amount' => 0];

        $parsed_data = [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'desc' => $appointment->desc,
            'customer_id' => $appointment->customer_id,
            'event_date' => date("Y-m-d", strtotime($appointment->event_date)),
            'start_datetime' => $start_datetime,
            'item_values' => $event_parsed_datas,
            'is_serviceless' => $appointment->is_serviceless > 0 ? true : false,
            'invoice' => $invoice,
        ];

        return $parsed_data;
    }

    public function show($id)
    {
        $appointment = Appointment::find($id);
        $data = $this->eventParse($appointment);

        $customer = DB::table('customers')
                ->selectRaw('customers.*, customers.id as value, customers.firstname as label,
                    count(appointments.id) as total_appointments,
                    count(case when appointments.status = "no_show" THEN 1 END) as no_show_appointments,
                    count(case when appointments.status = "cancelled" THEN 1 END) as cancelled_appointments'
                )
                ->where('customers.id', '=', $appointment->customer_id)
                ->leftJoin('appointments', 'appointments.customer_id', '=', 'customers.id')
                ->groupBy('customers.id')
                ->first();

        $response['data']['customer'] = $customer;
        $response['data']['appointment'] = $data;
        $response['payload'] = [];

        return response($response);
    }

    public function changeEvent(Request $request, $id)
    {
        $event = Event::find($id);
        $request->user_id && $event->user_id = $request->user_id;
        $event->start_time = date('Y-m-d H:i:s', strtotime($request->start_time));
        $event->end_time =  date('Y-m-d H:i:s', strtotime($request->end_time));
        $event->save();

        $appointment = Appointment::find($event->appointment_id);
        $appointment->event_date = date('Y-m-d', strtotime($request->start_time));
        $appointment->save();

        $response['data'] = $event;
        $response['payload'] = [];

        return response($response);
    }

    public function update(Request $request)
    {
        $items = $request->item_values;
        $old_events = Event::where('appointment_id', '=', $request->appointment_id)->get();
        $updated_event_ids = [];
        $event_total_payment = 0;

        foreach ($items as $item) {
            $item = (object)$item;
            if(isset($item->id)) {
                $event = Event::find($item->id);
                $updated_event_ids[] = $item->id;
            }
            else 
                $event = new Event;
            
            $request->customer_id ? $event->customer_id = $request->customer_id : null;
            $item->user_id ? $event->user_id = $item->user_id : null;
            $event->resource_id = $item->allow_resources == 1 ? $item->resource_id : 0;
            $item->service_id ? $event->service_id = $item->service_id : null;
            $item->duration ? $event->duration = $item->duration : null;
            $event->start_time = $request->event_date . ' ' . $item->start_time;
            $event->end_time = $request->event_date . ' ' . $item->end_time;
            $event->appointment_id = $request->appointment_id;
            $event->price = str_replace(',', '', $item->price);
            $event->save();

            $event_total_payment += $event->price;
        }

        foreach($old_events as $old_event) {
            if(!in_array($old_event->id, $updated_event_ids))
                $old_event->forcedelete();
        } 
        $items_number = count($items);
        
        $appointment = Appointment::find($request->appointment_id);
        $appointment->customer_id = $request->customer_id;
        $appointment->desc = $request->desc;
        $appointment->branch_id = $request->branch_id;
        $appointment->event_date = $request->event_date;
        if(!($items_number == 1 && $items[0]['service_id'] == 0)) {
            $appointment->is_serviceless = false;
        }
        $appointment->save();

        $invoice = $appointment->invoice;
        if($invoice) {
            $invoice->payment = $event_total_payment;
            $invoice->payable = $event_total_payment - $invoice->paid;
            $invoice->save();
        }

        $response['data'] = $event;
        $response['payload'] = [];

        return response($response);
    }

    public function changeStatus(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        $appointment->status = $request->status;
        $appointment->save();
        $status = 200;

        $response['data'] = $appointment;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function changeDesc(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        $appointment->desc = $request->desc;
        $appointment->save();

        $response['data'] = $appointment;
        $response['payload'] = [];

        return response($response);
    }

    public function cancelEvent(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        $appointment->status = $request->status;
        $appointment->cancellation_type = $request->cancel_type;
        $appointment->save();

        $events = $appointment->events;
        foreach ($events as $event) {
            $event->delete();
        }

        $appointment->delete();
        $status = 200;

        $response['data'] = $appointment;
        $response['payload'] = ['status' => $status];

        return $response;
    }

    public function createPayment(Request $request, $id) {
        $appointment = Appointment::find($id);
        // $appointment->status = $request->left_payment > 0 ? 'part_paid' : 'completed';
        $appointment->status = $request->state;
        $appointment->save();

        $invoice = Invoice::where('appointment_id', '=', $id)
                ->where('state', '!=', 'voided')
                ->first();
        if($invoice)
            $invoice->paid = intval($invoice->paid) + $request->paid_amount;
        else {
            $total_discount_amount = 0;

            $invoice = new Invoice();
            $invoice->appointment_id = $id;
            $invoice->customer_id = $appointment->customer_id;
            $invoice->user_id = $request->user_id;
            $invoice->payable = $request->payable;
            $invoice->paid = $request->paid_amount;
            $invoice->discount_amount = $total_discount_amount;
            $invoice->save();

            foreach($request->discounts as $discount) {
                $invoice_discount = new InvoiceDiscount();
                $invoice_discount->invoice_id = $invoice->id;
                $invoice_discount->type = $discount['type'];
                $invoice_discount->discount_amount = $discount['amount'];
                $invoice_discount->discount_percent = $discount['percent'];
                $invoice_discount->save();

                $total_discount_amount += $discount['amount'];
            }
            $invoice->discount_amount = $total_discount_amount;
            $invoice->save();
        }
        $invoice->payment = $request->payment;
        $invoice->state = $request->state;
        $invoice->save();

        foreach($request->split_payments as $split_payment) {
            $split_amount = $split_payment['split_payment_amount'];
            $type = $split_payment['type'];
            if($type !== 'qpay'){
                $payment = new Payment();
                $payment->invoice_id = $invoice->id;
                $payment->user_id = $request->user_id;
                $payment->type = $type;
                $payment->amount = $split_amount;
                $payment->bank_account_id = $split_payment['bank_account_id'];
                $payment->coupon_id = $split_payment['coupon_code_id'];
                $payment->desc = $split_payment['desc'];
                $payment->save();
            }

            if($type == 'coupon') {
                $coupon_code_id = $split_payment['coupon_code_id'];
                $coupon_code = CouponCode::find($coupon_code_id);
                $total_redeemed = $coupon_code->redeemed + $split_amount;
    
                $coupon_code->redeemed = $total_redeemed;
                ($total_redeemed == $coupon_code->value) ? $coupon_code->status = 'redeemed' : null;
                $coupon_code->save();
            }
        }

        $customer = Customer::withTrashed()->find($appointment->customer_id);
        $total_paid = intval($customer->total_paid) + $request->paid_amount;
        $customer->total_paid = $total_paid;
        $customer->left_payment = $request->left_payment;
        $customer->save();

        $status = 200;

        $response['data'] = $appointment;
        $response['payload'] = ['status' => $status];

        return $response;
    }

    public function getPaymentDetails($id) {
        $detail_datas = [];

        $payments = Payment::where('invoice_id', $id)->get();
        $payment_types = PaymentMethod::all();

        foreach($payments as $payment) {
            $type_name = 'Бусад';
            foreach($payment_types as $type) {
                if($payment->type == $type->slug) {
                    $type_name = $type->name;
                }    
            }
            $detail_datas[] = [
                'type' => $type_name,
                'paid' => $payment->amount,
                'payment_date' => date("Y-m-d H:m", strtotime($payment->created_at)),
                'desc' => $payment->desc,
                'coupon_code' => $payment->coupon_id > 0 ? $payment->couponCode->code : '',
                'bank_account' => $payment->bank_account_id > 0 ? $payment->bankAccount->name.', '. $payment->bankAccount->account_number : '',
            ];
        }
        $status = 200;

        $result['data'] = $detail_datas;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function voidPayment($id) { 
        $this->authorize('update', Customer::class);
        $invoice = [];

        $invoice = Invoice::find($id);
        $invoice->state = 'voided';
        $invoice->save();

        $left_payment = $invoice->payable - $invoice->paid;

        $customer = Customer::find($invoice->customer_id);
        $customer->total_paid = $customer->total_paid - $invoice->paid;
        $customer->left_payment = $customer->left_payment - $left_payment;
        $customer->save();

        $appointment = Appointment::find($invoice->appointment_id);
        $appointment->status = 'booked';
        $appointment->save();

        $status = 200;

        $result['data'] = $invoice;
        $result['payload'] = ['status' => $status];
        return response($result);
    }


    public function getPaymentMethods() {
        $payment_methods = PaymentMethod::select('id', 'name', 'slug', 'active')->get();

        foreach($payment_methods as $payment_method) {
            $payment_method_arr = $payment_method->attributesToArray();
            $payment_method_arr = [
                ...$payment_method_arr,
                'active' => $payment_method->active == 1 ? true : false,
            ];
            
            $payment_methods_data[] = $payment_method_arr;
        }
        return $payment_methods_data;
    }

    public function updatePaymentMethods(Request $request) {
        $methods = $request->payment_methods;
        foreach($methods as $method) {
            $payment_method = PaymentMethod::find($method['id']);
            $payment_method->active = $method['active'];
            $payment_method->save();
        }

        $status = 200;

        $response['data'] = [];
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function appointmentBookedMail($appointment) {
        $is_success = false;
        $settings = Settings::find(1);
        $data['event_date'] = $appointment->event_date;
        $data['branch_name'] = $settings->has_branch ? $appointment->branch->name : '';
        $lastname = ($appointment->customer && $appointment->customer->lastname && isset($appointment->customer->lastname[0])) ? $appointment->customer->lastname[0] . '.' : '';
        $data['customer_name'] = $appointment->customer ? $lastname . '' . $appointment->customer->firstname : '';
        $data['customer_phone'] = $appointment->customer ? $appointment->customer->phone : '';

        $users = [];
        $user_id = 0;
        foreach($appointment->events as $event) {
            $users[$event->user->id] = $event->user->email;
        }
        $users = array_unique($users);

        foreach($users as $user_id => $user_email) {
            $data['events'] = [];
            $custom_events = $appointment->events->where('user_id', $user_id);
            $data['event_time'] = date('H:i', strtotime($custom_events->first()->start_time)).' - '.date('H:i', strtotime($custom_events->last()->end_time));
      
            foreach($custom_events as $event) {
                $event['service_name'] = $event->service ? $event->service->code ? $event->service->code .' '. $event->service->name : $event->service->name : 'Үйлчилгээ сонгогдоогүй';
                $event['service_time'] = date('H:i', strtotime($event->start_time)).' - '.date('H:i', strtotime($event->end_time));

                $data['events'][] = $event;
            }

            if($data) {
                Mail::to($user_email)->send(new NewAppointmentBooked($data));
                $is_success = true;
            }
        }
        
        return $is_success;
    }
}
