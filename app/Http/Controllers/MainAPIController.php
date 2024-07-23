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
        return view('apiresources.index', [
            'user' => $user,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'token_name' => 'required',
        ]);
        $token = $request->user()->createToken($request->token_name);
        // dd($token->accessToken->id);
        // eloquent queiry to update the plaintexttoken to the database based on token id
        DB::table('personal_access_tokens')->where('id', $token->accessToken->id)->update([ 'plain_text_token' => $token->plainTextToken ]);
        
        // dd($token);
        // 3|SlFIgwo8XBEbIYf6Xu0B39Hj4arD7SmSZBss5j1d98bd76c9
        return redirect()->route('apiresources.index');
    }

    public function destroy(Request $request){
        $request->validate([
            'token_id' => 'required',
        ]);
        $request->user()->tokens()->where('id', $request->token_id)->delete();
        return redirect()->route('apiresources.index');
    }
}
