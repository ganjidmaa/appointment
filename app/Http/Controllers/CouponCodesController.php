<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use App\Models\Coupon;
use App\Models\CouponService;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class CouponCodesController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('view', CouponCode::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'coupon_codes.value like "%'.$search.'%" or coupon_codes.redeemed like "%'.$search.
                '%" or coupon_codes.code like "%'.$search.'%" or coupon_codes.start_date like "%'.$search.'%"'.
                ' or coupon_codes.end_date like "%'.$search.'%" or coupons.title like "%'.$search.'%"';
        }

        $coupon_codes = CouponCode::selectRaw('coupon_codes.id, coupon_codes.value, coupons.title, 
                coupon_codes.status as status, coupon_codes.redeemed, coupon_codes.code, usage_count,
                coupon_codes.type, coupon_codes.start_date, coupon_codes.end_date, coupon_codes.created_at')
            ->leftJoin('coupons', 'coupons.id', 'coupon_codes.coupon_id')
            ->whereRaw($where)
            ->orderBy('coupon_codes.id', 'desc')
            ->paginate($request->items_per_page)->withQueryString();

        $coupons_data = [];
        foreach($coupon_codes as $coupon_code) {
            $coupons_data[] = [...$coupon_code->attributesToArray(),
                'usable_balance' => $coupon_code->value - $coupon_code->redeemed,
            ];
        }

        $status = 200;    
        $payload = [
            'pagination' => $coupon_codes,
            'status' => $status
        ];

        $responce['data'] = $coupon_codes->items();
        $responce['payload'] = $payload;
        return response($responce);
    }

    public function getMasterData()
    {
        $today = Carbon::now()->format('Y-m-d');
        $coupons = Coupon::where('status', '=', 1)->where('end_date', '>=', $today)->get();
        $status = 200;

        foreach($coupons as $coupon) {
            $count = CouponCode::where('coupon_id', $coupon->id)->count();
            $coupon_services = 'Бүх үйлчилгээ';
            if($coupon->is_all_services == 0) { 
                $count_services = CouponService::select('service_id')
                    ->where('coupon_id', $coupon->id)
                    ->count();
                $coupon_services = $count_services.' үйлчилгээ ';
            }
            
            $coupon_arr = $coupon->attributesToArray();
            $coupon_arr = [
                ...$coupon_arr,
                'services' => $coupon_services,
                'code_count' => $count ?? 0,
            ];
            $coupons_data[] = $coupon_arr;
        }

        $responce['data'] = $coupons_data;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function store(Request $request)
    {
        $coupon = Coupon::find($request->coupon_id);
        $count = CouponCode::where('coupon_id', $coupon->id)->count();

        $coupon_code = [];
        if ($coupon->sell_limit == 1) {
            $remaining_limit = $coupon->limit_number - $count;
            if ($request->create_number > $remaining_limit) {
                $status = 201;
                $responce['data'] = 'Таны оруулсан тоо хэтэрсэн байна.';
                $responce['payload'] = ['status' => $status];
                return response($responce);
            }
        }
   
        for($i = 0; $i < $request->create_number; $i++) {
            $code_slug = ($coupon->type == 1 && $request->code) ? $request->code : $this->getPrefix().''.mt_rand(100000,999999);

            $coupon_code = new CouponCode();
            $coupon_code->coupon_id = $coupon->id;
            $coupon_code->value = $coupon->value;
            $coupon_code->code = $code_slug;
            $coupon_code->start_date = $coupon->start_date;
            $coupon_code->end_date = $coupon->end_date;
            $coupon_code->type = $coupon->type == 0 ? 'personal' : 'mass';
            $coupon_code->save();
        }

        $coupon->save();

        $status = 200;
        $responce['data'] = $coupon_code;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function getPrefix() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 1; $i <= 2; $i++) {
            $index = mt_rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
      
        return $randomString;
    }

    public function show(Request $request)
    {
        $search = $request->search;
        $id = $request->id ? $request->id : 0;

        $whereCondition = $id > 0 ? 
            'coupon_codes.id = '.$id : 
            'coupon_codes.code like "%'.$search.'%"';

        $coupon_codes = CouponCode::selectRaw('coupon_codes.id, coupon_codes.value, coupon_codes.redeemed, coupon_codes.code,
                coupon_codes.coupon_id, coupon_codes.status, coupon_codes.type, coupon_codes.start_date, coupon_codes.end_date,   
                coupons.title, coupons.is_all_services, coupons.sell_limit, coupons.limit_number')
            ->leftJoin('coupons', 'coupons.id', 'coupon_codes.coupon_id')
            ->whereRaw($whereCondition)
            ->get();
        $coupons_data = [];
        foreach($coupon_codes as $coupon_code) {
            $services = [];
            if($coupon_code->is_all_services == 0) {
                $services = CouponService::selectRaw('service_id, services.name as label, services.id as value')
                    ->leftJoin('services', 'services.id', 'service_id')
                    ->where('coupon_id', $coupon_code->coupon_id)
                    ->get();
            }

            $payments = Payment::selectRaw('payments.id, payments.amount, payments.created_at, 
                    customers.firstname as customer_name, customers.lastname')
                ->whereRaw('payments.coupon_id = '.$coupon_code->id.' and payments.type = "coupon"')
                ->leftJoin('invoices', 'invoices.id', 'invoice_id')
                ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
                ->get();

            $coupon_arr = $coupon_code->attributesToArray();
            $coupon_arr = [
                ...$coupon_arr,
                'services' => $services,
                'payments' => $payments,
                'is_all_services' => $coupon_code->is_all_services == 1 ? true : false,
            ];
        }
        
        return $coupon_arr;
    }

    public function edit(CouponCode $couponCodes)
    {
        //
    }

    public function update(Request $request)
    {
        $this->authorize('update', Coupon::class);

        $coupon = CouponCode::find($request->id);
        $coupon->status = 'invalid';
        $coupon->save();

        $responce['data'] = $coupon;
        $responce['payload'] = ['status' => 200];

        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Coupon::class);

        $coupon = CouponCode::find($id);
        $coupon->delete();

        return response(true);
    }
}
