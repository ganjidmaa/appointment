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
        // Normalize dates using Carbon
        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');
        
        // Flag to ensure the new shift is created only once
        $newShiftCreated = false;
        
        // ----------------------------
        // SINGLE-DAY SHIFT (start_date == end_date)
        // ----------------------------
        if ($start_date === $end_date) {
            // Get any previous shift that covers this day
            $prev_shifts = Shift::where('user_id', $request->user_id)
                ->where('start_date', '<=', $start_date)
                ->where('end_date', '>=', $start_date)
                ->get();
            
            // The new shift day as a Carbon instance
            $newDay = Carbon::parse($start_date);
            
            foreach ($prev_shifts as $prev_shift) {
                $prevStart = Carbon::parse($prev_shift->start_date);
                $prevEnd   = Carbon::parse($prev_shift->end_date);
                
                // CASE A: The previous shift exactly matches the new day.
                if ($prevStart->eq($newDay) && $prevEnd->eq($newDay)) {
                    // Remove the old shift
                    $prev_shift->delete();
                }
                // CASE B: Previous shift starts before the new day and ends exactly on the new day.
                else if ($prevStart->lt($newDay) && $prevEnd->eq($newDay)) {
                    // Trim the end date of the previous shift to the day before the new shift
                    $prev_shift->end_date = $newDay->copy()->subDay()->format('Y-m-d');
                    $prev_shift->save();
                }
                // CASE C: Previous shift starts exactly on the new day and ends after the new day.
                else if ($prevStart->eq($newDay) && $prevEnd->gt($newDay)) {
                    // Move the start date of the previous shift to the day after the new shift
                    $prev_shift->start_date = $newDay->copy()->addDay()->format('Y-m-d');
                    $prev_shift->save();
                }
                // CASE D: Previous shift spans over the new day (starts before and ends after)
                else if ($prevStart->lt($newDay) && $prevEnd->gt($newDay)) {
                    // Split the previous shift into two parts:
                    // 1. The left portion: adjust current shift to end before the new day
                    $oldEnd = $prev_shift->end_date;
                    $prev_shift->end_date = $newDay->copy()->subDay()->format('Y-m-d');
                    $prev_shift->save();
                    
                    // 2. The right portion: create a new shift for the days after the new day
                    $new_shift = new Shift();
                    $new_shift->start_date = $newDay->copy()->addDay()->format('Y-m-d');
                    $new_shift->end_date   = $oldEnd;
                    $new_shift->user_id    = $prev_shift->user_id;
                    $new_shift->branch_id  = $prev_shift->branch_id;
                    $new_shift->shift_data = $prev_shift->shift_data;
                    $new_shift->save();
                }
            }
            
            // Create the new single-day shift
            $shift = new Shift();
            $shift->start_date = $start_date;
            $shift->end_date   = $end_date;
            $shift->user_id    = $request->user_id;
            $shift->branch_id  = $request->branch_id;
            $shift->shift_data = json_encode($request->schedule);
            $shift->save();
            
            $status = 200;
            $response['payload'] = ['status' => $status];
            return response($response);
        }
        
        // ----------------------------
        // MULTI‑DAY SHIFT (start_date != end_date)
        // ----------------------------
        $prev_shifts = Shift::where('user_id', $request->user_id)->get();
        $newStart = Carbon::parse($start_date);
        $newEnd   = Carbon::parse($end_date);
        
        foreach ($prev_shifts as $prev_shift) {
            $prevStart = Carbon::parse($prev_shift->start_date);
            $prevEnd   = Carbon::parse($prev_shift->end_date);
            
            // Skip shifts with no overlap
            if ($prevEnd->lt($newStart) || $prevStart->gt($newEnd)) {
                continue;
            }
            
            // CASE 1: Previous shift completely covers the new shift.
            if ($prevStart->lte($newStart) && $prevEnd->gte($newEnd)) {
                // If there's a left portion (previous shift starts before new shift)
                if ($prevStart->lt($newStart)) {
                    $prev_shift->end_date = $newStart->copy()->subDay()->format('Y-m-d');
                    $prev_shift->save();
                }
                
                // Create the new shift (if not created already)
                if (!$newShiftCreated) {
                    $shift = new Shift();
                    $shift->start_date = $newStart->format('Y-m-d');
                    $shift->end_date   = $newEnd->format('Y-m-d');
                    $shift->user_id    = $request->user_id;
                    $shift->branch_id  = $request->branch_id;
                    $shift->shift_data = json_encode($request->schedule);
                    $shift->save();
                    $newShiftCreated = true;
                }
                
                // If there's a right portion (previous shift ends after new shift)
                if ($prevEnd->gt($newEnd)) {
                    $new_shift = new Shift();
                    $new_shift->start_date = $newEnd->copy()->addDay()->format('Y-m-d');
                    $new_shift->end_date   = $prevEnd->format('Y-m-d');
                    $new_shift->user_id    = $prev_shift->user_id;
                    $new_shift->branch_id  = $prev_shift->branch_id;
                    $new_shift->shift_data = $prev_shift->shift_data;
                    $new_shift->save();
                }
                // If exactly matching end, remove the previous shift
                else if ($prevEnd->eq($newEnd)) {
                    $prev_shift->delete();
                }
            }
            // CASE 2: Left-side overlap only.
            else if ($prevStart->lte($newStart) && $prevEnd->gt($newStart) && $prevEnd->lt($newEnd)) {
                if($prevStart->lt($newStart)) {
                    $prev_shift->end_date = $newStart->copy()->subDay()->format('Y-m-d');
                    $prev_shift->save();
                }
                else if ($prevStart->eq($newStart)) {
                    $prev_shift->delete();
                }
                
                if (!$newShiftCreated) {
                    $shift = new Shift();
                    $shift->start_date = $newStart->format('Y-m-d');
                    $shift->end_date   = $newEnd->format('Y-m-d');
                    $shift->user_id    = $request->user_id;
                    $shift->branch_id  = $request->branch_id;
                    $shift->shift_data = json_encode($request->schedule);
                    $shift->save();
                    $newShiftCreated = true;
                }
            }
            // CASE 3: Right-side overlap only.
            else if ($prevStart->gt($newStart) && $prevStart->lte($newEnd) && $prevEnd->gte($newEnd)) {
                $prev_shift->start_date = $newEnd->copy()->addDay()->format('Y-m-d');
                $prev_shift->save();
                
                if (!$newShiftCreated) {
                    $shift = new Shift();
                    $shift->start_date = $newStart->format('Y-m-d');
                    $shift->end_date   = $newEnd->format('Y-m-d');
                    $shift->user_id    = $request->user_id;
                    $shift->branch_id  = $request->branch_id;
                    $shift->shift_data = json_encode($request->schedule);
                    $shift->save();
                    $newShiftCreated = true;
                }
            }
            // CASE 4: Previous shift is completely within the new shift.
            else if ($prevStart->gte($newStart) && $prevEnd->lte($newEnd)) {
                $prev_shift->delete();
                
                if (!$newShiftCreated) {
                    $shift = new Shift();
                    $shift->start_date = $newStart->format('Y-m-d');
                    $shift->end_date   = $newEnd->format('Y-m-d');
                    $shift->user_id    = $request->user_id;
                    $shift->branch_id  = $request->branch_id;
                    $shift->shift_data = json_encode($request->schedule);
                    $shift->save();
                    $newShiftCreated = true;
                }
            }
        }
        
        // If no previous shift affected the range, create the new shift.
        if (!$newShiftCreated) {
            $shift = new Shift();
            $shift->start_date = $start_date;
            $shift->end_date   = $end_date;
            $shift->user_id    = $request->user_id;
            $shift->branch_id  = $request->branch_id;
            $shift->shift_data = json_encode($request->schedule);
            $shift->save();
        }
        
        $status = 200;
        $response['payload'] = ['status' => $status];
        return response($response);
    }


    public function storeTimeOff(Request $request)
    {
        
    }

}
