<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MainAPIController extends Controller
{
    //
    public function index()
    {

        $user = User::find(Auth::id());

        
        return view('apiresources.index', [
            'user' => $user,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'token_name' => 'required',
        ]);
        $token = $request->user()->createToken($request->token_name);
        return redirect()->route('apiresources.index');
    }
}
