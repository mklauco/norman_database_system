<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Since-id override
    |--------------------------------------------------------------------------
    |
    | If set, the EmpodatLegacy seeder uses this as the cutoff instead of
    | auto-detecting from max(empodat_main.id). Useful for backfills, tests,
    | or re-importing a specific id range. Leave null in normal operation.
    |
    */

    'since_id' => env('LEGACY_MIGRATION_SINCE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Backfill (Database\Seeders\EmpodatLegacy\Backfill\EmpodatLegacyBackfillSeeder)
    |--------------------------------------------------------------------------
    |
    | Copies EMPODAT rows that legacy has and NDS lacks (insert-only). Dry run
    | is the default; writing needs mode=execute AND execute=true AND
    | expect_pg_database equal to the live current_database(). Process
    | environment variables win over these values (see BackfillOptions), which
    | matters because production runs with a cached config.
    |
    | column_aliases: "pg_table.pg_column=legacy_column;..." (empty right side
    | fills NULL). Defaults: empodat_matrix_water_surface.ocean_sea_region_name
    | and empodat_matrix_biota.packing_material have no legacy column and are NULL.
    |
    */

    'backfill' => [
        'mode' => env('LEGACY_BACKFILL_MODE', 'dry-run'),
        'execute' => env('LEGACY_BACKFILL_EXECUTE', false),
        'expect_pg_database' => env('LEGACY_BACKFILL_EXPECT_PG_DATABASE'),
        'expect_pg_host' => env('LEGACY_BACKFILL_EXPECT_PG_HOST'),
        'bucket_size' => env('LEGACY_BACKFILL_BUCKET_SIZE', 1_000_000),
        'chunk_rows' => env('LEGACY_BACKFILL_CHUNK_ROWS', 5000),
        'scan_window' => env('LEGACY_BACKFILL_SCAN_WINDOW', 250_000),
        'legacy_pause_ms' => env('LEGACY_BACKFILL_LEGACY_PAUSE_MS', 0),
        'dry_run_read_only_ok' => env('LEGACY_BACKFILL_DRY_RUN_READ_ONLY_OK', false),
        'close_stale_run_id' => env('LEGACY_BACKFILL_CLOSE_STALE_RUN_ID'),
        'report_directory' => env('LEGACY_BACKFILL_REPORT_DIRECTORY', storage_path('logs')),
        'memory_limit' => env('LEGACY_BACKFILL_MEMORY_LIMIT', '4G'),
        'column_aliases' => env('LEGACY_BACKFILL_COLUMN_ALIASES', ''),
        'only_tables' => env('LEGACY_BACKFILL_ONLY_TABLES', ''),

        /*
         * id window (inclusive) that EVERY mode is restricted to, including
         * execute: use it to stage the real run (small window first, verify,
         * then widen). Empty = whole id space.
         */
        'id_from' => env('LEGACY_BACKFILL_ID_FROM'),
        'id_to' => env('LEGACY_BACKFILL_ID_TO'),

        /*
         * Dry run only: missing ids per table that are read and run through the
         * exact execute pipeline (without writing). 0 = no limit (full dry run,
         * reads as much legacy data as the real run). sample_rows = example
         * rows per table printed in the report.
         */
        'dry_run_max_rows' => env('LEGACY_BACKFILL_DRY_RUN_MAX_ROWS', 50_000),
        'sample_rows' => env('LEGACY_BACKFILL_SAMPLE_ROWS', 5),
    ],

];
