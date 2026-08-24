<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | EMPODAT Suspect materialized-view refresh commands — allowlist
    |--------------------------------------------------------------------------
    |
    | This array is the ONLY place that maps a command key to a real artisan
    | command. The Livewire command center and RunEmpodatSuspectCommandJob
    | both resolve the client-supplied key against this list — a command
    | string is never built from user input, and the client never sends
    | anything but this opaque key plus a small set of typed arguments that
    | are re-validated against the option definitions below before the
    | command actually runs.
    |
    | `--create` and `--force` are intentionally never exposed here: `--create`
    | drops and rebuilds a materialized view from scratch, `--force` blocks
    | all reads while it runs. Both are destructive/blocking and have no
    | business being reachable from a web button.
    |
    | Ordering matters for correctness, not just preference:
    | `refresh_filters` is the only command that rebuilds the
    | `empodat_suspect_stations_helper` table, which the
    | `empodat_suspect_station_filters` materialized view and all nine
    | `empodat_suspect_matrix_*` views (and the prioritisation view) read
    | from. Running any of the others first silently produces stale views —
    | there is no error, just wrong data. `refresh_all_order` below fixes the
    | sequence the "Refresh all" composite action must use.
    |
    */

    'commands' => [

        'refresh_filters' => [
            'signature' => 'empodat-suspect:refresh-filters',
            'label' => 'Refresh Station Filters',
            'description' => 'Rebuilds the empodat_suspect_stations_helper table and the '
                .'empodat_suspect_station_filters materialized view. Must run first: it is the '
                .'only command that rebuilds the helper table everything else reads from.',
            'estimated_duration' => '1-5 minutes',
            'destructive' => false,
            'options' => [
                'stats' => [
                    'type' => 'flag',
                    // Always shown in the captured output; not a user-controlled input.
                    'always' => true,
                ],
            ],
        ],

        'refresh_matrix_metadata' => [
            'signature' => 'empodat-suspect:refresh-matrix-metadata',
            'label' => 'Refresh Matrix Metadata',
            'description' => 'Rebuilds the nine empodat_suspect_matrix_* materialized views used '
                .'for CSV exports. Requires refresh-filters to have run first.',
            'estimated_duration' => '5-30 minutes',
            'destructive' => false,
            'options' => [
                'stats' => [
                    'type' => 'flag',
                    'always' => true,
                ],
                'only' => [
                    'type' => 'enum',
                    'nullable' => true,
                    'allowed' => [
                        'biota',
                        'sediments',
                        'water_surface',
                        'water_ground',
                        'water_waste',
                        'suspended_matter',
                        'soil',
                        'air',
                        'sewage_sludge',
                    ],
                ],
            ],
        ],

        'refresh_prioritisation' => [
            'signature' => 'empodat-suspect:refresh-prioritisation',
            'label' => 'Refresh Prioritisation',
            'description' => 'Rebuilds the empodat_suspect_prioritisation materialized view. '
                .'Requires refresh-filters to have run first.',
            'estimated_duration' => '10-60 minutes',
            'destructive' => false,
            'options' => [
                'stats' => [
                    'type' => 'flag',
                    'always' => true,
                ],
                'file' => [
                    'type' => 'file_id',
                    'nullable' => true,
                    // Resolved at validation time via database_entities.code, not a hardcoded
                    // id: the numeric id is environment-specific (dev happens to be 18).
                    'database_entity_code' => 'empodat_suspect',
                ],
            ],
        ],

        'generate_statistics' => [
            'signature' => 'empodat-suspect:generate-statistics',
            'label' => 'Generate Statistics',
            'description' => 'Recomputes the EMPODAT Suspect dashboard statistics cards.',
            'estimated_duration' => '1-5 minutes',
            'destructive' => false,
            'options' => [
                'sync' => [
                    'type' => 'flag',
                    // Forced on: without it the command just dispatches its own queued job
                    // and returns immediately, which would make our run history record a
                    // near-instant "success" for work that hasn't actually happened yet.
                    // We are already inside a queued job, so running inline here is correct.
                    'always' => true,
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | "Refresh all" composite order
    |--------------------------------------------------------------------------
    |
    | The exact, non-negotiable sequence for the composite action.
    |
    */
    'refresh_all_order' => [
        'refresh_filters',
        'refresh_matrix_metadata',
        'refresh_prioritisation',
        'generate_statistics',
    ],

];
