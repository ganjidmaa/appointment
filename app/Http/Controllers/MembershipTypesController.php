<?php

namespace App\Http\Controllers;

use App\Models\MembershipType;
use Illuminate\Http\Request;

class MembershipTypesController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('view', MembershipType::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'percent like "%'.$search.'%" or limit_price like "%'.$search.
                '%" or title like "%'.$search.'%" or prefix like "%'.$search.'%"';
        }

        $types = MembershipType::whereRaw($where)->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $types,
            'status' => $status
        ];

        $response['data'] = $types->items();
        $response['payload'] = $payload;
        return response($response);
    }

    public function store(Request $request)
    {
        $this->authorize('create', MembershipType::class);

        $membership_type = new MembershipType();
        $membership_type->title = $request->title;
        $membership_type->percent = $request->percent;
        $membership_type->prefix = $request->prefix ? $request->prefix : '';
        $membership_type->type = $request->type ? $request->type : 'free';
        $membership_type->limit_price = $request->limit_price ? $request->limit_price : 0;
        if ($request->has('note')) {
            $membership_type->note = $request->note;
        }
        $membership_type->save();
        $status = 200;

        $response['data'] = $membership_type;
        $response['payload'] = ['status' => $status];
        return response($response);
    }

    public function show($id)
    {
        $this->authorize('update', MembershipType::class);

        $membership_type = MembershipType::find($id);
        $status = 200;

        $response['data'] = $membership_type;
        $response['payload'] = ['status' => $status];
        return response($response);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', MembershipType::class);

        $membership_type = MembershipType::find($id);
        $membership_type->title = $request->title;
        $membership_type->percent = $request->percent;
        $membership_type->prefix = $request->prefix ? $request->prefix : '';
        $membership_type->type = $request->type ? $request->type : 'free';
        $membership_type->limit_price = $request->limit_price ? $request->limit_price : 0;
        if ($request->has('note')) {
            $membership_type->note = $request->note;
        }
        $membership_type->save();
        $status = 200;

        $response['data'] = $membership_type;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function destroy($id)
    {
        $this->authorize('delete', MembershipType::class);
        
        $membership_type = MembershipType::find($id);
        $membership_type->delete();

        return response(true);
    }
}
