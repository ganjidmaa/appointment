<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Event;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Branch;
use App\Models\Settings;
use App\Models\ServiceBranch;
use App\Models\ServiceUser;
use App\Models\OnlineBookingSettings;
use App\Models\Shift;
use App\Models\Comment;
use App\Models\Coupon;
use App\Models\CouponCode;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use App\Models\MembershipType;
use DateInterval;
use DateTime;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OnlineBookingController extends Controller
{
    public function getMasterData()
    {
        $users = [];
        $services = [];
        $types = [];
        $settings = Settings::find(1);
        $branches = [];
        $comments = [];
        $filtered_services = [];
        $coupons_response = [];
        //if settings has branch services and users will be fetched later
        if ($settings->has_branch == 1) {
            $branches = Branch::all();
        }else{
            $service_categories = Service::select('id', 'name')
                ->whereRaw('is_category = 1')
                ->orderByRaw('CASE WHEN category_id=0 THEN services.id ELSE category_id END asc')
                ->get();
            foreach ($service_categories as $service_category) {
                $service_data = Service::whereRaw('category_id = ' . $service_category->id)->where('is_app_option', 1)->get();
                $service = [
                    'id' => $service_category->id,
                    'name' => $service_category->name,
                    'services' => $service_data,
                ];
                $services[] = $service;
            }
            $users = User::select('users.id as value', 'firstname as label', 'users.id', 'firstname', 'roles.name as role_name')
                ->leftJoin('roles', 'roles.id', 'users.role_id')
                ->where('show_in_online_booking', 1)
                ->where('status', 'active')
                ->where('roles.name', 'user')
                ->get();
        }
        if ($settings->has_service_type == 1){
            $types = ServiceType::all();
        }
        // online booking settings array making
        $booking_settings = OnlineBookingSettings::find(1);
        $settings_arr = $booking_settings->attributesToArray();

        $path = env("APP_URL") . '/'.env("APP_PUBLIC_STORAGE");
        $formatted_data = [
            ...$settings_arr,
            'choose_qpay' => $booking_settings->choose_qpay == 1 ? true : false,
            'choose_autoDiscard' =>$booking_settings->choose_autoDiscard == 1 ? true : false,
            'choose_user' => $booking_settings->choose_user == 1 ? true : false,
            'image_url' => $booking_settings->image ? $path . '/' . $booking_settings->image : '',
        ];
        //if type and branch present same time
        if ($settings->has_service_type == 1 || $settings->has_branch == 1){
            $branches_type = DB::select(DB::raw('SELECT branches.id, GROUP_CONCAT(DISTINCT services.type SEPARATOR ",") AS types FROM services
                left join service_branches on service_branches.service_id = services.id
                left join branches on branches.id = service_branches.branch_id
                GROUP BY branches.id;
                ')->getValue(DB::connection()->getQueryGrammar()));
            foreach ($branches as $key => $branch) {
                foreach ($branches_type as $key => $branch_type) {
                    if($branch_type->id === null || $branch_type->id === $branch->id){
                        $branch->types .= $branch_type->types;
                    }
                }
            }
        }

        $today = Carbon::now()->format('Y-m-d');
        $coupons = Coupon::select('id', 'title', 'desc as note', 'image')->where('end_date', '>=', $today)->where('status', 1)->get();
        $coupons_response = [];
        foreach ($coupons as $coupon) {
            $coupon_arr = $coupon->attributesToArray();
            $coupons_response[] = [...$coupon_arr, 'image' => env("APP_URL") . '/' . env("APP_PUBLIC_STORAGE") . '/user_images/' . $coupon->image];
        }
        // $comments = Comment::orderBy('created_at', 'desc')->limit(5)->get();

        return response([
            'branches' => $branches,
            'users' => $users,
            'serviceCategories' => $services,
            'bookingSettings' => $formatted_data,
            'types' => $types,
            'popularServices' => $filtered_services,
            'comments' => $comments,
            'memberships' => $coupons_response
        ]);
    }

    public function onlineBooking(Request $request)
    {
        $success = false;
        $settings = Settings::find(1);
        $booking_settings = OnlineBookingSettings::find(1);
        try {
            DB::beginTransaction();
            $possibleEvents = [];
            $getFreeTimeRequest = [
                'branch_id' => isset($request->branch) ? $request->branch['id'] : null,
                'service_ids' => $request->service_ids, 
                'user' => isset($request->user) ? $request->user['id'] : null, 
                'event_date' => $request->event_date
            ];
            $getFreeTimeData = $this->getAvailableHours(Request::create('/', 'POST', $getFreeTimeRequest), true);
            $possiblity = [];
            foreach ($getFreeTimeData as $key => $data) {
                if($data['head_time'] === $request->start_time){
                    $possiblity = $data;
                    break;
                }
            }
            if(empty($possiblity)){
                $success = false;
                $status = 202;
                goto end;
            }

            $customer = Customer::where('registerno', $request->customer['registerno'])->first();
            if(!$customer) {
                $customer = Customer::where('phone', $request->customer['phone'])->first();
                if(!$customer) {
                    $customer = new Customer();
                    $customer->firstname = $request->customer['firstname'];
                    $customer->lastname = $request->customer['lastname'];
                    $customer->registerno = $request->customer['registerno'];
                    $customer->phone = $request->customer['phone'];
                    $customer->desc = in_array('desc', $request->customer) ? $request->customer['desc'] : '';
                    $customer->type = 'online';
                    $customer->save();
                }
            }
            else {
                $customer->firstname = $request->customer['firstname'];
                $customer->lastname = $request->customer['lastname'] ?? $customer->lastname;
                $customer->email = $request->customer['email'] ?? $customer->email;
                $customer->save();
            }
            
            $appointment = new Appointment();
            $appointment->event_date = $request->event_date;
            $appointment->customer_id = $customer->id;
            $appointment->desc = $request->customer['desc'];
            $appointment->is_online_booking = true;
            if($settings->use_qpay == 1 && $booking_settings->choose_qpay == 1)
            $appointment->validated = false;
            if ($request->branch) {
                $appointment->branch_id = $request->branch['id'];
            } else {
                $appointment->branch_id = 1;
            }

            $appointment->save();
            $earlier_user = 999;
            foreach ($possiblity['possibility'] as $key => $event) {
                $service = Service::find($event['service_id']);
                if (in_array($earlier_user, $event['possible_users'])) {
                    $user = User::find($earlier_user);
                } else {
                    $user = User::whereIn('id', $event['possible_users'])->inRandomOrder()->first();
                    $earlier_user = $user->id;
                }
                $start_time = $event['hour'];
                $dateTime = DateTime::createFromFormat('H:i', $start_time);
                $dateTime->add(new DateInterval('PT' . $service['duration'] . 'M'));
                $end_time = $dateTime->format('H:i:s');

                $event = new Event();
                $event->appointment_id = $appointment->id;
                $event->customer_id = $customer->id;
                $event->resource_id = 0;
                $event->service_id = $service['id'];
                $event->duration = $service['duration'];
                $event->start_time = $request->event_date . ' ' . $start_time. ':00';  
                $event->end_time = $request->event_date . ' ' . $end_time;  
                $event->price = $service['price'];
                $event->user_id = $user['id'];
                $event->save();
            }

            $success = true;
        } catch (Exception $error) {
            Log::info($error);
            $status = 403;
        }

        end:
        if ($success) {
            DB::commit();
            $status = 200;
            $response['data'] = $appointment;
            $response['payload'] = ['status' => $status];
            return response($response);
        } else {
            DB::rollback();
            $response['data'] = [];
            $response['payload'] = ['status' => $status];
            return response($response);
        }
    }

    public function getAvailableHours(Request $request, $getData = false)
    {
        //doing setup based on branch

        $online_settings = OnlineBookingSettings::find(1);

        $settings = Settings::find(1);
        $branch = $request->branch_id ? Branch::find($request->branch_id) : '';
        $work_start_time = $branch && $branch->start_time ? $branch->start_time : $settings->start_time;
        $work_end_time = $branch && $branch->end_time ? $branch->end_time : $settings->end_time;   
        $business_days = $branch && $branch->business_days ? $branch->business_days : $settings->business_days;
        $slot_minut = $branch && $branch->slot_duration ? $branch->slot_duration : $settings->slot_duration;
        $whereRaw = $request->branch_id ? 'branch_id LIKE  "%'.$request->branch_id.'%"' : '1';

        $start_time = Carbon::createFromFormat('H:i:s', $work_start_time . ':00');
        $end_time = Carbon::createFromFormat('H:i:s', $work_end_time . ':00');

        $lunch_start_time = $branch && $branch->lunch_start_time ? $branch->lunch_start_time : $settings->lunch_start_time;
        $lunch_end_time = $branch && $branch->lunch_end_time ? $branch->lunch_end_time : $settings->lunch_end_time;
        

        //setting up event happen day
        $event_date = date('Y-m-d', strtotime($request->event_date));
        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $event_date . ' 00:00:01');
        $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $event_date . ' 23:59:59');
        //setting up possible users
        if($request->user == 0){
            $possible_users = User::selectRaw('users.id, users.lastname, users.firstname, users.phone, users.email')
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->where('show_in_online_booking', 1)
            ->where('status', '=', 'active')
            ->where('roles.name', '=', 'user')
            ->whereRaw($whereRaw)
            ->get();
        }else{
            $possible_users = User::where('id', $request->user)->get();
        }
        // to remove hours that will exceed company working hour lets subtract company end time by sum of duration
        $sum_duration = Service::whereIn('id', $request->service_ids)->sum('duration');
        $end_time->subMinutes($sum_duration);
        //make possible hours
        $possible_hours = [];
        while ($start_time->lessThanOrEqualTo($end_time)) {
            $possible_hours[] = $start_time->copy();
            $start_time->addMinutes($slot_minut);
        }
        // if date is today remove already passed times
        if(now()->format('Y-m-d') === $event_date){
            $now = Carbon::createFromFormat('H:i:s', now()->format('H:i:s'))->addHours(8);
            foreach ($possible_hours as $key => $possible_hour) {
                if($possible_hour->lessThanOrEqualTo($now)){
                    unset($possible_hours[$key]);
                }
            }
            $possible_hours = array_values($possible_hours);
        }
        // loop around hours_when_working to cherry pick return available hours

        $available_hours = [];       
        foreach ($possible_hours as $possible_hour) {
            //before going to next possible hour set new flag and duration
            $possible_set_of_hours['head_time'] = $possible_hour->format('H:i');
            $isPossibleHourAvailable = false;
            // $currentDuration = 0;
            foreach ($request->service_ids as $service_count => $service_id) {
                // $possible_hour->addMinutes($currentDuration);
                $free_time = [
                    'service_id' =>$service_id,
                    'hour' => $possible_hour->format('H:i'),
                    'possible_users' => []
                ];
                $service = Service::find($service_id);
                $users_will_do_that_service = [];
                //getting users that can do that service  (when user is set this foreach will run once)
                foreach ($possible_users as $user) {
                    $user_matched_service = false;
                    if ($service->available_all_user){
                        $user_matched_service = true;
                    }
                    else {
                        $service_user = ServiceUser::where('service_id', $service->id)
                            ->where('user_id', $user->id)
                            ->first();
            
                        $service_user && $user_matched_service = true;
                    }
                    $user_matched_service && $users_will_do_that_service[] = $user;
                }
                //using users that will do that service check is that user can do that time
                foreach ($users_will_do_that_service as $key => $ongoing_user) {
                    $booked_hours_by_user = DB::table('events')
                    ->selectRaw('DATE_FORMAT(events.start_time,"%H:%i") as start_time, DATE_FORMAT(events.end_time,"%H:%i") as end_time')
                    ->whereBetween('events.start_time', [$start_date, $end_date])
                    ->where('deleted_at', null)
                    ->where('events.user_id', $ongoing_user->id)
                    ->get()->toArray();
                    $booked_hours_by_user = json_decode(json_encode($booked_hours_by_user), true);

                    //------------------start of shift times------------------
                        //make lunch time to event
                    if($lunch_start_time && $lunch_end_time){
                        $booked_hours_by_user[] = [
                            'start_time' => $lunch_start_time,
                            'end_time' => $lunch_end_time
                        ];
                    }

                    $shifts = Shift::whereRaw(sql: 'start_date <= "'.$start_date->format('Y-m-d').'" and 
                        end_date >= "'.$start_date->format('Y-m-d').'" and
                        user_id = "'.$user->id.'"')->get();

                    $day_name = strtolower(string: Carbon::parse($event_date)->format('l'));
                    foreach ($shifts as $shift) {
                        $shift_week_data = json_decode($shift->shift_data);
                        foreach ($shift_week_data as $key => $shift_week) {
                            if($key == $day_name) {
                                if($shift_week->enabled == true) {
                                    //make custom working time to event
                                    $booked_hours_by_user[] = ['start_time' => '00:00', 'end_time' => $shift_week->start];
                                    $booked_hours_by_user[] = ['start_time' => $shift_week->end, 'end_time' => '23:59'];
                                } else {
                                    $booked_hours_by_user[] = ['start_time' => '00:00', 'end_time' => '23:59'];
                                }
                            }
                            
                        }
                    }
                    //------------------end of shift times------------------

                    if($this->checkIsTimeAvailable($possible_hour, $service->duration, $booked_hours_by_user)){
                        $free_time['possible_users'][] = $ongoing_user->id; 
                    }
                }
                if(count($free_time['possible_users']) > 0){
                     $possible_hour->addMinutes($service->duration); 
                }else{
                    $possible_set_of_hours = [];
                    break;
                }
                if($getData) {
                    $possible_set_of_hours['possibility'][] = $free_time;
                }
                $free_time = [];
                if(($service_count+1) === count($request->service_ids)){
                    $isPossibleHourAvailable = true;
                }
            }
            if ($isPossibleHourAvailable){
                $available_hours[] = $possible_set_of_hours;
                $possible_set_of_hours = [];
            }
        }
        return $available_hours;
    }

    public function getBranchServices($branch_id)
    {
        $service_categories = Service::select('id', 'name')
            ->whereRaw('is_category = 1 and is_app_option = 1')
            ->orderByRaw('CASE WHEN category_id=0 THEN services.id ELSE category_id END asc')
            ->get();

        $service_categories_data = [];
        foreach ($service_categories as $service_category) {
            $services = Service::whereRaw('category_id = ' . $service_category->id)->where('is_app_option', 1)->get();
            $service_arr = [];

            foreach ($services as $key => $service) {
                $branch_matched_service = true;

                if (!$service->available_all_branch) {
                    $service_branch = ServiceBranch::where('branch_id', $branch_id)
                        ->where('service_id', $service->id)->first();

                    !$service_branch && $branch_matched_service = false;
                }

                $branch_matched_service && $service_arr[] = $service;
            }

            if (count($service_arr) > 0) {
                $service_data = [
                    'id' => $service_category->id,
                    'name' => $service_category->name,
                    'services' => $service_arr,
                ];
                $service_categories_data[] = $service_data;
            }
        }

        return response($service_categories_data);
    }

    public function getBranchUsers(Request $request)
    {
        if ($request->branch_id)
            $branch_id = $request->branch_id;
        elseif ($request->branch)
            $branch_id = $request->branch['id'];
        else
            $branch_id = '';

        $service_ids = $request->service_ids;
        $whereRaw = $branch_id ? 'branch_id LIKE  "%'.$branch_id.'%"' : '1';
        $user_data = [];

        $users = User::selectRaw('users.id, users.lastname, users.firstname, users.phone, users.email')
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->where('show_in_online_booking', 1)
            ->where('status', '=', 'active')
            ->where('roles.name', '=', 'user')
            ->whereRaw($whereRaw)
            ->get();
        $services_with_related_user = Service::selectRaw('services.id, GROUP_CONCAT(service_users.user_id SEPARATOR ", ") AS related_users')
            ->leftJoin('service_users', 'service_users.service_id', 'services.id')
            ->whereIn('services.id', $service_ids)
            ->groupBy('services.id')->get();
        foreach ($users as $key => $user) {
            $is_user_can_do_all_service = true;
            foreach ($services_with_related_user as $key => $related_users_data) {
                if($related_users_data->related_users !== null) {
                    $related_user_ids = explode(', ', $related_users_data->related_users);
                    !in_array($user->id ,$related_user_ids) && $is_user_can_do_all_service = false;
                }
            }
            $is_user_can_do_all_service && $user_data[] = $user; 
        }

        return $user_data;
    }

    public function checkIsTimeAvailable($start_time, $duration, $eventsOnUser) {
        $end_time = $start_time->copy();
        $end_time->addMinutes($duration);
        $end_time = $end_time->format('H:i');
        $start_time = $start_time->format('H:i');
        foreach ($eventsOnUser as $event) {
            if (
            ($event['start_time'] < $start_time && $start_time < $event['end_time'])||
            ($event['start_time'] < $end_time && $end_time < $event['end_time'])||
            ($event['start_time'] == $start_time && $end_time == $event['end_time'])||
            ($event['start_time'] >= $start_time && $end_time >= $event['end_time'])
            ) {
                return false;
            }
        }
        return true;
    }

    public function getAvailableDates(Request $request) {
        $settings = Settings::find(1);
        $branch = $request->branch_id ? Branch::find($request->branch_id) : '';
        $business_days = $branch && $branch->business_days ? $branch->business_days : $settings->business_days;

        $start_date = Carbon::parse(Carbon::now()); // Start date
        $end_date = Carbon::parse(Carbon::now()->addDays(13)); // End date
        $activeDays = [];

        foreach (CarbonPeriod::create($start_date, $end_date->subDay()) as $date) {
            $day_name = strtolower(string: Carbon::parse($date)->format('l'));
            $shift_day_enabled = false;
            $user_has_shift = false;

            if(isset($request->user) && $request->user > 0) {
                $shifts = Shift::whereRaw(sql: 'start_date <= "'.$date->format('Y-m-d').'" and 
                    end_date >= "'.$date->format('Y-m-d').'" and
                    user_id = "'.$request->user.'"')->get();

                foreach ($shifts as $shift) {
                    $user_has_shift = true;
                    $shift_week_data = json_decode($shift->shift_data);
                    
                    foreach ($shift_week_data as $key => $shift_week) {
                        if($key == $day_name && $shift_week->enabled == true) {
                            $shift_day_enabled = true;
                        }
                    }
                    
                    $activeDays[] = [
                        'date' => $date->format('Y-m-d'),
                        'enabled' => $shift_day_enabled
                    ]; 
                }
            }
            
            if(!$user_has_shift) { 
                $day_index = strtolower(Carbon::parse($date)->weekday());
                if(strpos($business_days, $day_index) !== false)
                    $shift_day_enabled = true;

                $activeDays[] = [
                    'date' => $date->format('Y-m-d'),
                    'enabled' => $shift_day_enabled
                ]; 
            }
        }

        return $activeDays;
    }
    public function checkMembership(Request $request) {
        $coupon_id = $request->coupon_id;
        $coupon_code = CouponCode::where('code', $request->code)->where('coupon_id', $coupon_id)->first();
        $data = 0;
        $statusCode = 201;

        if($coupon_code && $coupon_code->status == 'valid') {
            if($coupon_code->coupon->type == 'mass')
                $data = (int)$coupon_code->value;
            else
                $data = ((int)$coupon_code->value - (int)$coupon_code->redeemed);
            $statusCode = 200;
        }

        return response(['data' => $data, 'statusCode' => $statusCode]);
    }


}