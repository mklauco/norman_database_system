<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Database\Seeders\EmpodatSuspectDataSourceSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Full reset + STRICTLY SEQUENTIAL re-seed of the EMPODAT Suspect module.
 *
 * WHY THIS EXISTS
 * ---------------
 * The 15 source files (file_id 10001–10015) were originally imported in a
 * chaotic order, and the final TerraChem batch (10012–10015) was imported in
 * PARALLEL. Because empodat_suspect_main.id is a single shared BIGSERIAL, the
 * parallel runs interleaved their auto-increment ids, so the per-file id ranges
 * (files.main_id_from / main_id_to, derived later by a rescan) overlap and are
 * out of order. This seeder wipes the Suspect data and re-imports every source
 * one-at-a-time in ascending file_id order, so each file ends up occupying one
 * clean, contiguous, non-overlapping block of ids.
 *
 * The ORDER in which the *MainSeeder classes run is the ONLY thing that assigns
 * the id ranges. Running them sequentially (never in parallel) is the fix.
 *
 * WHAT IT DOES
 * ------------
 *   1+2. TRUNCATEs the four per-source data tables and RESTARTs their identity
 *        sequences, so the first re-imported main row gets id = 1:
 *            - empodat_suspect_main       (partitioned: _numeric / _nonnumeric)
 *            - empodat_suspect_metadata   (partitioned: _numeric / _nonnumeric)
 *            - empodat_suspect_substances
 *            - empodat_suspect_data_source
 *   3.   Re-imports every source in ascending file_id order. Each source's full
 *        chain completes before the next source begins.
 *   4.   Re-populates empodat_suspect_data_source for every Suspect file.
 *
 * WHAT IT DOES *NOT* DO (run manually afterwards, per the agreed workflow)
 * -----------------------------------------------------------------------
 *   - It does NOT rescan files.main_id_from / main_id_to / number_of_records.
 *     Do that via the web UI "Rescan" after verifying the import.
 *   - It does NOT refresh any materialized view or statistics.
 *
 * SAFETY NOTES
 * ------------
 *   - SHARED / reference data is deliberately NOT touched: the `files` rows
 *     (FileSeeders use updateOrCreate), empodat_suspect_xlsx_stations_mapping,
 *     empodat_suspect_susdat_code_mappings and susdat_substances. The per-source
 *     XlsxStationsMapping / MappingFill seeders are idempotent (skip-if-exists /
 *     UPDATE), so re-running them against the kept mapping table is a safe no-op.
 *   - TRUNCATE uses no CASCADE: the only historical inbound FK (the old
 *     file_empodat_suspect_main pivot) was dropped when empodat_suspect_main was
 *     recreated as a partitioned table, so no live FK references these tables.
 *   - This is a long (multi-hour) operation. Each MainSeeder manages its own
 *     memory limits, transaction and foreign-key handling.
 *
 * RUN WITH:
 *   php artisan db:seed --class="Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectResetAndReseedSeeder"
 */
class EmpodatSuspectResetAndReseedSeeder extends Seeder
{
    /**
     * Per-source data tables wiped before re-import. Truncated in a single atomic
     * statement, so listing order is irrelevant.
     *
     * @var list<string>
     */
    private const TABLES_TO_TRUNCATE = [
        'empodat_suspect_main',
        'empodat_suspect_metadata',
        'empodat_suspect_substances',
        'empodat_suspect_data_source',
    ];

    public function run(): void
    {
        $this->command->warn('==================================================================');
        $this->command->warn(' EMPODAT SUSPECT — FULL RESET AND ORDERED RE-SEED');
        $this->command->warn(' This DELETES every row from:');
        foreach (self::TABLES_TO_TRUNCATE as $table) {
            $this->command->warn('   - '.$table);
        }
        $this->command->warn(' then re-imports all 15 sources sequentially (file_id 10001 → 10015).');
        $this->command->warn('==================================================================');

        if (! $this->command->confirm('This is destructive and runs for hours. Proceed?', false)) {
            $this->command->info('Aborted. No changes made.');

            return;
        }

        $this->truncateSuspectData();
        $this->reseedInOrder();

        $this->command->info('');
        $this->command->info('Re-seed complete. NEXT (manual) STEPS:');
        $this->command->info('  1. Rescan each file in the web UI to repopulate main_id_from / main_id_to / number_of_records.');
        $this->command->info('  2. Refresh: refresh-filters -> refresh-matrix-metadata -> refresh-prioritisation, then station_filters MV + generate-statistics.');
    }

    /**
     * Steps 1 + 2: wipe the four per-source data tables and restart their identity
     * sequences so the first re-imported main row gets id = 1.
     */
    private function truncateSuspectData(): void
    {
        $tables = implode(', ', self::TABLES_TO_TRUNCATE);

        $this->command->info("Truncating: {$tables} (RESTART IDENTITY)...");
        DB::statement("TRUNCATE TABLE {$tables} RESTART IDENTITY");
        $this->command->info('Truncate complete. empodat_suspect_main.id sequence restarted at 1.');
    }

    /**
     * Steps 3 + 4: import every source in ascending file_id order. Each source's
     * full chain finishes before the next begins, so the *MainSeeder runs — and
     * therefore the assigned id ranges — are strictly sequential and never overlap.
     */
    private function reseedInOrder(): void
    {
        // Create the 8 legacy `files` rows (10001–10008) up front (updateOrCreate).
        // The 7 newer `files` rows (10009–10015) are created by their own
        // per-source orchestrators below.
        $this->call(EmpodatSuspectFileSeeder::class);

        // ---- LEGACY SOURCES (10001–10008) -----------------------------------
        // No per-source orchestrator exists, so each chain is spelled out here:
        //   XlsxStationsMapping -> XlsxStationsMappingFill -> Main
        // (substances are now collected inside each *MainSeeder's single read pass)

        // File ID 10001 — CONNECT 1 SEDIMENT
        $this->call([
            EmpodatSuspectSedimentXlsxStationsMappingSeeder::class,
            EmpodatSuspectSedimentXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectSedimentMainSeeder::class,
        ]);

        // File ID 10002 — CONNECT 1 BIOTA
        $this->call([
            EmpodatSuspectBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectBiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectBiotaMainSeeder::class,
        ]);

        // File ID 10003 — CONNECT 2 SEDIMENTS
        $this->call([
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingSeeder::class,
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectConnect2SedimentsMainSeeder::class,
        ]);

        // File ID 10004 — CONNECT 2 BIOTA
        $this->call([
            EmpodatSuspectConnect2BiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectConnect2BiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectConnect2BiotaMainSeeder::class,
        ]);

        // File ID 10005 — HELCOM PreEMPT SEDIMENTS
        $this->call([
            EmpodatSuspectHelcomSedimentsXlsxStationsMappingSeeder::class,
            EmpodatSuspectHelcomSedimentsXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectHelcomSedimentsMainSeeder::class,
        ]);

        // File ID 10006 — HELCOM PreEMPT BIOTA
        $this->call([
            EmpodatSuspectHelcomBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectHelcomBiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectHelcomBiotaMainSeeder::class,
        ]);

        // File ID 10007 — LIFE APEX
        $this->call([
            EmpodatSuspectApexXlsxStationsMappingSeeder::class,
            EmpodatSuspectApexXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectApexMainSeeder::class,
        ]);

        // File ID 10008 — UBA-HELCOM
        $this->call([
            EmpodatSuspectUbaHelcomXlsxStationsMappingSeeder::class,
            EmpodatSuspectUbaHelcomXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectUbaHelcomMainSeeder::class,
        ]);

        // ---- NEWER SOURCES (10009–10015) ------------------------------------
        // Each orchestrator runs its own chain:
        //   File -> XlsxStationsMapping -> XlsxStationsMappingFill -> Main

        // File ID 10009 — BlackSea BIOTA
        $this->call(EmpodatSuspectBlackSeaBiotaSeeder::class);

        // File ID 10010 — BlackSea SEDIMENT
        $this->call(EmpodatSuspectBlackSeaSedimentSeeder::class);

        // File ID 10011 — BlackSea SURFACE WATER
        $this->call(EmpodatSuspectBlackSeaSurfaceWaterSeeder::class);

        // File ID 10012 — TerraChem INVERTEBRATE
        $this->call(EmpodatSuspectTerraChemInvertebrateSeeder::class);

        // File ID 10013 — TerraChem PLANT
        $this->call(EmpodatSuspectTerraChemPlantSeeder::class);

        // File ID 10014 — TerraChem RODENT
        $this->call(EmpodatSuspectTerraChemRodentSeeder::class);

        // File ID 10015 — TerraChem SOIL
        $this->call(EmpodatSuspectTerraChemSoilSeeder::class);

        // Re-populate data sources for every Suspect file (needs all `files` rows).
        $this->call(EmpodatSuspectDataSourceSeeder::class);
    }
}
