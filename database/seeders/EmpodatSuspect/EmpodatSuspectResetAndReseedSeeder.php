<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Services\EmpodatSuspect\SeedRowLimiter;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
 *   1. TRUNCATEs empodat_suspect_main + empodat_suspect_metadata and RESTARTs
 *      their identity sequence, so the first re-imported main row gets id = 1.
 *      This delegates to `empodat-suspect:truncate-main-and-metadata` (see
 *      {@see \App\Console\Commands\TruncateEmpodatSuspectMainAndMetadata}), the
 *      single source of truth for that TRUNCATE — both tables must be named in
 *      the same statement now that `fk_esmd_main` exists, and that command
 *      already carries a production guard and a pre-flight FK check that this
 *      seeder deliberately does not duplicate.
 *   2. Re-imports every source in ascending file_id order. Each source's full
 *      chain completes before the next source begins. Immediately before each
 *      file's Main seeder runs, that file's rows are scope-deleted from
 *      empodat_suspect_substances — see {@see deleteSubstancesForFile()} and
 *      the SAFETY NOTES below for why that table is cleared per-file instead
 *      of truncated.
 *   3. Drops idx_esmd_file_id before the re-import and rebuilds it afterwards
 *      (in a `finally`, so a crash never leaves the schema incomplete), since
 *      that plain btree index is the only meaningful slowdown across a
 *      39M-row load.
 *
 * WHAT IT DOES *NOT* DO (run manually afterwards, per the agreed workflow)
 * -----------------------------------------------------------------------
 *   - It does NOT touch empodat_suspect_data_source at all — neither truncated
 *     nor re-seeded. Its 15 rows key to `files`, which this reload never
 *     touches, so they stay valid exactly as they are. Its seeder also uses a
 *     plain `insert()` with no dedupe, so re-running it here would double the
 *     rows. Deliberately dropped from the chain.
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
 *   - empodat_suspect_substances is deliberately NOT truncated and its identity
 *     sequence is NOT reset. Instead it is scope-deleted per file_id immediately
 *     before that file's Main seeder runs. This is required for the 8 legacy
 *     Main seeders, which bulk-insert their collected substances with a plain
 *     `insert()` and no dedupe (see e.g. EmpodatSuspectApexMainSeeder's
 *     substances flush) — re-running them against an already-populated table
 *     would duplicate ~95k rows per file. The 7 newer seeders already dedupe,
 *     so the same scoped delete is harmless (a no-op) for them too. Doing it
 *     per-file also makes re-running a single file safe on its own.
 *   - TRUNCATE is no longer trivially CASCADE-free: `fk_esmd_main` is a live FK
 *     from empodat_suspect_metadata to empodat_suspect_main, which is exactly
 *     why both tables must be named in one TRUNCATE statement (delegated to
 *     empodat-suspect:truncate-main-and-metadata — see its own docblock). There
 *     is still no CASCADE and no outside table holds a foreign key into either
 *     of these two, which that command's pre-flight guard verifies before it
 *     touches anything.
 *   - This is a long (multi-hour) operation. Each MainSeeder manages its own
 *     memory limits, transaction and foreign-key handling.
 *   - The current run mode — {@see \App\Services\EmpodatSuspect\SeedRowLimiter}'s
 *     "ROW CAP ACTIVE" vs "FULL IMPORT" banner — is printed before the
 *     destructive confirm() prompt and again in the closing summary, so a
 *     smoke-test row cap (EMPODAT_SUSPECT_SEED_ROW_LIMIT) left set in `.env`
 *     can never be mistaken for a real full import.
 *
 * RUN WITH:
 *   php artisan db:seed --class="Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectResetAndReseedSeeder"
 */
class EmpodatSuspectResetAndReseedSeeder extends Seeder
{
    /**
     * The two tables TRUNCATEd before re-import (delegated to
     * empodat-suspect:truncate-main-and-metadata — see {@see truncateSuspectData()}).
     * Listed here only to drive the confirmation banner in run().
     *
     * empodat_suspect_substances and empodat_suspect_data_source are
     * deliberately absent — see the class docblock's SAFETY NOTES and
     * "WHAT IT DOES *NOT* DO" for why each is handled differently.
     *
     * @var list<string>
     */
    private const TABLES_TO_TRUNCATE = [
        'empodat_suspect_main',
        'empodat_suspect_metadata',
    ];

    public function run(): void
    {
        $limiter = new SeedRowLimiter;

        $this->command->warn('==================================================================');
        $this->command->warn(' EMPODAT SUSPECT — FULL RESET AND ORDERED RE-SEED');
        $this->command->warn(' This TRUNCATEs (RESTART IDENTITY):');
        foreach (self::TABLES_TO_TRUNCATE as $table) {
            $this->command->warn('   - '.$table);
        }
        $this->command->warn(' empodat_suspect_substances is NOT truncated — it is cleared per-file,');
        $this->command->warn(' scoped by file_id, immediately before that file re-imports.');
        $this->command->warn(' empodat_suspect_data_source is NOT touched at all.');
        $this->command->warn(' Then re-imports all 15 sources sequentially (file_id 10001 → 10015).');
        $this->command->warn(' RUN MODE: '.$limiter->banner());
        $this->command->warn('==================================================================');

        if (! $this->command->confirm('This is destructive and runs for hours. Proceed?', false)) {
            $this->command->info('Aborted. No changes made.');

            return;
        }

        $this->truncateSuspectData();

        DB::statement('DROP INDEX IF EXISTS idx_esmd_file_id');

        try {
            $this->reseedInOrder();
        } finally {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_esmd_file_id ON empodat_suspect_metadata (file_id)');
        }

        $this->command->info('');
        $this->command->info('RUN MODE was: '.$limiter->banner());
        $this->command->info('Re-seed complete. NEXT (manual) STEPS:');
        $this->command->info('  1. Rescan each file in the web UI to repopulate main_id_from / main_id_to / number_of_records.');
        $this->command->info('  2. Refresh: refresh-filters -> refresh-matrix-metadata -> refresh-prioritisation, then station_filters MV + generate-statistics.');
    }

    /**
     * Step 1: delegate the TRUNCATE of empodat_suspect_main + empodat_suspect_metadata
     * to `empodat-suspect:truncate-main-and-metadata` — the single source of truth for
     * that statement (see {@see \App\Console\Commands\TruncateEmpodatSuspectMainAndMetadata}).
     * Both tables must be named together in one statement, and that command already
     * carries a production guard plus a pre-flight FK check, so this seeder does not
     * issue its own TRUNCATE.
     *
     * `--force` skips that command's own confirmation prompt — this seeder already
     * obtained one explicit confirm() in run(). A non-SUCCESS exit aborts the whole
     * re-seed rather than importing on top of a truncate that may not have happened.
     */
    private function truncateSuspectData(): void
    {
        $this->command->info('Delegating to empodat-suspect:truncate-main-and-metadata...');

        $exitCode = $this->command->callSilent('empodat-suspect:truncate-main-and-metadata', ['--force' => true]);

        if ($exitCode !== Command::SUCCESS) {
            throw new RuntimeException(
                "ABORTED: empodat-suspect:truncate-main-and-metadata exited with code {$exitCode}. "
                .'No seeding was attempted. Run it directly '
                .'(php artisan empodat-suspect:truncate-main-and-metadata) to see the full error output — '
                .'likely its production guard or pre-flight FK check.'
            );
        }

        $this->command->info('Truncate complete: empodat_suspect_main + empodat_suspect_metadata are empty, identity restarted.');
    }

    /**
     * Step 2: import every source in ascending file_id order. Each source's
     * full chain finishes before the next begins, so the *MainSeeder runs — and
     * therefore the assigned id ranges — are strictly sequential and never overlap.
     * Immediately before each file's Main seeder runs, that file's rows are
     * scope-deleted from empodat_suspect_substances — see
     * {@see deleteSubstancesForFile()}.
     */
    private function reseedInOrder(): void
    {
        // Create the 8 legacy `files` rows (10001–10008) up front (updateOrCreate).
        // The 7 newer `files` rows (10009–10015) are created by their own
        // per-source orchestrators below.
        $this->call(EmpodatSuspectFileSeeder::class);

        // ---- LEGACY SOURCES (10001–10008) -----------------------------------
        // No per-source orchestrator exists, so each chain is spelled out here:
        //   XlsxStationsMapping -> XlsxStationsMappingFill -> [substances delete] -> Main
        // (substances are now collected inside each *MainSeeder's single read pass)

        // File ID 10001 — CONNECT 1 SEDIMENT
        $this->call([
            EmpodatSuspectSedimentXlsxStationsMappingSeeder::class,
            EmpodatSuspectSedimentXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10001);
        $this->call(EmpodatSuspectSedimentMainSeeder::class);

        // File ID 10002 — CONNECT 1 BIOTA
        $this->call([
            EmpodatSuspectBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectBiotaXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10002);
        $this->call(EmpodatSuspectBiotaMainSeeder::class);

        // File ID 10003 — CONNECT 2 SEDIMENTS
        $this->call([
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingSeeder::class,
            EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10003);
        $this->call(EmpodatSuspectConnect2SedimentsMainSeeder::class);

        // File ID 10004 — CONNECT 2 BIOTA
        $this->call([
            EmpodatSuspectConnect2BiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectConnect2BiotaXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10004);
        $this->call(EmpodatSuspectConnect2BiotaMainSeeder::class);

        // File ID 10005 — HELCOM PreEMPT SEDIMENTS
        $this->call([
            EmpodatSuspectHelcomSedimentsXlsxStationsMappingSeeder::class,
            EmpodatSuspectHelcomSedimentsXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10005);
        $this->call(EmpodatSuspectHelcomSedimentsMainSeeder::class);

        // File ID 10006 — HELCOM PreEMPT BIOTA
        $this->call([
            EmpodatSuspectHelcomBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectHelcomBiotaXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10006);
        $this->call(EmpodatSuspectHelcomBiotaMainSeeder::class);

        // File ID 10007 — LIFE APEX
        $this->call([
            EmpodatSuspectApexXlsxStationsMappingSeeder::class,
            EmpodatSuspectApexXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10007);
        $this->call(EmpodatSuspectApexMainSeeder::class);

        // File ID 10008 — UBA-HELCOM
        $this->call([
            EmpodatSuspectUbaHelcomXlsxStationsMappingSeeder::class,
            EmpodatSuspectUbaHelcomXlsxStationsMappingFillSeeder::class,
        ]);
        $this->deleteSubstancesForFile(10008);
        $this->call(EmpodatSuspectUbaHelcomMainSeeder::class);

        // ---- NEWER SOURCES (10009–10015) ------------------------------------
        // Each orchestrator runs its own chain:
        //   File -> XlsxStationsMapping -> XlsxStationsMappingFill -> Main+Metadata
        // We don't own those orchestrator classes, so the earliest point reachable
        // from here is immediately before the orchestrator call itself. That is
        // still effectively "immediately before Main" in practice: nothing before
        // the Main+Metadata step in that chain touches empodat_suspect_substances.

        // File ID 10009 — BlackSea BIOTA
        $this->deleteSubstancesForFile(10009);
        $this->call(EmpodatSuspectBlackSeaBiotaSeeder::class);

        // File ID 10010 — BlackSea SEDIMENT
        $this->deleteSubstancesForFile(10010);
        $this->call(EmpodatSuspectBlackSeaSedimentSeeder::class);

        // File ID 10011 — BlackSea SURFACE WATER
        $this->deleteSubstancesForFile(10011);
        $this->call(EmpodatSuspectBlackSeaSurfaceWaterSeeder::class);

        // File ID 10012 — TerraChem INVERTEBRATE
        $this->deleteSubstancesForFile(10012);
        $this->call(EmpodatSuspectTerraChemInvertebrateSeeder::class);

        // File ID 10013 — TerraChem PLANT
        $this->deleteSubstancesForFile(10013);
        $this->call(EmpodatSuspectTerraChemPlantSeeder::class);

        // File ID 10014 — TerraChem RODENT
        $this->deleteSubstancesForFile(10014);
        $this->call(EmpodatSuspectTerraChemRodentSeeder::class);

        // File ID 10015 — TerraChem SOIL
        $this->deleteSubstancesForFile(10015);
        $this->call(EmpodatSuspectTerraChemSoilSeeder::class);
    }

    /**
     * Scoped delete of one file's rows from empodat_suspect_substances, run
     * immediately before that file's Main seeder. The table is deliberately
     * NOT truncated as a whole — see the class docblock's SAFETY NOTES — so
     * that re-running a single file's chain (e.g. after fixing a bad source
     * spreadsheet) is safe without disturbing every other file's substances.
     */
    private function deleteSubstancesForFile(int $fileId): void
    {
        $deleted = DB::table('empodat_suspect_substances')->where('file_id', $fileId)->delete();

        $this->command->info("  Cleared {$deleted} existing empodat_suspect_substances row(s) for file_id={$fileId}.");
    }
}
