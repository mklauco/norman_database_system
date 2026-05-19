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
use Database\Seeders\EmpodatLegacy\Steps\ImportStationsStep;
use Database\Seeders\EmpodatLegacy\Steps\PopulateFileIdStep;
use Database\Seeders\EmpodatLegacy\Steps\ScrubOrphansStep;
use Database\Seeders\EmpodatLegacy\Steps\Step;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates a delta import from the legacy MariaDB `empodat` database
 * into PG. The only input the operator supplies is the "since id" cutoff;
 * the seeder reads it from PG (`max(empodat_main.id)`) by default so the
 * next delta picks up exactly where the previous one left off.
 *
 * Requires a configured database connection named `legacy_empodat` in
 * `config/database.php` pointing at the staging MariaDB (see
 * LEGACY_MIGRATION_PLAN.md for the credentials).
 *
 * Run:
 *   php artisan db:seed --class=Database\\Seeders\\EmpodatLegacy\\EmpodatLegacySeeder
 *
 * To override the cutoff explicitly (e.g. for dry-run or backfill):
 *   LEGACY_MIGRATION_SINCE_ID=100028680 php artisan db:seed --class=...
 */
class EmpodatLegacySeeder extends Seeder
{
    public function run(): void
    {
        $sinceId = $this->resolveSinceId();
        $this->command?->info("Legacy delta cutoff: id > {$sinceId}");

        /** @var list<Step> $steps */
        $steps = [
            // Phase 1 — precursor lookups (independent)
            new ImportFilesStep($sinceId, $this->command),
            new ImportDataSourcesStep($sinceId, $this->command),
            new ImportAnalyticalMethodsStep($sinceId, $this->command),
            new EnsureListMatricesStep($sinceId, $this->command),

            // Phase 2 — stations
            new ImportStationsStep($sinceId, $this->command),

            // Phase 3 — main delta
            new ImportEmpodatMainStep($sinceId, $this->command),
            new PopulateFileIdStep($sinceId, $this->command),

            // Phase 4 — dependent 1:1 tables
            new ImportEmpodatMinorStep($sinceId, $this->command),
            new ImportMatrixBiotaStep($sinceId, $this->command),

            // Phase 5 — post-load cleanup
            new ScrubOrphansStep($sinceId, $this->command),
        ];

        foreach ($steps as $step) {
            $this->command?->info("→ {$step->name()}");
            $step->run();
        }

        $this->command?->info('Legacy delta import complete.');
    }

    private function resolveSinceId(): int
    {
        $override = config('empodat_legacy.since_id');
        if (is_numeric($override)) {
            return (int) $override;
        }

        return (int) (DB::table('empodat_main')->max('id') ?? 0);
    }
}
