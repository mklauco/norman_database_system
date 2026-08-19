<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Seed row limit (per source file)
    |--------------------------------------------------------------------------
    |
    | Opt-in cap on the number of empodat_suspect_main rows written PER SOURCE
    | FILE by the EmpodatSuspect seeders. Re-importing all 15 EMPODAT Suspect
    | source spreadsheets takes hours; setting this lets you smoke-test the
    | whole pipeline against a small, bounded sample instead.
    |
    | Set EMPODAT_SUSPECT_SEED_ROW_LIMIT=10000 in .env to enable it. Leave it
    | unset (or remove the line) for a real, full-production-grade import —
    | null means no cap, and that is the required default. A real full reload
    | is also run from a developer's local machine, so this must never switch
    | itself on by default: it is opt-in only.
    |
    */

    'seed_row_limit' => env('EMPODAT_SUSPECT_SEED_ROW_LIMIT'),

];
