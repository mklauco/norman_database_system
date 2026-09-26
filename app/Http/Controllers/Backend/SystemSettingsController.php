<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ServerPaymentStatusService;
use App\Services\ServerStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SystemSettingsController extends Controller
{
    /**
     * Display the System Settings index page.
     *
     * Reachable by super_admin, admin and user_manager; role-sensitive data
     * (server payments, disk/uptime) is only computed for the roles that can
     * see it.
     */
    public function index(ServerStatsService $serverStatsService, ServerPaymentStatusService $serverPaymentStatusService): View
    {
        $user = Auth::user();

        // Get statistics for system information cards
        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'total_api_tokens' => \Laravel\Sanctum\PersonalAccessToken::count(),
        ];

        $canViewServerPayments = $serverPaymentStatusService->canView($user);
        $paymentStatus = $canViewServerPayments ? $serverPaymentStatusService->status() : null;

        $canViewServerStats = $serverStatsService->canView($user);
        $serverStats = $canViewServerStats
            ? $serverStatsService->stats()
            : ['disk' => null, 'uptime' => null];

        return view('backend.system-settings.index', [
            'user' => $user,
            'statistics' => $statistics,
            'canViewServerPayments' => $canViewServerPayments,
            'paymentStatus' => $paymentStatus,
            'canViewServerStats' => $canViewServerStats,
            'serverStats' => $serverStats,
        ]);
    }

    /**
     * Display the Maintenance page: the database recount/rebuild operations
     * that used to live as buttons on the Main panel. Super admin only.
     */
    public function maintenance(): View
    {
        return view('backend.system-settings.maintenance', [
            'operationGroups' => $this->operationGroups(),
        ]);
    }

    /**
     * Database maintenance operations, grouped by the database they affect.
     *
     * Each operation keeps its original route name and HTTP method. This is
     * static route metadata for the Maintenance page, not business logic, so
     * it lives here rather than in a service class.
     *
     * @return list<array{database: string, operations: list<array{label: string, description: string, touches: string, route: string, method: string, warning: string|null}>}>
     */
    private function operationGroups(): array
    {
        return [
            [
                'database' => 'Empodat',
                'operations' => [
                    [
                        'label' => 'Rebuild country filter',
                        'description' => 'Rebuild the list of countries used to filter EMPODAT records.',
                        'touches' => 'empodat_search_countries',
                        'route' => 'cod.unique.search.countries',
                        'method' => 'POST',
                        'warning' => 'Scans the entire EMPODAT dataset (100+ million records) — can take several minutes.',
                    ],
                    [
                        'label' => 'Rebuild ecosystem filter',
                        'description' => 'Rebuild the list of ecosystems (matrices) used to filter EMPODAT records.',
                        'touches' => 'empodat_search_matrices',
                        'route' => 'cod.unique.search.matrices',
                        'method' => 'POST',
                        'warning' => 'Scans the entire EMPODAT dataset (100+ million records) — can take several minutes.',
                    ],
                    [
                        'label' => 'Recount EMPODAT and SUSDAT',
                        'description' => 'Recount the EMPODAT and SUSDAT records shown on the Main panel.',
                        'touches' => 'database entity counts for Empodat and SusDat',
                        'route' => 'update.dbentities.counts',
                        'method' => 'POST',
                        'warning' => 'Scans the entire EMPODAT dataset (100+ million records) — can take several minutes.',
                    ],
                ],
            ],
            [
                'database' => 'Ecotox',
                'operations' => [
                    [
                        'label' => 'Recount Ecotox',
                        'description' => 'Recount the records shown for the Ecotox module.',
                        'touches' => 'database entity count for Ecotox',
                        'route' => 'ecotox.ecotox.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                    [
                        'label' => 'Recount Lowest PNEC',
                        'description' => 'Recount the records shown for the Lowest PNEC list.',
                        'touches' => 'database entity count for Lowest PNEC',
                        'route' => 'ecotox.lowestpnec.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                    [
                        'label' => 'Recount entire Ecotox database',
                        'description' => 'Recount Ecotox, Lowest PNEC and their combined total together.',
                        'touches' => 'database entity counts for Ecotox, Lowest PNEC and the combined total',
                        'route' => 'ecotox.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                    [
                        'label' => 'Sync Ecotox substances',
                        'description' => 'Add any newly seen Ecotox substances to the distinct-substances list.',
                        'touches' => 'the distinct Ecotox substances list',
                        'route' => 'ecotox.unique.search.substances',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                    [
                        'label' => 'Sync PNEC3 substances',
                        'description' => 'Add any newly seen substances to the PNEC3 distinct-substances list.',
                        'touches' => 'the distinct PNEC3 substances list',
                        'route' => 'ecotox.unique.search.substances.pnec3',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'SLE',
                'operations' => [
                    [
                        'label' => 'Recount SLE',
                        'description' => 'Recount the records shown for the Suspect List Exchange (SLE) module.',
                        'touches' => 'database entity count for SLE',
                        'route' => 'slehome.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'ARBG',
                'operations' => [
                    [
                        'label' => 'Recount ARBG',
                        'description' => 'Recount the bacteria and gene records shown for the ARBG module.',
                        'touches' => 'database entity count for ARBG',
                        'route' => 'arbg.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Indoor',
                'operations' => [
                    [
                        'label' => 'Recount Indoor',
                        'description' => 'Recount the records shown for the Indoor module.',
                        'touches' => 'database entity count for Indoor',
                        'route' => 'indoor.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Passive',
                'operations' => [
                    [
                        'label' => 'Recount Passive',
                        'description' => 'Recount the records shown for the Passive Sampling module.',
                        'touches' => 'database entity count for Passive',
                        'route' => 'passive.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Prioritisation',
                'operations' => [
                    [
                        'label' => 'Recount Prioritisation',
                        'description' => 'Recount the monitoring and modelling records shown for the Prioritisation module.',
                        'touches' => 'database entity count for Prioritisation',
                        'route' => 'prioritisation.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Bioassay',
                'operations' => [
                    [
                        'label' => 'Recount Bioassay',
                        'description' => 'Recount the records shown for the Bioassay module.',
                        'touches' => 'database entity count for Bioassay',
                        'route' => 'bioassay.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Literature',
                'operations' => [
                    [
                        'label' => 'Recount Literature',
                        'description' => 'Recount the records shown for the Literature module.',
                        'touches' => 'database entity count for Literature',
                        'route' => 'literature.countAll',
                        'method' => 'GET',
                        'warning' => null,
                    ],
                ],
            ],
            [
                'database' => 'Empodat Suspect',
                'operations' => [
                    [
                        'label' => 'Recount Empodat Suspect',
                        'description' => 'Recount the records shown for the Empodat Suspect module.',
                        'touches' => 'database entity count for Empodat Suspect',
                        'route' => 'empodat_suspect.countAll',
                        'method' => 'GET',
                        'warning' => 'Reads a large table (tens of millions of records) — can take a while.',
                    ],
                ],
            ],
            [
                'database' => 'Factsheets',
                'operations' => [
                    [
                        'label' => 'Populate factsheet statistics',
                        'description' => "Create placeholder statistics rows for any EMPODAT substance that doesn't have one yet.",
                        'touches' => 'factsheet statistics records',
                        'route' => 'factsheets.statistics.populate-all',
                        'method' => 'POST',
                        'warning' => 'Reads the full EMPODAT dataset to find substances — can take a while.',
                    ],
                ],
            ],
            [
                'database' => 'All databases',
                'operations' => [
                    [
                        'label' => 'Refresh last-updated dates',
                        'description' => 'Refresh the "last updated" date shown for every database, based on its most recently uploaded file.',
                        'touches' => 'the last-updated date for every database',
                        'route' => 'update.dbentities.lastupdate',
                        'method' => 'POST',
                        'warning' => null,
                    ],
                ],
            ],
        ];
    }
}
