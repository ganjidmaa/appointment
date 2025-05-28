<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResourcesController extends Controller
{

    public function index(Request $request)
    { 
        $this->authorize('view', Resource::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'name like "%'.$search.'%"';
        }
        $resources = Resource::whereRaw($where)->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $resources,
            'status' => $status
        ];

        $responce['data'] = $resources->items();
        $responce['payload'] = $payload;

        return response($responce);
    }

    public function getResources()
    {
        $this->authorize('view', Resource::class);

        $resources = Resource::where('status', 1)->get();

        $responce['data'] = $resources;
        $responce['payload'] = [];

        return response($responce);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Resource::class);
   
        $resource = new Resource();
        $resource->name = $request->name;
        $resource->status = $request->status;
        $resource->desc = $request->desc;
        $resource->save();

        $status = 200;

        $responce['data'] = $resource;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function show($id)
    {
        $this->authorize('update', Resource::class);

        $resource = Resource::find($id);
        $status = 200;

        $responce['data'] = $resource;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Resource::class);

        $resource = Resource::find($id);
        $resource->name = $request->name;
        $resource->status = $request->status;
        $resource->desc = $request->desc;
        $resource->save();

        $status = 200;

        $responce['data'] = $resource;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Resource::class);

        $resource = Resource::find($id);
        $resource->delete();

        return true;
    }
}
