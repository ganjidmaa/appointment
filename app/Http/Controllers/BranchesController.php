<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class BranchesController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('view', Branch::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'name like "%'.$search.'%" or phone like "%'.$search.'%" ';
        }

        $branches = Branch::selectRaw('branches.id, branches.name, lunch_start_time, lunch_end_time,
                branches.start_time, branches.end_time, branches.slot_duration, branches.phone, branches.business_days')
            ->whereRaw($where)
            ->paginate($request->items_per_page)->withQueryString();

        $status = 200;

        $payload = [
            'pagination' => $branches,
            'status' => $status
        ];
    
        $responce['data'] = $branches->items();
        $responce['payload'] = $payload;
        return response($responce);
    }

    public function store(Request $request)
    {
        // $this->authorize('create', Branch::class);
        $branch = new Branch();
        $branch->name = $request->name;
        $branch->start_time = $request->start_time;
        $branch->end_time = $request->end_time;
        $branch->lunch_start_time = $request->lunch_start_time;
        $branch->lunch_end_time = $request->lunch_end_time;
        $branch->slot_duration = $request->slot_duration;
        $branch->phone = $request->phone;
        $branch->address = $request->address;
        $branch->business_days = $request->business_days;
        $branch->save();
        
        $status = 200;
        $response['data'] = $branch;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function show($id)
    {
        // $this->authorize('update', Branch::class);
        $branch = Branch::find($id);
        if(!$branch) 
            $branch = (object)[];

        $status = 200;
        $response['data'] = $branch;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function update(Request $request, $id)
    {
        // $this->authorize('update', Branch::class);

        $branch = Branch::find($id);
        $branch->name = $request->name;
        $branch->start_time = $request->start_time;
        $branch->end_time = $request->end_time;
        $branch->lunch_start_time = $request->lunch_start_time;
        $branch->lunch_end_time = $request->lunch_end_time;
        $branch->slot_duration = $request->slot_duration;
        $branch->phone = $request->phone;
        $branch->address = $request->address;
        $branch->business_days = $request->business_days;
        $branch->save();
        
        $status = 200;
        $response['data'] = $branch;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function destroy($id)
    {
        // $this->authorize('delete', Branch::class);

        $branch = Branch::find($id);
        $branch->delete();

        return true;
    }
}
