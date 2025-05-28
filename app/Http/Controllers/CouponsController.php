<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponService;
use App\Models\CouponCode;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CouponsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('view', Coupon::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'value like "%'.$search.'%" or price like "%'.$search.
                '%" or start_date like "%'.$search.'%"'.
                ' or end_date like "%'.$search.'%" or title like "%'.$search.'%"';
        }
        $coupons_response = [];
        $coupons = Coupon::where('status', '=', 1)
            ->whereRaw($where)
            ->paginate($request->items_per_page)->withQueryString();
        foreach ($coupons as $coupon) {
            $coupon_arr = $coupon->attributesToArray();
            $coupons_response[] = [...$coupon_arr];
        }
        $payload = [
            'pagination' => [...$coupons->toArray(), 'data' => ''],
            'status' => 200
        ];

        $responce['data'] = $coupons_response;
        $responce['payload'] = $payload;

        return response($responce);
    }

    public function getMasterData()
    {
        $this->authorize('view', Coupon::class);

        $services = Service::selectRaw('services.id, services.name, 
            services.category_id, services.price, services.desc, services.duration, 
            services.status, service_categories.name as category_name')
            ->leftJoin('services as service_categories', 'services.id', '=', 'services.category_id')
            ->where('services.status', '=', 1)
            ->where('services.is_category', '=', 0)
            ->get();
        $service_categories = Service::where('is_category', '=', 1)->get();

        $data['services'] = $services;
        $data['service_categories'] = $service_categories;
        $status = 200;

        $responce['data'] = $data;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Coupon::class);

        $coupon = new Coupon();
        $coupon->title = $request->title;
        $coupon->value = str_replace(',', '', $request->value);
        $coupon->price = str_replace(',', '', $request->price);
        $coupon->start_date = $request->start_date;
        $coupon->end_date = $request->end_date;
        $request->sell_limit ? $coupon->sell_limit = $request->sell_limit : null;
        $coupon->limit_number = str_replace(',', '', $request->limit_number);
        $coupon->is_all_services = $request->is_all_services;
        $coupon->desc = $request->desc;
        $coupon->type = $request->type;
        $coupon->save();

        foreach ($request->selected_services as $selected_service) {
            foreach ($selected_service['service_ids'] as $service_id) {
                $coupon_service = new CouponService();
                $coupon_service->category_id = $selected_service['category_id'];
                $coupon_service->coupon_id = $coupon->id;
                $coupon_service->service_id = $service_id;
                $coupon_service->save();
            }
        }
        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $coupon->image = $image_name;
            $coupon->save();
        }

        $status = 200;

        $responce['data'] = $coupon;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function show($id)
    {
        $this->authorize('update', Coupon::class);

        $coupon = Coupon::find($id);
        $categories = CouponService::select('category_id')->where('coupon_id', $coupon->id)->groupBy('category_id')->get();
        $data = [];

        foreach ($categories as $category) {
            $service_ids = [];
            $coupon_services = CouponService::select('service_id')
                ->where('coupon_id', $coupon->id)
                ->where('category_id', $category->category_id)
                ->get();
            foreach ($coupon_services as $coupon_service) {
                $service_ids[] = $coupon_service->service_id;
            }

            $data[] = ['category_id' => $category->category_id, 'group_selection' => true, 'service_ids' => $service_ids];
        }

        $coupon->selected_services = $data;
        $status = 200;

        $responce['data'] = $coupon;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Coupon::class);

        $coupon = Coupon::find($id);
        $coupon->title = $request->title;
        $coupon->value = str_replace(',', '', $request->value);
        $coupon->price = str_replace(',', '', $request->price);
        $coupon->start_date = $request->start_date;
        $coupon->end_date = $request->end_date;
        $request->sell_limit ? $coupon->sell_limit = $request->sell_limit : null;
        $coupon->limit_number = str_replace(',', '', $request->limit_number);
        $coupon->is_all_services = $request->is_all_services;
        $coupon->desc = $request->desc;
        $coupon->type = $request->type;
        $coupon->save();

        $old_coupon_services = CouponService::where('coupon_id', $coupon->id)->delete();
        foreach ($request->selected_services as $selected_service) {
            foreach ($selected_service['service_ids'] as $service_id) {
                $coupon_service = new CouponService();
                $coupon_service->category_id = $selected_service['category_id'];
                $coupon_service->coupon_id = $coupon->id;
                $coupon_service->service_id = $service_id;
                $coupon_service->save();
            }
        }
        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $coupon->image = $image_name;
            $coupon->save();
        }

        $status = 200;

        $responce['data'] = $coupon;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Coupon::class);

        $coupon = Coupon::find($id);
        $coupon->status = 0;
        $coupon->save();

        return response(true);
    }

    public function base64ToFile($encoded_file)
    {
        $image_64 = $encoded_file;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $image_name = Str::random(10) . '.' . $extension;
        Storage::disk('user_images')->put($image_name, base64_decode($image));

        return $image_name;
    }
}
