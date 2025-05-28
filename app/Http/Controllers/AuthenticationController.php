<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Settings;
use App\Mail\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class AuthenticationController extends Controller
{
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $overdue = $this->validateSystemOverdue('login');

        $user = User::where('email', $request->email)->first();
     
        if (!$user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Нэвтрэх эсвэл нууц үг буруу байна.'],
            ]);
        }
        
        $token = $user->createToken($request->password);
        $result['api_token'] = $token->plainTextToken;

        return response($result);
    }

    public function forgotPassword(Request $request) {
        $user = User::where('email', $request->email)->first();
        $is_success = false;

        if($user) {
            $password = $this->getPrefix().''.mt_rand(100000,999999);
            Mail::to($user->email)->send(new PasswordReset($password));
            $is_success = true;
        }
        
        if($is_success) {
            $user->password = Hash::make($password);
            $user->save();
        }

        $result['result'] = $is_success;
        return response($result);
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

    public function validateSystemOverdue($method_type='api') {
        $settings = Settings::find(1);
        $today = date('Y-m-d');
        $overdue = false;

        if($today > $settings->limit_date_usage) {
            $overdue = true;
        }

        if($overdue) {
            throw ValidationException::withMessages([
                'limit_date' => ['Үйлчилгээний хугацаа хэтэрсэн байна. 7500-4000, 86086036 дугаарт холбогдож хугацаагаа сунгуулна уу.'],
            ]);
        } 

        return response(['overdue' => $overdue]);  
    }

}
