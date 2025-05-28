<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscountsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Discount::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'value like "%'.$search.'%" or limit_price like "%'.$search.
            '%" or start_date like "%'.$search.'%"'.
            ' or end_date like "%'.$search.'%" or title like "%'.$search.'%"';
        }
        
        $discounts = Discount::whereRaw($where)->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $discounts,
            'status' => $status
        ];

        $responce['data'] = $discounts->items();
        $responce['payload'] = $payload;

        return response($responce);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Discount::class);

        $discount = new Discount();
        $discount->title = $request->title;
        $discount->value = str_replace(',', '', $request->value);
        $discount->type = $request->type;
        $discount->start_date = $request->start_date;
        $discount->end_date = $request->end_date;
        $request->limit_price ? $discount->limit_price = str_replace(',', '', $request->limit_price) : null;
        $discount->is_all_services = $request->is_all_services;
        $discount->desc = $request->desc;
        $discount->save();

        foreach ($request->selected_services as $selected_service) {
            foreach ($selected_service['service_ids'] as $service_id) {
                $discount_service = new DiscountService();
                $discount_service->category_id = $selected_service['category_id'];
                $discount_service->discount_id = $discount->id;
                $discount_service->service_id = $service_id;
                $discount_service->save();
            }
        }
        $status = 200;

        $responce['data'] = $discount;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function getMasterData()
    {
        $this->authorize('view', Discount::class);

        $services = Service::selectRaw('services.id, services.name, services.category_id, 
            services.price, services.desc, services.duration, services.status')
            ->where('services.status', '=', 1)
            ->where('services.is_category', '=', 0)
            ->get();
        $service_categories = Service::selectRaw('services.id, services.name,
            services.status, services.name as category_name')
            ->where('services.status', '=', 1)
            ->where('services.is_category', '=', 1)
            ->get();

        $data['services'] = $services;
        $data['service_categories'] = $service_categories;
        $status = 200;

        $responce['data'] = $data;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function show($id)
    {
        $this->authorize('update', Discount::class);

        $discount = Discount::find($id);
        $categories = DiscountService::select('category_id')->where('discount_id', $discount->id)->groupBy('category_id')->get();
        $data = [];

        foreach ($categories as $category) {
            $service_ids = [];
            $discount_services = DiscountService::select('service_id')
                ->where('discount_id', $discount->id)
                ->where('category_id', $category->category_id)
                ->get();
            foreach ($discount_services as $discount_service) {
                $service_ids[] = $discount_service->service_id;
            }

            $data[] = ['category_id' => $category->category_id, 'group_selection' => true, 'service_ids' => $service_ids];
        }
        $discount->selected_services = $data;
        $discount->is_all_services = $discount->is_all_services === 1 ? true : false;
        $status = 200;

        $responce['data'] = $discount;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Discount::class);

        $discount = Discount::find($id);
        $discount->title = $request->title;
        $discount->value = str_replace(',', '', $request->value);
        $discount->type = $request->type;
        $discount->start_date = $request->start_date;
        $discount->end_date = $request->end_date;
        $request->limit_price ? $discount->limit_price = str_replace(',', '', $request->limit_price) : null;
        $discount->is_all_services = $request->is_all_services;
        $discount->desc = $request->desc;
        $discount->save();

        $old_discount_services = DiscountService::where('discount_id', $discount->id)->delete();
        foreach ($request->selected_services as $selected_service) {
            foreach ($selected_service['service_ids'] as $service_id) {
                $discount_service = new DiscountService();
                $discount_service->category_id = $selected_service['category_id'];
                $discount_service->discount_id = $discount->id;
                $discount_service->service_id = $service_id;
                $discount_service->save();
            }
        }
        $status = 200;

        $responce['data'] = $discount;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Discount::class);

        $discount = Discount::find($id);
        $discount->delete();

        return response(true);
    }
}
