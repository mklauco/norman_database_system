<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\EmpodatLegacyBackfillSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;
use RuntimeException;

/**
 * Second EMPODAT reconcile batch (decisions of 2026-11-26), in this order, each
 * step its own audited run, stopping at the first error:
 *
 *  1. station names (1b.10)
 *  2. uncertainty, rating and range of 45 methods (1b.11; needs migration 2026_11_26_100133)
 *  3. LoD and LoQ of all methods from legacy (item 9)
 *  4. delete the file 481 rows on the ids of file 613 (item 2)
 *  5. id sequences, then the legacy backfill of that id window, which inserts the file 613 rows
 *  6. sampling month/day of that id window
 *  7. matrix, year, indicator, method and substance of all measurements (item 9)
 *  8. id sequences, VACUUM (ANALYZE), EMPODAT and factsheet statistics, suspect
 *     filters, matrix metadata and prioritisation (execute only; a failing command stops the run)
 *
 * Dry run (reads only; steps 5-6 see nothing to do because step 4 did not write):
 *   docker exec nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\EmpodatReconcileBatch2Seeder' --force
 * Execute:
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\EmpodatReconcileBatch2Seeder' --force
 */
final class EmpodatReconcileBatch2Seeder extends Seeder
{
    private const TOUCHED_TABLES = [
        'empodat_stations',
        'empodat_analytical_methods',
        'empodat_main',
        'empodat_minor',
        'empodat_matrix_water_surface',
    ];

    public function run(): void
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $options = ReconcileOptions::fromEnvironment();
        $execute = $options->isExecute();

        $this->call(ReconcileStationNamesSeeder::class);
        $this->call(ReconcileAnalyticalMethodValuesSeeder::class);
        $this->call(ReconcileMethodLimitsSeeder::class);
        $this->call(ReconcileFile613Seeder::class);

        if ($execute) {
            $this->artisan('db:fix-sequences');
        }

        $window = [
            'LEGACY_BACKFILL_ID_FROM' => (string) ReconcileFile613Seeder::ID_FROM,
            'LEGACY_BACKFILL_ID_TO' => (string) ReconcileFile613Seeder::ID_TO,
            'LEGACY_BACKFILL_MODE' => $execute ? 'execute' : 'dry-run',
            'LEGACY_BACKFILL_EXECUTE' => $execute ? 'true' : 'false',
            'LEGACY_BACKFILL_DRY_RUN_READ_ONLY_OK' => $execute ? 'false' : 'true',
            'LEGACY_BACKFILL_EXPECT_PG_DATABASE' => (string) getenv('RECONCILE_EXPECT_PG_DATABASE'),
        ];
        $this->withEnvironment($window, fn () => $this->call(EmpodatLegacyBackfillSeeder::class));

        $this->withEnvironment([
            'RECONCILE_ID_FROM' => (string) ReconcileFile613Seeder::ID_FROM,
            'RECONCILE_ID_TO' => (string) ReconcileFile613Seeder::ID_TO,
        ], fn () => $this->call(ReconcileSamplingDatePartsSeeder::class));

        $this->call(ReconcileMeasurementValuesSeeder::class);

        if ($execute) {
            $this->artisan('db:fix-sequences');
            foreach (self::TOUCHED_TABLES as $table) {
                $this->command?->info('VACUUM (ANALYZE) '.$table);
                DB::statement('VACUUM (ANALYZE) '.$table);
            }
            $this->artisan('empodat:generate-statistics', ['--sync' => true]);
            $this->artisan('factsheets:generate-statistics');
            $this->artisan('empodat-suspect:refresh-filters', ['--create' => true]);
            $this->artisan('empodat-suspect:refresh-matrix-metadata', ['--create' => true]);
            $this->artisan('empodat-suspect:refresh-prioritisation');
        }

        $this->command?->info('EmpodatReconcileBatch2Seeder finished at '.now()->toDateTimeString().'.');
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function withEnvironment(array $variables, callable $callback): void
    {
        $previous = [];
        foreach ($variables as $name => $value) {
            $previous[$name] = getenv($name);
            putenv($name.'='.$value);
        }

        try {
            $callback();
        } finally {
            foreach ($previous as $name => $value) {
                putenv($value === false ? $name : $name.'='.$value);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function artisan(string $command, array $parameters = []): void
    {
        $this->command?->info('php artisan '.$command);
        $exitCode = Artisan::call($command, $parameters, $this->command?->getOutput());
        if ($exitCode !== 0) {
            throw new RuntimeException('php artisan '.$command.' failed with exit code '.$exitCode.'.');
        }
    }
}
