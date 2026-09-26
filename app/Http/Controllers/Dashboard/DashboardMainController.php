<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\User;
use App\Services\ServerPaymentStatusService;
use App\Services\ServerStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardMainController extends Controller
{
    /**
     * Display the dashboard index.
     */
    public function index(ServerPaymentStatusService $serverPaymentStatusService, ServerStatsService $serverStatsService): View
    {
        /** @var User $user */
        $user = Auth::user();

        $canViewSearches = $user->hasAnyRole(['admin', 'super_admin']);

        $databaseEntities = $this->getDashboardDatabaseEntities($user, $canViewSearches);

        $serverPaymentStatus = $serverPaymentStatusService->canView($user)
            ? $serverPaymentStatusService->status()
            : null;

        $serverStats = $serverStatsService->canView($user)
            ? $serverStatsService->stats()
            : null;

        return view('backend.dashboard.index', [
            'user' => $user,
            'databaseEntities' => $databaseEntities,
            'canViewSearches' => $canViewSearches,
            'serverPaymentStatus' => $serverPaymentStatus,
            'serverStats' => $serverStats,
        ]);
    }

    /**
     * Database entities shown on the dashboard, filtered to the ones the given
     * user may access. Mirrors the access rule in
     * DatabaseDirectoryController::canUserAccessModule() so the Main panel and
     * the public database directory agree on visibility.
     *
     * @return Collection<int, DatabaseEntity>
     */
    private function getDashboardDatabaseEntities(User $user, bool $withSearchCounts): Collection
    {
        $query = DatabaseEntity::query()
            ->where('show_in_dashboard', true)
            ->where('dashboard_route_name', 'not like', '%https%');

        if ($withSearchCounts) {
            // GROUP BY the primary key is enough in PostgreSQL: every other
            // column of database_entities is functionally dependent on it.
            $query
                ->leftJoin('query_logs', 'database_entities.code', '=', 'query_logs.database_key')
                ->select('database_entities.*')
                ->selectRaw('COUNT(query_logs.id) as query_log_count')
                ->groupBy('database_entities.id');
        }

        return $query
            ->orderBy('name')
            ->get()
            ->filter(fn (DatabaseEntity $entity): bool => $this->userCanAccessDatabaseEntity($user, $entity))
            ->values();
    }

    /**
     * Access rules:
     * 1. Public database entities are visible to everyone.
     * 2. Private entities are visible to admin/super_admin, and to users
     *    holding the role matching the entity's code.
     */
    private function userCanAccessDatabaseEntity(User $user, DatabaseEntity $entity): bool
    {
        if ($entity->is_public) {
            return true;
        }

        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole($entity->code);
    }
}
