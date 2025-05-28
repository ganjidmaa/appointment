<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('view', Membership::class);

        $search = $request->search;
        $sortColumn = $request->sort ?? 'id'; // Default sort by ID if not provided
        $sortOrder = strtolower($request->order) === 'desc' ? 'desc' : 'asc'; // Ensure order is valid
        $where = '1 = 1';
        if($search) {
            $where = 'code like "%'.$search.'%" or title like "%'.$search.'%"';
        }

        $memberships = Membership::selectRaw('memberships.id, memberships.membership_type_id,
                memberships.code, membership_types.title, customer_codes.customer_number')
            ->leftJoin('membership_types', 'membership_types.id', 'memberships.membership_type_id')
            ->leftJoin(DB::raw('(SELECT customers.id, customers.membership_code, count(customers.membership_code) as customer_number FROM customers
                GROUP BY customers.membership_code) customer_codes'), 'customer_codes.membership_code', 'memberships.code')
            ->groupBy('memberships.code')
            ->orderBy($sortColumn, $sortOrder)
            ->whereRaw($where)
            ->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $memberships,
            'status' => $status
        ];
    
        $responce['data'] = $memberships->items();
        $responce['payload'] = $payload;
        return response($responce);
    }


    public function getMasterData()
    {
        $this->authorize('view', Membership::class);

        $customers = Customer::all();
        $status = 200;

        $responce['data'] = $customers;
        $responce['payload'] = ['status' => $status];
        return response($responce);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Membership::class);
        if($request->code) {
            $used_code = Membership::where('code', $request->code)->first();
            if($used_code) {
                $status = 401;
                $responce['data'] = [];
                $responce['payload'] = ['status' => $status];
                return response($responce);
            }
        }

        $membership = new Membership();
        $membership->membership_type_id = $request->membership_type_id;
        $membership->code = $request->code ? $request->code : '';
        $membership->password = '';
        $membership->save();

        $customer_ids = $request->selected_customers;
        if($customer_ids) {
            foreach($customer_ids as $customer_id) {
                $customer = Customer::find($customer_id);
                $customer->membership_code = $request->code;
                $customer->save();
            }
        }
        $status = 200;

        $responce['data'] = $membership;
        $responce['payload'] = ['status' => $status];
        return response($responce);
    }


    public function show($id, $type)
    {
        $this->authorize('update', Membership::class);

        $membership_arr = '';
        if($type === 'customer') {
            $customer = Customer::find($id);
            $membership = Membership::where('code', $customer->membership_code)->first();
        } 
        else if($id)
            $membership = Membership::find($id);

        if($membership) {
            $customer_ids = Customer::select('id')->where('membership_code', $membership->code)->get()->pluck('id');

            $membership_arr = $membership->attributesToArray();
            $membership_arr = [
                ...$membership_arr,
                'percent' => $membership->membershipType->percent,
                'selected_customers' => $customer_ids
            ];
        }
        $membership_types = MembershipType::select('id as value', 'title as label', 'percent', 'prefix')->get();

        $data['membership'] = $membership_arr;
        $data['membership_types'] = $membership_types;
        $status = 200;

        $response['data'] = $data;
        $response['payload'] = ['status' => $status];
        return response($response);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Membership::class);

        $membership = Membership::find($id);
        $membership->membership_type_id = $request->membership_type_id;
        $membership->code = $request->code;
        $membership->password = '';
        $membership->save();

        $prev_customers = Customer::where('membership_code', $membership->code)->get();
        foreach($prev_customers as $prev_customer) {
            $prev_customer->membership_code = '';
            $prev_customer->save();
        }

        $customer_ids = $request->selected_customers;
        foreach($customer_ids as $customer_id) {
            $customer = Customer::find($customer_id);
            $customer->membership_code = $request->code;
            $customer->save();
        }
        $status = 200;

        $responce['data'] = $membership;
        $responce['payload'] = ['status' => $status];
        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Membership::class);

        $membership = Membership::find($id);
        $membership->delete();

        return response(true);
    }   
}
