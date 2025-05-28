<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', BankAccount::class);

        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'name like "%'.$search.'%" or account_number like "%'.$search.'%"';
        }
        
        $bank_accounts = BankAccount::whereRaw($where)->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $bank_accounts,
            'status' => $status
        ];

        $responce['data'] = $bank_accounts->items();
        $responce['payload'] = $payload;

        return response($responce);
    }

    public function store(Request $request)
    {
        $this->authorize('create', BankAccount::class);

        $bank_account = new BankAccount();
        $bank_account->name = $request->name;
        $bank_account->account_number = $request->account_number;
        $bank_account->save();

        $status = 200;

        $responce['data'] = $bank_account;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function show($id)
    {
        $this->authorize('update', BankAccount::class);

        $bank_account = BankAccount::find($id);
       
        $status = 200;

        $responce['data'] = $bank_account;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', BankAccount::class);

        $bank_account = BankAccount::find($id);
        $bank_account->name = $request->name;
        $bank_account->account_number = $request->account_number;
        $bank_account->save();

        $status = 200;

        $responce['data'] = $bank_account;
        $responce['payload'] = ['status' => $status];

        return response($responce);
    }

    public function destroy($id)
    {
        $this->authorize('delete', BankAccount::class);

        $bank_account = BankAccount::find($id);
        $bank_account->delete();

        return response(true);
    }
}
