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

        // dd($user->tokens);
        return view('backend.dashboard.apiresources.index', [
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {

        $user = User::find(Auth::id());
        $tokenName = 'token_'.lcfirst($user->first_name).ucfirst($user->last_name).str_pad($user->tokens()->count() + 1, 3, '0', STR_PAD_LEFT);

        $token = $user->createToken($this->convertToAscii($tokenName));

        // Only the hash is stored; the plain token is shown once and cannot be recovered later.
        return redirect()->back()->with('new_api_token', $token->plainTextToken);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token_id' => 'required',
        ]);
        $request->user()->tokens()->where('id', $request->token_id)->delete();

        return redirect()->route('apiresources.index');
    }
}
