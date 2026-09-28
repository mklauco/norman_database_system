<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\UserLoginRetention;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UserLoginRetentionController extends Controller
{
    public function filter(Request $request)
    {
        return view('backend.user-login-retention.filter');
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

        // Filter by date range - set defaults if not provided.
        // The dates are Bratislava calendar days, converted to the stored (app) timezone.
        $timezone = UserLoginRetention::DISPLAY_TIMEZONE;
        $dateFrom = $request->filled('date_from') ? $request->date_from : now($timezone)->subMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now($timezone)->format('Y-m-d');

        $query->where('login_datetime', '>=', Carbon::parse($dateFrom, $timezone)->startOfDay()->setTimezone(config('app.timezone')));
        $query->where('login_datetime', '<=', Carbon::parse($dateTo, $timezone)->endOfDay()->setTimezone(config('app.timezone')));

        // Order by login datetime (newest first)
        $query->orderBy('login_datetime', 'desc');

        // Log the query for debugging
        Log::info('UserLoginRetention search query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $request->all(),
        ]);

        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100], true) ? $request->integer('per_page') : 25;

        $results = $query->paginate($perPage);

        return view('backend.user-login-retention.search', compact('results', 'perPage'));
    }
}
