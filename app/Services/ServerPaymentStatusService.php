<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Backend\ServerPayment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Status of the current paid server period, shared by the Main panel and System Settings.
 */
class ServerPaymentStatusService
{
    /**
     * @var array<int, string>
     */
    public const VIEWER_ROLES = ['super_admin', 'server_payment_admin', 'server_payment_viewer'];

    public const RENEWAL_WARNING_DAYS = 14;

    public function canView(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWER_ROLES);
    }

    /**
     * @return array{payment: ServerPayment|null, days_remaining: int|null, progress_percentage: float, renewal_due: bool}
     */
    public function status(): array
    {
        $payment = ServerPayment::query()
            ->where('status', 'paid')
            ->orderBy('period_end_date', 'desc')
            ->first();

        if ($payment === null) {
            return [
                'payment' => null,
                'days_remaining' => null,
                'progress_percentage' => 0.0,
                'renewal_due' => false,
            ];
        }

        $endDate = Carbon::parse($payment->period_end_date);

        if ($endDate->isFuture()) {
            $daysRemaining = (int) Carbon::today()->diffInDays($endDate, false);
            $totalDays = Carbon::parse($payment->period_start_date)->diffInDays($endDate);
            $progressPercentage = $totalDays > 0 ? (($totalDays - $daysRemaining) / $totalDays) * 100 : 0.0;
        } else {
            $daysRemaining = 0;
            $progressPercentage = 100.0;
        }

        return [
            'payment' => $payment,
            'days_remaining' => $daysRemaining,
            'progress_percentage' => (float) $progressPercentage,
            'renewal_due' => $daysRemaining <= self::RENEWAL_WARNING_DAYS,
        ];
    }
}
