<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Database\Seeders\EmpodatSuspect\Traits\DeletesSuspectFileData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * CONNECT 2 SEDIMENTS re-import pipeline (file_id=10003), end to end.
 *
 * Phases:
 *   0. Scoped teardown  — delete ONLY file_id=10003: substances, metadata,
 *                         main (by its id range), then mapping rows. Order is
 *                         foreign-key dictated; see
 *                         {@see DeletesSuspectFileData} for the guards that
 *                         refuse the delete if the file's rows fall outside
 *                         that range or another file shares its mapping rows.
 *   1. File             — repoint files.original_name / file_path at the v2
 *                         spreadsheet. Two columns, one row; nothing else on
 *                         the `files` row is touched.
 *   2. Mapping          — one row per station column, station_id NULL.
 *   3. Mapping fill     — resolve station_id by equality on
 *                         empodat_stations.short_sample_code; abort unless all
 *                         14 resolve to exactly one station each.
 *   4. Main + metadata  — stream the spreadsheet back into the SAME id block
 *                         (4978377..6414446), in one transaction.
 *
 * NOT done here, deliberately: `empodat_suspect_data_source` is untouched (its
 * row keys to `files`, which this pipeline never re-creates); `files.rescan`
 * (main_id_from / main_id_to / number_of_records) is a manual step in the web
 * UI after the import is verified; no materialized view or statistic is
 * refreshed.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsReimportSeeder
 */
class EmpodatSuspectConnect2SedimentsReimportSeeder extends Seeder
{
    use DeletesSuspectFileData;
    use WithoutModelEvents;

    private const int FILE_ID = 10003;

    private const int ID_FROM = 4978377;

    private const int ID_TO = 6414446;

    /**
     * `--force` (the option `db:seed` already carries) stands in for the typed
     * confirmation, so this pipeline can be driven from a script or a
     * non-interactive shell. Without it, an unattended run simply declines.
     */
    private function confirmed(): bool
    {
        if ($this->command->option('force')) {
            $this->command->warn(' --force given: proceeding without a prompt.');

            return true;
        }

        return $this->command->confirm('Proceed?', false);
    }

    public function run(): void
    {
        $this->command->warn('=== CONNECT 2 SEDIMENTS re-import (file_id='.self::FILE_ID.') ===');
        $this->command->warn(' This DELETES and regenerates file_id='.self::FILE_ID.' only.');
        $this->command->warn(' Id range re-used: '.number_format(self::ID_FROM).'..'.number_format(self::ID_TO));

        if (! $this->confirmed()) {
            $this->command->info('Aborted. No changes made.');

            return;
        }

        $this->command->info('Phase 0 — scoped teardown');
        $this->deleteSuspectFileData(self::FILE_ID, self::ID_FROM, self::ID_TO);

        $this->command->info('Phase 1-4 — re-import');
        $this->call([
            EmpodatSuspectConnect2SedimentsFileSeeder::class,
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingSeeder::class,
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder::class,
        ]);

        // The ONLY caller that re-uses the existing id block. A full reload
        // (EmpodatSuspectResetAndReseedSeeder) calls the Main seeder without
        // this flag, so it draws from the sequence like every other source.
        $this->callWith(EmpodatSuspectConnect2SedimentsMainSeeder::class, ['useFixedIdRange' => true]);

        $this->command->info('=== CONNECT 2 SEDIMENTS re-import complete ===');
        $this->command->info('NEXT (manual): rescan file '.self::FILE_ID.' in the web UI, then refresh-filters -> '
            .'refresh-matrix-metadata -> refresh-prioritisation --file='.self::FILE_ID.' -> generate-statistics.');
    }
}
