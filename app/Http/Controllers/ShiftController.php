<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Settings;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{

    public function index(Request $request)
    {
        
        $settings = Settings::find(1);
 
        $users = User::select('users.id as value', 'users.id', 'firstname', 'branch_id')
                        ->leftJoin('roles', 'roles.id', 'users.role_id')
                        ->where('status', 'active')
                        ->where('roles.name', 'user')
                        ->get();

        foreach ($users as $user) { 
            $start_date = Carbon::parse($request->start_date); // Start date
            $end_date = Carbon::parse($request->end_date);  
            $businessHours = [];
            foreach (CarbonPeriod::create($start_date, $end_date->subDay()) as $date) {
                $day_name = strtolower(string: Carbon::parse($date)->format('l'));
                $shift_day_enabled = false;

                $shifts = Shift::whereRaw(sql: 'start_date <= "'.$date->format('Y-m-d').'" and 
                    end_date >= "'.$date->format('Y-m-d').'" and
                    user_id = "'.$user->value.'"')->get();

                $user_has_shift = false;
                foreach ($shifts as $shift) {
                    $user_has_shift = true;
                    $shift_week_data = json_decode($shift->shift_data);
                    
                    foreach ($shift_week_data as $key => $shift_week) {
                        if($key == $day_name && $shift_week->enabled == true) {
                            $shift_day_enabled = true;
                            $start_time = $shift_week->start;
                            $end_time = $shift_week->end;
                        }
                    }
                    
                    $businessHours[] = $shift_day_enabled ? $start_time . ' - ' . $end_time : ' - ';   
                }

                if(!$user_has_shift) { 
                    $branch = '';
                    if ($settings->has_branch) {
                        $branch = Branch::where('id', $request->branch_id)->first();
                    }
                    $start_time = $branch && $branch->start_time ? $branch->start_time : $settings->start_time;
                    $end_time = $branch && $branch->end_time ? $branch->end_time : $settings->end_time;   
                    $business_days = $branch && $branch->business_days ? $branch->business_days : $settings->business_days;

                    $day_index = strtolower(Carbon::parse($date)->weekday());
                    if(strpos($business_days, $day_index) !== false)
                        $shift_day_enabled = true;

                    $businessHours[] = $shift_day_enabled ? $start_time . ' - ' . $end_time : ' - ';
                }
            }
            $state = symbolLabel();

            $resource_shift[] = [
                'id' => $user->value, 
                'name' => $user->firstname, 
                'color' => $state,
                'branch_id' => $user->branch_id, 
                'businessHours' => $businessHours
            ];
        }

        $payload['message'] = 'success';
        $payload['status'] = 200;

        $response['data'] = $resource_shift;
        $response['payload'] = $payload;

        return response($response);
    }

    public function store(Request $request)
    {
        $start_date = date('Y-m-d', strtotime($request->start_date));
        $end_date = date('Y-m-d', strtotime($request->end_date));

        $prev_shifts = Shift::where('user_id', $request->user_id)->get();
        foreach($prev_shifts as $prev_shift) {
            if($prev_shift->start_date <= $start_date && $prev_shift->end_date >= $start_date) {
                $prev_shift->end_date = Carbon::parse($start_date)->subDay()->format('Y-m-d');
                $prev_shift->save();

                $shift = new Shift();
                $shift->start_date = $start_date;
                $shift->end_date = $end_date;
                $shift->user_id = $request->user_id;
                $shift->branch_id = $request->branch_id;
                $shift->shift_data = json_encode($request->schedule);
                $shift->save();
                
                if($prev_shift->end_date >= $end_date) {
                    $new_shift = new Shift();
                    $new_shift->start_date = Carbon::parse($end_date)->addDay()->format('Y-m-d');
                    $new_shift->end_date = $prev_shift->end_date;
                    $new_shift->user_id = $prev_shift->user_id;
                    $new_shift->branch_id = $prev_shift->branch_id;
                    $new_shift->shift_data = $prev_shift->shift_data;
                    $new_shift->save();
                }
                else if($prev_shift->end_date <= $end_date) {
                }
                
            }
            else if($prev_shift->start_date >= $start_date && $prev_shift->start_date <= $end_date) {
                $shift = new Shift();
                $shift->start_date = $start_date;
                $shift->end_date = $end_date;
                $shift->user_id = $request->user_id;
                $shift->branch_id = $request->branch_id;
                $shift->shift_data = json_encode($request->schedule);
                $shift->save();
                
                if($prev_shift->end_date >= $end_date) {
                    $prev_shift->start_date = Carbon::parse($end_date)->addDay()->format('Y-m-d');
                    $prev_shift->save();
                }
                else if($prev_shift->end_date <= $end_date) {
                    $prev_shift->delete();
                }
            }
        }

        if (count($prev_shifts) == 0) {
            $shift = new Shift();
            $shift->start_date = $start_date;
            $shift->end_date = $end_date;
            $shift->user_id = $request->user_id;
            $shift->branch_id = $request->branch_id;
            $shift->shift_data = json_encode($request->schedule);
            $shift->save();
        }

        $status = 200;

        // $response['data'] = $shift;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function storeTimeOff(Request $request)
    {
        
    }

}
