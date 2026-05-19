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

];
