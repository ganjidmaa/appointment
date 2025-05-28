<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ApiController extends Controller
{
    public function postFromSite(Request $request){

        $to_email = ['info@ubisol.mn'];

        Mail::send('emails.crm', [ 'companyName' => $request->companyName, 'name'=>$request->name,'mobile'=>$request->mobile,'batch'=>$request->batch,'demo'=>$request->demo], function ($m) use ($to_email){
            $m->from(env('MAIL_FROM_ADDRESS'), 'Холбоос');
            $m->to($to_email)->subject('Холбоос шинэ сэжим.');
          
        });

    }
}
