<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\SoumDistrict;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Image;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

 // Link image type to correct image loader and saver
// - makes it easier to add additional types later on
// - makes the function easier to read
const IMAGE_HANDLERS = [
    IMAGETYPE_JPEG => [
        'load' => 'imagecreatefromjpeg',
        'save' => 'imagejpeg',
        'quality' => 100
    ],
    IMAGETYPE_PNG => [
        'load' => 'imagecreatefrompng',
        'save' => 'imagepng',
        'quality' => 0
    ],
    IMAGETYPE_GIF => [
        'load' => 'imagecreatefromgif',
        'save' => 'imagegif'
    ]
];

/**
 * @param $src - a valid file location
 * @param $dest - a valid file target
 * @param $targetWidth - desired output width
 * @param $targetHeight - desired output height or null
 */
function createThumbnail($src, $dest, $targetWidth, $targetHeight = null) {

    // 1. Load the image from the given $src
    // - see if the file actually exists
    // - check if it's of a valid image type
    // - load the image resource

    // get the type of the image
    // we need the type to determine the correct loader
    $type = exif_imagetype($src);

    // if no valid type or no handler found -> exit
    if (!$type || !IMAGE_HANDLERS[$type]) {
        return null;
    }

    // load the image with the correct loader
    $image = call_user_func(IMAGE_HANDLERS[$type]['load'], $src);

    // no image found at supplied location -> exit
    if (!$image) {
        return null;
    }


    // 2. Create a thumbnail and resize the loaded $image
    // - get the image dimensions
    // - define the output size appropriately
    // - create a thumbnail based on that size
    // - set alpha transparency for GIFs and PNGs
    // - draw the final thumbnail

    // get original image width and height
    $width = imagesx($image);
    $height = imagesy($image);

    // maintain aspect ratio when no height set
    if ($targetHeight == null) {

        // get width to height ratio
        $ratio = $width / $height;

        // if is portrait
        // use ratio to scale height to fit in square
        if ($width > $height) {
            $targetHeight = floor($targetWidth / $ratio);
        }
        // if is landscape
        // use ratio to scale width to fit in square
        else {
            $targetHeight = $targetWidth;
            $targetWidth = floor($targetWidth * $ratio);
        }
    }

    // create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

    // set transparency options for GIFs and PNGs
    if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {

        // make image transparent
        imagecolortransparent(
            $thumbnail,
            imagecolorallocate($thumbnail, 0, 0, 0)
        );

        // additional settings for PNGs
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
    }

    // copy entire source image to duplicate image and resize
    imagecopyresampled(
        $thumbnail,
        $image,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );


    // 3. Save the $thumbnail to disk
    // - call the correct save method
    // - set the correct quality leve

    return call_user_func(
        IMAGE_HANDLERS[$type]['save'],
        $thumbnail,
        $dest,
        IMAGE_HANDLERS[$type]['quality']
    );
}
class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Customer::class);

        $search = $request->search;
        $sortColumn = $request->sort ?? 'id'; // Default sort by ID
        $sortOrder = strtolower($request->order) === 'desc' ? 'desc' : 'asc'; // Ensure valid order
        
        $where = '1 = 1';
        $filter = '1';

        if ($search) {
            $where = 'lastname LIKE "%'.$search.'%" OR firstname LIKE "%'.$search.
                '%" OR registerno LIKE "%'.$search.'%" OR phone LIKE "%'.$search.'%" OR phone2 LIKE "%'.$search.
                '%" OR email LIKE "%'.$search.'%" OR membership_code LIKE "%'.$search.'%" OR left_payment LIKE "%'.$search.
                '%" OR total_paid LIKE "%'.$search.'%"';
        }

        if ($request->filter_residue) {
            $filter = $request->filter_residue === 'payment_with_residue' ? 'left_payment > 0' : '(left_payment = 0 OR left_payment IS NULL)'; 
        }

        $customers = Customer::select('*', 'total_paid as total_payment', 'firstname as name')
            ->whereRaw($where)
            ->whereRaw($filter);

        // Apply sorting after defining the base query
        if ($sortColumn === 'total_payment') { 
            $customers->orderByRaw("CAST(total_payment AS UNSIGNED) $sortOrder");
        } elseif ($sortColumn === 'left_payment') {
            $customers->orderByRaw("CAST(left_payment AS UNSIGNED) $sortOrder");
        } else {
            $customers->orderBy($sortColumn, $sortOrder);
        }

        $customers = $customers->paginate($request->items_per_page)->withQueryString();

        // Format response
        $customers_response = [];
        foreach ($customers as $customer) {
            $customer_arr = $customer->attributesToArray();
            $customers_response[] = $this->getCustomerFormat($customer_arr, $customer);
        }

        return response([
            'data' => $customers_response,
            'payload' => [
                'pagination' => $customers,
                'status' => 200
            ]
        ]);
    }

    public function symbolLabel()
    {
        $colors = ['warning', 'primary', 'success', 'danger', 'info', 'dark'];
        $index = mt_rand(0, count($colors) - 1);
        return $colors[$index];
    }

    public function getCustomerFormat($customer_arr, $customer)
    {
        $initials = [];

        if($customer->avatar) {
            $path = env("APP_URL") . '/'.env("APP_PUBLIC_STORAGE").'/profile_images/';
            $image = [
                'name' => $customer->avatar,
                'path' => $customer->avatar,
                'preview' => $path . '' . $customer->avatar,
            ];
        } else {
            $state = $this->symbolLabel();
            $label = mb_substr($customer->firstname, 0, 1);

            $initials['label'] = mb_strtoupper($label);
            $initials['state'] = $state;
        }

        $address = $customer->address_id > 0 ? $customer->address : null;
        $total = $customer->appointments;
        $no_shows = $customer->noShowAppointments;
        $cancelled = $customer->cancelledAppointments;

        $customer_data = [
            ...$customer_arr,
            'province_id' => $address?->province_id,
            'soum_district_id' => $address?->soum_district_id,
            'street' => $address ? $address->street : '',
            'street1' => $address ? $address->street1 : '',
            'address' => $address ? $address->province->name . ', ' . $address->soumDistrict->name . ', ' .
                $address->street . ', ' . $address->street1 : '',
            'phone2' => $customer->phone2 ? $customer->phone2 : '',
            'avatar' => $customer->avatar ? [$image] : [],
            'avatar_url' => $customer->avatar ? $path . '' . $customer->avatar : '',
            'total_appointments' => count($total) + count($cancelled),
            'no_show_appointments' => count($no_shows),
            'cancelled_appointments' => count($cancelled),
            'initials' => $initials,
            'value' => $customer->id,
            'label' => $customer->firstname
        ];

        return $customer_data;
    }

    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);
        $request->validate([
            'firstname' => 'required|string|max:255',
        ]);

        $customer = [];
        $address = '';
        if ($request->province_id) {
            $address = new Address;
            $address->province_id = $request->province_id;
            $address->soum_district_id = $request->soum_district_id;
            $address->street = $request->street;
            $address->street1 = $request->street1;
            $address->save();
        }

        $customer = new Customer;
        $customer->firstname = $request->firstname;
        $customer->lastname = $request->lastname ? $request->lastname : '';
        $customer->registerno = $request->registerno ? $request->registerno : '';
        $customer->email = $request->email ? $request->email : '';
        $address ? $customer->address_id = $address->id : null;
        $customer->phone = $request->phone;
        $customer->phone2 = $request->phone2 ? $request->phone2 : '';
        $customer->desc = $request->desc;
        $customer->card_number = $request->card_number ? $request->card_number : '';
        $customer->surgery_card_number = $request->surgery_card_number ? $request->surgery_card_number : '';
        if ($request->gender == null) {
            $customer->gender = null;
        } elseif ($request->gender == 'er') {
            $customer->gender = 0;
        } elseif ($request->gender == 'em') {
            $customer->gender = 1;
        }
        $customer->save();

        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $customer->avatar = $image_name;
            $customer->save();
        }
        $status = 200;

        $customer_arr = $customer->attributesToArray();
        $customers_response = $this->getCustomerFormat($customer_arr, $customer);
        $result['data'] = $customers_response;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function show(Request $request, $id)
    {
        $this->authorize('view', Customer::class);

        $data = [];
        $provinces = [];
        $soum_districts = [];
        $customer_data = [];
        $customer = null;

        $provinces = Province::getProvinces();
        $soum_districts = SoumDistrict::getDistricts();

        if ($id !== 'null')
            $customer = Customer::find($id);

        if ($customer) {
            $customer_arr = $customer->attributesToArray();
            $customer_data = $this->getCustomerFormat($customer_arr, $customer);
        }
        $data['provinces'] = $provinces;
        $data['soumDistricts'] = $soum_districts;
        $data['customer'] = $customer_data;
        if ($data['customer']) {
            if ($data['customer']['gender'] == 0) {
                $data['customer']['gender'] = 'er';
            } else if ($data['customer']['gender'] == 1) {
                $data['customer']['gender'] = 'em';
            }
        }
        $status = 200;

        $result['data'] = $data;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Customer::class);

        $customer = [];
        $customer = Customer::find($id);
        $customer->firstname = $request->firstname;
        $customer->lastname = $request->lastname;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->phone2 = $request->phone2;
        $customer->registerno = $request->registerno;
        $customer->desc = $request->desc;
        $customer->card_number = $request->card_number;
        $customer->surgery_card_number = $request->surgery_card_number;
        if ($request->gender == null) {
            $customer->gender = null;
        } elseif ($request->gender == 'er') {
            $customer->gender = 0;
        } elseif ($request->gender == 'em') {
            $customer->gender = 1;
        }

        if ($request->province_id && $request->soum_district_id) {
            $address = $customer->address;
            if (!$address)
                $address = new Address();
            $address->province_id = $request->province_id;
            $address->soum_district_id = $request->soum_district_id;
            $address->street = $request->street;
            $address->street1 = $request->street1;
            $address->save();

            $customer->address_id = $address->id;
        }

        if ($request->file) {
            $image_name = $this->base64ToFile($request->file);
            $customer->avatar = $image_name;
        }

        $customer->save();
        $status = 200;

        $response['data'] = $customer;
        $response['payload'] = ['status' => $status];
        return response($response);
    }

    public function base64ToFile($encoded_file)
    {
        $image_64 = $encoded_file;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $image_name = Str::random(10) . '.' . $extension;
        Storage::disk('profile_images')->put($image_name, base64_decode($image));

        return $image_name;
    }

    public function destroy($id)
    {
        $this->authorize('delete', Customer::class);

        $customer = Customer::find($id);
        $customer->delete();

        return response(true);
    }

    public function getAppointmentHistory($id)
    {
        $this->authorize('update', Customer::class);
        $customer = [];

        $customer = Customer::find($id);

        $appointments = $customer->appointmentsWithTrashed;
        $appointment_data = [];
        $appointment_datas = [];
        foreach ($appointments as $appointment) {
            $appointment_data['id'] = $appointment->id;
            $appointment_data['event_date'] = $appointment->event_date;
            $appointment_data['status'] = $appointment->status;
            $appointment_data['cancellation_type'] = $appointment->cancellation_type;
            $appointment_data['desc'] = $appointment->desc;
            $appointment_data['diagnosis'] = $appointment->diagnosis;
            $appointment_data['conclusion'] = $appointment->conclusion;

            $events = $appointment->eventsWithTrashed;
            $event_datas = [];

            foreach ($events as $event) {
                $event_data['id'] = $event->id;
                $event_data['service_name'] = $event->service_id > 0 ? $event->service->name : '';
                $event_data['service_price'] = $event->price ? $event->price : '0';
                $event_data['user_name'] = $event->user?->firstname;
                $event_data['start_time'] = $event->start_time;
                $event_data['end_time'] = $event->end_time;
                $event_data['duration'] = $event->duration;
                $event_data['resource_name'] = $event->resource?->name;
                $event_data['treatment'] = $event->treatment;

                $event_datas[] = $event_data;
            }

            $appointment_data['events'] = $event_datas;
            $appointment_datas[] = $appointment_data;
        }
        $status = 200;

        $result['data'] = $appointment_datas;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function getPaymentHistory($id)
    {
        $this->authorize('update', Customer::class);
        $customer = [];

        $customer = Customer::find($id);

        $invoices = Invoice::selectRaw('invoices.id, invoices.customer_id, invoices.appointment_id, invoices.payment, 
                invoices.paid, appointments.event_date, invoices.state, invoices.payable, customers.left_payment as stored_left_payment, 
                invoices.discount_amount')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->orderBy('invoices.id', 'desc')
            ->where('invoices.customer_id', '=', $customer->id)
            ->get();

        $status = 200;

        $result['data'] = $invoices;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function getMembership($id)
    {
        $this->authorize('update', Customer::class);
        $member_ids = [];
        $membership = '';

        $customer = Customer::find($id);
        $membership_code = $customer->membership_code;

        if ($membership_code) {
            $membership = Membership::selectRaw('memberships.code, membership_types.title, membership_types.percent, membership_types.limit_price')
                ->leftJoin('membership_types', 'membership_types.id', 'memberships.membership_type_id')
                ->where('code', $membership_code)
                ->first();
            $member_ids = Customer::where('membership_code', $membership_code)->get()->pluck('id');
        }
        $status = 200;

        $result['data'] = ['membership_data' => $membership, 'members' => $member_ids];
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function removeMember($id)
    {
        $this->authorize('update', Customer::class);
        $customer = [];

        $customer = Customer::find($id);
        $customer->membership_code = '';
        $customer->save();

        $status = 200;

        $result['data'] = $customer;
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function addMember(Request $request)
    {
        $this->authorize('update', Customer::class);
        $customer = [];

        $customer_ids = $request->selected_customers;
        foreach ($customer_ids as $customer_id) {
            $customer = Customer::find($customer_id);
            $customer->membership_code = $request->code;
            $customer->save();
        }

        $status = 200;

        $result['data'] = $customer;
        $result['payload'] = ['status' => $status];
        return response($result);
    }
    public function addImage(Request $request)
    {
        $this->authorize('update', Customer::class);
        $image = new Image;
        $image->customer_id = $request->id;
        $image->caption = $request->caption;
        $image_name = $this->base64ToFileCustomerImage($request->file);
        $image->image_name = $image_name;
        $image->save();
        $status = 200;
        $result['data'] = [];
        $result['payload'] = ['status' => $status];
        return response($result);
    }

    public function base64ToFileCustomerImage($encoded_file)
    {
        $image_64 = $encoded_file;
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $image_name = Str::random(10) . '.' . $extension;
        $path = storage_path('app/public/'.$image_name);
        $pathTo = storage_path('app/public/thumb-'.$image_name);
        Storage::disk('public')->put($image_name, base64_decode($image));
        $targetWidth = 150;
        $targetHeight = 150;
        createThumbnail($path,$pathTo,$targetWidth,$targetHeight);
        return $image_name;
    }

    public function getImages($id)
    {
        $this->authorize('update', Customer::class);
        $images = Image::where('customer_id', $id)->get();
        $data = [];
        foreach ($images as $key => $image) {
            $pack = [
                'image_name' => $image->image_name,
                'caption' => $image->caption,
                'id' => $image->id
            ];
            $data[] = $pack;
        }
        $status = 200;

        $result['data'] = $data;
        $result['payload'] = ['status' => $status];
        return response($result);
    }
    public function deleteImage($id)
    {
        $image = Image::find($id);
        
        if (Storage::disk('public')->exists($image->image_name)) {
            Storage::disk('public')->delete($image->image_name);
        }
        if (Storage::disk('public')->exists('thumb-'.$image->image_name)) {
            Storage::disk('public')->delete('thumb-'.$image->image_name);
        }
        $image->delete();
        $status = 200;
        $result['data'] = [];
        $result['payload'] = ['status' => $status];
        return response($result);
    }


}