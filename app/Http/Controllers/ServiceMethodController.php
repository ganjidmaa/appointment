<?php

namespace App\Http\Controllers;

use App\Models\ServiceMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServiceMethodController extends Controller
{
    public function index(Request $request)
    {

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'service_methods.name like "%'.$search.'%" or service_methods.content like "%'.$search.'%"';
        }
        $service_methods = DB::table('service_methods')
            ->selectRaw('service_methods.id, service_methods.name, service_methods.content')
            ->whereRaw($where)
            ->orderByRaw('service_methods.id asc')
            ->get();
        
        $service_methods_paginate = DB::table('service_methods')
        ->selectRaw('service_methods.id, service_methods.name, service_methods.content')
        ->whereRaw($where)
        ->orderByRaw('service_methods.id asc')
        ->paginate($request->items_per_page)->withQueryString();


        $status = 200;
        $payload = [
            'pagination' => $service_methods_paginate,
            'status' => $status
        ];

        $response['data'] = $service_methods;
        $response['payload'] = $payload;
        return response($response);
    }

    public function getServiceMethod($id){
        $result['data'] = ServiceMethods::find($id);
        $result['status'] = 200;
        return $result;
    }

    public function deleteServiceMethod($id){
        ServiceMethods::find($id)->delete();
        $result['data'] = true;
        return $result;
    }

    public function updateOrCreate(Request $request){
        if ($request->id === 0){
            $service_method = new ServiceMethods();
        }else{
            $service_method = ServiceMethods::find($request->id);
        }
        $service_method->name = $request->name;
        $service_method->content = $request->content;
        $service_method->save();
        return $service_method;
    }

}
