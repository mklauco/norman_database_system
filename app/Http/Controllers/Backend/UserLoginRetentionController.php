<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\UserLoginRetention;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserLoginRetentionController extends Controller
{
    public function filter(Request $request)
    {
        $users = User::orderBy('last_name')->orderBy('first_name')->get();

        // Debug: Log the users data
        Log::info('UserLoginRetention filter users:', [
            'users_count' => $users->count(),
            'users_data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->last_name.', '.$user->first_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ];
            })->toArray(),
        ]);

        return view('backend.user-login-retention.filter', compact('users'));
    }

    public function search(Request $request)
    {
        // Debug: Log the incoming request data
        Log::info('UserLoginRetention search request:', [
            'all_params' => $request->all(),
            'user_id' => $request->user_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        $query = UserLoginRetention::with(['user']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range - set defaults if not provided
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

        $query->whereDate('login_datetime', '>=', $dateFrom);
        $query->whereDate('login_datetime', '<=', $dateTo);

        // Order by login datetime (newest first)
        $query->orderBy('login_datetime', 'desc');

        // Log the query for debugging
        Log::info('UserLoginRetention search query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $request->all(),
        ]);

        $results = $query->cursorPaginate(100);

        return view('backend.user-login-retention.search', compact('results'));
    }
}
