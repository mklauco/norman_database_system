<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MainAPIController extends Controller
{
    //
    public function index()
    {

        $user = User::find(Auth::id());

        // dd($user->tokens);
        return view('dashboard.apiresources.index', [
            'user' => $user,
        ]);
    }

    public function store(Request $request){

        $user = User::find(Auth::id());
        $tokenName = 'token_'.lcfirst($user->first_name).ucfirst($user->last_name).str_pad($user->tokens()->count()+1, 3, '0', STR_PAD_LEFT);

        $token = $user->createToken($this->convertToAscii($tokenName));
        // dd($token->accessToken->id);
        // eloquent queiry to update the plaintexttoken to the database based on token id
        DB::table('personal_access_tokens')->where('id', $token->accessToken->id)->update([ 'plain_text_token' => $token->plainTextToken ]);
        
        // dd($token);
        // 3|SlFIgwo8XBEbIYf6Xu0B39Hj4arD7SmSZBss5j1d98bd76c9
        return redirect()->back();
    }

    public function destroy(Request $request){
        $request->validate([
            'token_id' => 'required',
        ]);
        $request->user()->tokens()->where('id', $request->token_id)->delete();
        return redirect()->route('apiresources.index');
    }
}
