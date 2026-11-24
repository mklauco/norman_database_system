<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Runs every EMPODAT reconcile seeder in order, each as its own audited run
 * (undo one with `php artisan empodat-reconcile:rollback {run_id}`), then
 * fixes the id sequences, vacuums the touched tables and refreshes the
 * EMPODAT statistics. Stops at the first error.
 *
 * Dry run (reads only):
 *   docker exec nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\EmpodatReconcileSeeder' --force
 * Execute:
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\EmpodatReconcileSeeder' --force
 */
final class EmpodatReconcileSeeder extends Seeder
{
    /** @var list<class-string<ReconcileSeeder>> */
    private const SEEDERS = [
        ReconcileMissingDataSourcesSeeder::class,
        ReconcileDataSourceFieldsSeeder::class,
        ReconcileAnalyticalMethodFieldsSeeder::class,
        ReconcileDuplicateMatrixRowsSeeder::class,
        ReconcileOrphanMatrixRowsSeeder::class,
        ReconcileMeasurementMethodsSeeder::class,
        ReconcileMeasurementSubstancesSeeder::class,
        ReconcileSamplingDatePartsSeeder::class,
    ];

    private const TOUCHED_TABLES = [
        'empodat_data_sources',
        'empodat_analytical_methods',
        'empodat_matrix_water_surface',
        'empodat_matrix_water_waste',
        'empodat_main',
        'empodat_minor',
    ];

    public function run(): void
    {
        $execute = ReconcileOptions::fromEnvironment()->isExecute();

        foreach (self::SEEDERS as $seeder) {
            $this->call($seeder);
            if ($execute && $seeder === ReconcileMissingDataSourcesSeeder::class) {
                $this->artisan('db:fix-sequences');
            }
        }

        if ($execute) {
            foreach (self::TOUCHED_TABLES as $table) {
                $this->command?->info('VACUUM (ANALYZE) '.$table);
                DB::statement('VACUUM (ANALYZE) '.$table);
            }
            $this->artisan('empodat:generate-statistics');
            $this->artisan('factsheets:generate-statistics');
        }

        $this->command?->info('EmpodatReconcileSeeder finished at '.now()->toDateTimeString().'.');
    }

    private function artisan(string $command): void
    {
        $this->command?->info('php artisan '.$command);
        Artisan::call($command, [], $this->command?->getOutput());
    }
}
