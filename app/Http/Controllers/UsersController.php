<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Province;
use App\Models\SoumDistrict;
use App\Models\User;
use App\Models\Role;
use App\Models\Settings;
use App\Models\Branch;
use App\Mail\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', User::class);

        $search = $request->search;
        $sortColumn = $request->sort ?? 'id'; // Default sort by ID if not provided
        $sortOrder = strtolower($request->order) === 'desc' ? 'desc' : 'asc'; // Ensure order is valid
        $where = '1 = 1 and users.deleted_at IS NULL';
        $filter = '1';
        if($search) {
            $where = '(users.lastname like "%'.$search.'%" or users.firstname like "%'.$search.
                '%" or users.registerno like "%'.$search.'%" or users.phone like "%'.$search.'%" or users.phone2 like "%'.$search.
                '%" or users.email like "%'.$search.'%" or branches.name like "%'.$search.'%") and users.deleted_at IS NULL';
        }
        if($request->filter_role) {
            $role = Role::where('name', '=', $request->filter_role)->first();
            $filter = 'role_id = '.$role->id;
        }

        $users = User::selectRaw('users.id, users.lastname, users.firstname, users.firstname as name, users.registerno, users.phone, users.address_id, users.show_in_online_booking,
                        users.phone2, users.email, users.branch_id, users.role_id, users.status, users.avatar, branches.name as branch_name')
                    ->leftJoin('branches', 'branches.id', 'users.branch_id')
                    ->whereRaw($where)
                    ->whereRaw($filter)
                    ->orderBy($sortColumn, $sortOrder)
                    ->paginate($request->items_per_page)
                    ->withQueryString();
        $users_response = [];
        foreach ($users as $user) {
            $user_arr = $user->attributesToArray();
            $users_response[] = $this->updateUserFormat($user_arr, $user);
        }
        $payload = [
            'pagination' => $users,
            'status' => 200
        ];

        $response['data'] = $users_response;
        $response['payload'] = $payload;
        return response($response);
    }


    public function updateUserFormat($user_arr, $user)
    {
        $initials = [];
        $settings = Settings::find(1);

        if($user->avatar) {
            $path = env("APP_URL") . '/'.env("APP_PUBLIC_STORAGE").'/user_images/';
            $image = [
                'name' => $user->avatar,
                'path' => $user->avatar,
                'preview' => $path . '' . $user->avatar,
            ];
        }
        else {
            $state = symbolLabel();
            $label = mb_substr($user->firstname, 0, 1);

            $initials['label'] = mb_strtoupper($label);
            $initials['state'] = $state;
        }

        $address = $user->address_id > 0 ? $user->address : null;

        $user_data = [
            ...$user_arr,
            'province_id' => $address?->province_id,
            'soum_district_id' => $address?->soum_district_id,
            'street' => $address ? $address->street : '',
            'street1' => $address ? $address->street1 : '',
            'address' =>  $address ? $address->province->name . ', ' . $address->soumDistrict->name . ', ' .
                $address->street . ', ' . $address->street1 : '',
            'phone2' => $user->phone2 ? $user->phone2 : '',
            'avatar' => $user->avatar ? [$image] : [],
            'avatar_url' => $user->avatar ? $path . '' . $user->avatar : '',
            'role' => $user->role_id ? $user->role->name : '',
            'show_in_online_booking' => $user->show_in_online_booking == 1 ? true : false,
            'initials' => $initials,
        ];

        return $user_data;
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $user = [];
        $address = '';

        $user_role_available = $this->checkUserLimit($request->role_id);
        if(!$user_role_available) {
            $status = 201;

            $result['data'] = $user;
            $result['payload'] = ['status' => $status];
            return response($result);
        }

        $request->validate([
            'email' => 'required|string:max:255|unique:users'
        ]);

        if ($request->province_id) {
            $address = new Address;
            $address->province_id = $request->province_id;
            $address->soum_district_id = $request->soum_district_id;
            $address->street = $request->street;
            $address->street1 = $request->street1;
            $address->save();
        }

        $branch_id = '';
        if(isset($request->branch_id) && count($request->branch_id) > 0) {    
            if($request->role_id == 3){
                foreach ($request->branch_id as $branch) {
                    $branch_id .= $branch['value'].',';
                }
            }
            else 
                if(isset($request->branch_id[0]))
                    $branch_id = $request->branch_id[0]['value'];
                elseif(isset($request->branch_id['value']))
                    $branch_id = $request->branch_id['value'];
                else    
                    $branch_id = '';
        }

        // new field should add to user model
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'registerno' => $request->registerno,
            'email' => $request->email,
            'phone' => $request->phone,
            'phone2' => $request->phone2 ? $request->phone2 : '',
            'address_id' => $address ? $address->id : 0,
            'status' => $request->status,
            'show_in_online_booking' => $request->show_in_online_booking ? 1 : 0,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'branch_id' => $branch_id ? $branch_id : '1',
        ]);

        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $user->avatar = $image_name;
            $user->save();
        }
        $status = 200;

        $result['data'] = $user;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function show($id)
    {
        $this->authorize('update', User::class);

        $user_data = [];
        $provinces = [];
        $soum_districts = [];
        $roles = [];

        if ($id !== 'null') {
            $user = User::find($id);
            $user_arr = $user->attributesToArray();
            $user_data = $this->updateUserFormat($user_arr, $user);
        }

        $provinces = Province::getProvinces();
        $soum_districts = SoumDistrict::getDistricts();
        $roles = Role::select('id as value', 'name as label')->get();
        $branches = Branch::select('id as value', 'name as label')->get();

        $data['provinces'] = $provinces;
        $data['soumDistricts'] = $soum_districts;
        $data['user'] = $user_data;
        $data['roles'] = $roles;
        $data['branches'] = $branches;
        $status = 200;

        $result['data'] = $data;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function upload($folder = 'user_images', $key = 'file', $validation = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|sometimes')
    {
        $file = null;
        if (request()->hasFile($key)) {
            $file = Storage::putFile($folder, request()->file($key), 'public');
        }

        return $file;
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

    public function update(Request $request, $id)
    {
        $this->authorize('update', User::class);
        $user = [];

        $user_role_available = $this->checkUserLimit($request->role_id, $id);
        if(!$user_role_available) {
            $status = 201;

            $result['data'] = $user;
            $result['payload'] = ['status' => $status];
            return response($result);
        }

        $branch_id = '';
        if(isset($request->branch_id) && count($request->branch_id) > 0) {
            if($request->role_id == 3) {
                foreach ($request->branch_id as $branch) {
                    $branch_id .= $branch['value'].',';
                }
            }
            else {
                if(isset($request->branch_id[0]))
                    $branch_id = $request->branch_id[0]['value'];
                elseif(isset($request->branch_id['value']))
                    $branch_id = $request->branch_id['value'];
                else    
                    $branch_id = '';
            }
        }


        $user = User::find($id);
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->phone2 = $request->phone2;
        $user->registerno = $request->registerno;
        $user->status = $request->status;
        $user->role_id = $request->role_id;
        $user->show_in_online_booking = $request->show_in_online_booking;
        $user->branch_id = $branch_id ? $branch_id : '1';

        if($request->province_id && $request->soum_district_id) {
            $address = $user->address;
            if (!$address)
                $address = new Address;
            $address->province_id = $request->province_id;
            $address->soum_district_id = $request->soum_district_id;
            $address->street = $request->street;
            $address->street1 = $request->street1;
            $address->save();

            $user->address_id = $address->id;
        }
        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $user->avatar = $image_name;
        }
        $user->save();
        $status = 200;

        $response['data'] = $user;
        $response['payload'] = ['status' => $status];
        return response($response);
    }

    public function destroy($id)
    {
        $this->authorize('delete', User::class);

        $user = User::find($id);
        $status = 201;
        if(!$user->role_id || $user->role->name != 'administrator') {
            $user->email = $user->email.'-prev';
            $user->status = 'deactive';
            $user->delete();
            $status = 200;
        }

        return response($status);
    }

    public function updateEmail(Request $request, $id) {
        $this->authorize('update', User::class);

        $request->validate([
            'email' => 'required|string:max:255|unique:users'
        ]);

        $user = [];
        $status = 201;

        $user = User::find($id);
        if ($user) {
            if (Hash::check($request->confirmPassword, $user->password)) {
                $user->email = $request->email;
                $user->save();

                $status = 200;
            }
        }

        $result['data'] = $user;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function updatePassword(Request $request, $id) {
        $this->authorize('update', User::class);

        $user = [];
        $status = 201;

        $user = User::find($id);
        if ($user) {
            // if (Hash::check($request->currentPassword, $user->password)) {
                $user->password = Hash::make($request->newPassword);
                $user->save();

                $status = 200;
            // }
        }

        $result['data'] = $user;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function checkUserLimit($role_id, $id=null) {
        $role_name = Role::find($role_id)->name;
        $user = [];
        $query = $id ? 'id != '.$id :  '1 = 1';

        if($role_name == 'user') {
            $user_role_users = User::where('role_id', '=', $role_id)->where('status', '=', 'active')->whereRaw($query)->get();
            $settings = Settings::find(1);

            if(count($user_role_users) >= $settings->user_limit) {
                return False;
            }
        }

        return True;
    }

}
