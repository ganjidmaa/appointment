<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Service;
use App\Models\ServiceResource;
use App\Models\ServiceType;
use App\Models\ServiceUser;
use App\Models\ServiceBranch;
use App\Models\Branch;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Service::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'services.name like "%'.$search.'%" or price like "%'.$search.'%"
                        or services.code like "%'.$search.'%" or service_types.name like "%'.$search.'%"';
        }

        $services_response = [];
        $services = DB::table('services')
            ->selectRaw('services.id, services.category_id, services.is_category, services.price, services.name, services.code,
                services.desc, services.status, services.type, services.duration, services.allow_resources, services.is_app_option, 
                service_types.name as type_name')
            ->leftJoin('service_types', 'service_types.id', 'services.type')
            ->whereRaw($where)
            ->whereRaw('service_types.deleted_at IS NULL and services.deleted_at IS NULL')
            // ->orderByRaw('CASE WHEN category_id = 0 THEN services.id ELSE category_id END asc')
            ->orderByRaw('services.code asc, services.category_id asc, services.id')
            ->paginate($request->items_per_page)->withQueryString();

        //category-n door ni services ni gardagaar order-n tuld umnuh query-g dahij ashiglasan.
        $categories = DB::table('services')
            ->selectRaw('services.id, services.category_id, services.is_category, services.price, services.name, services.code,
                services.desc, services.status, services.type, services.duration, services.allow_resources, 
                service_types.name as type_name')
            ->leftJoin('service_types', 'service_types.id', 'services.type')
            ->whereRaw($where)
            ->whereRaw('service_types.deleted_at IS NULL and services.deleted_at IS NULL')
            ->whereRaw('services.is_category = 1')
            ->get();

        $category_id = 0;
        foreach($categories as $category) {
            $services_response = [...$services_response, $category];

            foreach($services as $service) {
                if($service->is_category == 0 && $service->category_id == $category->id) {
                    $services_response = [...$services_response, $service];
                }
            }
        }

        $status = 200;
        $payload = [
            'pagination' => $services,
            'status' => $status
        ];

        $response['data'] = $services_response;
        $response['payload'] = $payload;
        return response($response);
    }

    public function getServiceFormat($datas, $service)
    {
        $checked_resources = [];
        foreach ($service->resources as $resource) {
            $checked_resources[] = strval($resource->resource_id);
        }

        $checked_users = [];
        foreach ($service->users as $user) {
            $checked_users[] = strval($user->user_id);
        }

        $checked_branches = [];
        foreach ($service->branches as $branch) {
            $checked_branches[] = strval($branch->branch_id);
        }

        $formatted_data = [
            ...$datas,
            'allow_resources' => $service->allow_resources == 1 ? true : false,
            'checked_resources' => $checked_resources,
            'available_all_user' => $service->available_all_user == 1 ? true : false,
            'checked_users' => $checked_users,
            'available_all_branch' => $service->available_all_branch == 1 ? true : false,
            'is_app_option' => $service->is_app_option == 1 ? true : false,
            'checked_branches' => $checked_branches
        ];

        return $formatted_data;
    }

    public function store(Request $request)
    {
        $this->authorize('create', Service::class);
        $settings = Settings::find(1);
        $service = new Service();
        $service->name = $request->name;
        $service->price = str_replace(',', '', $request->price);
        $service->desc = $request->desc;
        $service->duration = $request->duration;
        $service->type = $request->type;
        if($request->category_id != null){
        $service->category_id = $request->category_id;
        $service->code = $request->code;
        $service->allow_resources = $request->allow_resources;
        $service->available_all_user = $request->available_all_user;
        $service->available_all_branch = $request->available_all_branch;
        $service->is_app_option = $request->is_app_option;
        $service->save();

        if ($service->allow_resources) {
            foreach ($request->checked_resources as $resource) {
                ServiceResource::create([
                    'service_id' => $service->id,
                    'resource_id' => (int)$resource,
                ]);
            }
        }

        if(!$service->available_all_user) {
            foreach($request->checked_users as $user) {
                ServiceUser::create([
                    'service_id' => $service->id,
                    'user_id' => (int)$user
                ]);
            }
        }

        if($settings->has_branch && !$service->available_all_branch) {
            foreach($request->checked_branches as $branch) {
                ServiceBranch::create([
                    'service_id' => $service->id,
                    'branch_id' => (int)$branch
                ]);
            }
        }
        }else{
            $app_option_category_id = Service::where('is_app_option', 1)->where('is_category', 1)->first()->id;
            $service->category_id = $app_option_category_id;
            $service->allow_resources = false;
            $service->available_all_user = true;
            $service->available_all_branch = true;
            $service->is_app_option = true;
            $service->save();
        }
        
        $status = 200;

        $response['data'] = $service;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function show($id)
    {
        $this->authorize('update', Service::class);

        $services_response = [];
        if ($id) {
            $service = Service::find($id);
            $service_arr = $service->attributesToArray();
            $services_response = $this->getServiceFormat($service_arr, $service);
        }
        $categories = Service::select('id as value', 'name as label')->where('is_category', '=', true)->get();
        $resources = Resource::all();
        $service_types = ServiceType::select('id as value', 'name as label')->get();
        $branches = Branch::all();
        $users = User::select('users.id', 'users.firstname')
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->where('status', 'active')
            ->where('roles.name', 'user')
            ->get();

        $data['categories'] = $categories;
        $data['resources'] = $resources;
        $data['service'] = $services_response;
        $data['serviceTypes'] = $service_types;
        $data['branches'] = $branches;
        $data['users'] = $users;
        $status = 200;

        $response['data'] = $data;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Service::class);

        $service = Service::find($id);
        $service->name = $request->name;
        $service->category_id = $request->category_id;
        $service->price = str_replace(',', '', $request->price);
        $service->status = $request->status;
        $service->type = $request->type;
        $service->desc = $request->desc;
        $service->duration = $request->duration;
        $service->code = $request->code;
        $service->allow_resources = $request->allow_resources;
        $service->available_all_user = $request->available_all_user;
        $service->available_all_branch = $request->available_all_branch;
        $service->is_app_option = $request->is_app_option;
        $service->save();

        $settings = Settings::find(1);

        ServiceResource::where('service_id', $service->id)->delete();

        if ($service->allow_resources) {
            foreach ($request->checked_resources as $resource) {
                ServiceResource::create([
                    'service_id' => $service->id,
                    'resource_id' => (int)$resource,
                ]);
            }
        }

        ServiceUser::where('service_id', $service->id)->delete();
        if(!$service->available_all_user) {
            foreach($request->checked_users as $user) {
                ServiceUser::create([
                    'service_id' => $service->id,
                    'user_id' => (int)$user
                ]);
            }
        }
        
        ServiceBranch::where('service_id', $service->id)->delete();
        if($settings->has_branch && !$request->available_all_branch) {
            foreach($request->checked_branches as $branch) {
                ServiceBranch::create([
                    'service_id' => $service->id,
                    'branch_id' => (int)$branch
                ]);
            }
        }
        $status = 200;

        $response['data'] = $service;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function destroy($id) {
        $this->authorize('delete', Service::class);

        $service = Service::find($id);
        $service->delete();

        return true;
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', Service::class);

        $request->validate(['name' => 'required']);

        $category = new Service();
        $category->name = $request->name;
        $category->is_category = true;
        $category->is_app_option = $request->is_app_option == 1 ? true : false;
        $category->save();
        $status = 200;

        $response['data'] = $category;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function showCategory($id)
    {
        $this->authorize('update', Service::class);

        $category = Service::find($id);
        $category->is_app_option = $category->is_app_option == 1 ? true : false;
        $status = 200;

        $response['data'] = $category;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function updateCategory(Request $request, $id)
    {
        $this->authorize('update', Service::class);

        $category = Service::find($id);
        $category->name = $request->name;
        $category->is_app_option = $request->is_app_option == 1 ? true : false;
        $category->save();
        $status = 200;

        $response['data'] = $category;
        $response['payload'] = ['status' => $status];

        return response($response);
    }

    public function destroyCategory($id) {
        $this->authorize('delete', Service::class);

        $service = Service::find($id)->delete();
        $services = Service::where('category_id', $id)->delete();

        return true;
    }

    public function appOptionIndex(Request $request)
    {
        $this->authorize('view', Service::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'services.is_app_option = 1 and (services.name like "%'.$search.'%" or price like "%'.$search.'%"
                        or services.code like "%'.$search.'%" or service_types.name like "%'.$search.'%")';
        }

        $services_response = [];
        $services = DB::table('services')
            ->selectRaw('services.id, services.category_id, services.is_category, services.price, services.name, services.code,
                services.desc, services.status, services.type, services.duration, services.allow_resources, 
                service_types.name as type_name, services.is_app_option')
            ->leftJoin('service_types', 'service_types.id', 'services.type')
            ->whereRaw($where)
            ->whereRaw('service_types.deleted_at IS NULL and services.deleted_at IS NULL')
            ->where('is_app_option', 1)
            // ->orderByRaw('CASE WHEN category_id = 0 THEN services.id ELSE category_id END asc')
            ->orderByRaw('services.code asc, services.category_id asc, services.id')
            ->paginate($request->items_per_page)->withQueryString();

            foreach($services as $service) {
                if($service->is_category == 0) {
                    $services_response = [...$services_response, $service];
                }
            }

        $status = 200;
        $payload = [
            'pagination' => $services,
            'status' => $status
        ];

        $response['data'] = $services_response;
        $response['payload'] = $payload;
        return response($response);
    }
}
