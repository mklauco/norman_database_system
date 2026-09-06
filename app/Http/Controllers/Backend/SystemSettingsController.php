<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\ServerPayment;
use App\Models\User;
use App\Services\ServerStatsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SystemSettingsController extends Controller
{
    /**
     * Display the System Settings index page.
     * Only accessible to super_admin and admin roles.
     *
     * @return \Illuminate\View\View
     */
    public function index(ServerStatsService $serverStatsService)
    {
        $user = Auth::user();

        // Get statistics for system information cards
        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'total_api_tokens' => \Laravel\Sanctum\PersonalAccessToken::count(),
        ];

        // Get server payment information if user has access
        $serverPayment = null;
        $daysRemaining = null;
        $progressPercentage = 0;

        if ($user->hasAnyRole(['super_admin', 'server_payment_admin', 'server_payment_viewer'])) {
            $serverPayment = ServerPayment::where('status', 'paid')
                ->orderBy('period_end_date', 'desc')
                ->first();

            if ($serverPayment) {
                $today = Carbon::today();
                $endDate = Carbon::parse($serverPayment->period_end_date);

                if ($endDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($endDate, false);
                    $totalDays = Carbon::parse($serverPayment->period_start_date)->diffInDays($endDate);
                    $daysPassed = $totalDays - $daysRemaining;
                    $progressPercentage = $totalDays > 0 ? ($daysPassed / $totalDays) * 100 : 0;
                } else {
                    $daysRemaining = 0;
                    $progressPercentage = 100;
                }
            }
        }

        // Server disk usage and uptime, shown to super admins only
        $serverStats = $user->hasRole('super_admin')
            ? $serverStatsService->stats()
            : ['disk' => null, 'uptime' => null];

        return view('backend.system-settings.index', [
            'user' => $user,
            'statistics' => $statistics,
            'serverPayment' => $serverPayment,
            'daysRemaining' => $daysRemaining,
            'progressPercentage' => $progressPercentage,
            'serverStats' => $serverStats,
        ]);
    }
}
