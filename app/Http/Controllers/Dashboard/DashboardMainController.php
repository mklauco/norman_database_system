<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardMainController extends Controller
{
    //

    public function index()
    {
        $user = User::find(Auth::id());
        return view('dashboard.index', [
            'user' => $user,
        ]);
    }
}
