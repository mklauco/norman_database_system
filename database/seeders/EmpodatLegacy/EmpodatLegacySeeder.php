<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy;

use Database\Seeders\EmpodatLegacy\Steps\EnsureListMatricesStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportAnalyticalMethodsStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportDataSourcesStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportEmpodatMainStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportEmpodatMinorStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportFilesStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportMatrixBiotaStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportMatrixSedimentsStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportMatrixSoilStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportSimpleLookupsStep;
use Database\Seeders\EmpodatLegacy\Steps\ImportStationsStep;
use Database\Seeders\EmpodatLegacy\Steps\PopulateFileIdStep;
use Database\Seeders\EmpodatLegacy\Steps\ScrubOrphansStep;
use Database\Seeders\EmpodatLegacy\Steps\Step;
use Database\Seeders\EmpodatLegacy\Steps\VacuumAnalyzeStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orchestrates a delta import from the legacy MariaDB `empodat` database
 * into PG. The only input the operator supplies is the "since id" cutoff;
 * the seeder reads it from PG (`max(empodat_main.id)`) by default so the
 * next delta picks up exactly where the previous one left off.
 *
 * Run lifecycle is recorded in `empodat_legacy_import_runs` (one row per run)
 * and each step writes to `empodat_legacy_import_log`. A failed run can be
 * rolled back row-for-row with:
 *
 *   php artisan empodat-legacy:rollback {run_id}
 *
 * Requires the `legacy_empodat` connection in config/database.php pointing at
 * the staging MariaDB — see LEGACY_MIGRATION_PLAN.md.
 *
 * Run:
 *   php artisan db:seed --class=Database\\Seeders\\EmpodatLegacy\\EmpodatLegacySeeder
 *
 * Override cutoff (for testing / backfill):
 *   LEGACY_MIGRATION_SINCE_ID=100028680 php artisan db:seed --class=...
 */
class EmpodatLegacySeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '8G');
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();
        DB::connection('legacy_empodat')->disableQueryLog();

        $sinceId = $this->resolveSinceId();
        $runId = $this->startRun($sinceId);

        $this->command?->info("Legacy delta cutoff: id > {$sinceId}  (run #{$runId})");

        try {
            foreach ($this->buildSteps($sinceId, $runId) as $step) {
                $this->command?->info("→ {$step->name()}");
                $step->run();
            }

            $this->markRunCompleted($runId);
            $this->command?->info("Legacy delta import complete (run #{$runId}).");
        } catch (Throwable $e) {
            $this->markRunFailed($runId, $e);
            $this->command?->error("Run #{$runId} failed: {$e->getMessage()}");
            $this->command?->warn("Roll back with: php artisan empodat-legacy:rollback {$runId}");
            throw $e;
        }
    }

    /**
     * @return list<Step>
     */
    private function buildSteps(int $sinceId, int $runId): array
    {
        return [
            // Phase 1 — precursor lookups (independent)
            new ImportFilesStep($sinceId, $runId, $this->command),
            new ImportDataSourcesStep($sinceId, $runId, $this->command),
            new ImportAnalyticalMethodsStep($sinceId, $runId, $this->command),
            new EnsureListMatricesStep($sinceId, $runId, $this->command),

            // Phase 2 — stations
            new ImportStationsStep($sinceId, $runId, $this->command),

            // Phase 3 — main delta
            new ImportEmpodatMainStep($sinceId, $runId, $this->command),
            new PopulateFileIdStep($sinceId, $runId, $this->command),

            // Phase 4 — dependent 1:1 tables
            new ImportEmpodatMinorStep($sinceId, $runId, $this->command),
            new ImportMatrixBiotaStep($sinceId, $runId, $this->command),
            new ImportMatrixSedimentsStep($sinceId, $runId, $this->command),
            new ImportMatrixSoilStep($sinceId, $runId, $this->command),

            // Phase 5 — post-load cleanup
            new ScrubOrphansStep($sinceId, $runId, $this->command),

            // Phase 6 — lookup tables (data_* -> list_*)
            new ImportSimpleLookupsStep($sinceId, $runId, $this->command),

            // VACUUM at the very end so it picks up Phase 6 inserts too
            new VacuumAnalyzeStep($sinceId, $runId, $this->command),
        ];
    }

    private function resolveSinceId(): int
    {
        $override = config('empodat_legacy.since_id');
        if (is_numeric($override)) {
            return (int) $override;
        }

        return (int) (DB::table('empodat_main')->max('id') ?? 0);
    }

    private function startRun(int $sinceId): int
    {
        return (int) DB::table('empodat_legacy_import_runs')->insertGetId([
            'since_id' => $sinceId,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    private function markRunCompleted(int $runId): void
    {
        DB::table('empodat_legacy_import_runs')->where('id', $runId)->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    private function markRunFailed(int $runId, Throwable $error): void
    {
        DB::table('empodat_legacy_import_runs')->where('id', $runId)->update([
            'status' => 'failed',
            'finished_at' => now(),
            'notes' => substr($error->getMessage(), 0, 1000),
        ]);
    }
}
