<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshEmpodatSuspectPrioritisation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:refresh-prioritisation
                            {--file= : Rebuild only the partition for this files.id (e.g. 10009). Omit to rebuild every partition.}
                            {--stats : Show statistics after the rebuild}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild one or all partitions of the empodat_suspect_prioritisation table';

    /**
     * Name of the LIST-partitioned parent table.
     */
    private const TABLE = 'empodat_suspect_prioritisation';

    /**
     * Name of the table tracking one row per partition rebuild.
     */
    private const BUILDS_TABLE = 'empodat_suspect_prioritisation_builds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║  Empodat Suspect - Rebuild Prioritisation Partitions               ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $fileIds = $this->resolveFileIds();

        if ($fileIds === []) {
            $this->error('✗ No file_id values found to rebuild.');

            return Command::FAILURE;
        }

        $failures = 0;

        foreach ($fileIds as $fileId) {
            if (! $this->rebuildPartition($fileId)) {
                $failures++;
            }
        }

        if ($this->option('stats')) {
            $this->newLine();
            $this->showStatistics();
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error("✗ {$failures} of ".count($fileIds).' partition(s) failed to rebuild.');

            return Command::FAILURE;
        }

        $this->info('✓ All partitions rebuilt successfully.');

        return Command::SUCCESS;
    }

    /**
     * Resolve the list of files.id values whose partition should be rebuilt.
     *
     * @return list<int>
     */
    private function resolveFileIds(): array
    {
        $option = $this->option('file');

        if ($option !== null) {
            if (! is_numeric($option)) {
                $this->error("✗ --file must be numeric, got: {$option}");

                return [];
            }

            return [(int) $option];
        }

        return DB::table('empodat_suspect_main')
            ->whereNotNull('file_id')
            ->distinct()
            ->orderBy('file_id')
            ->pluck('file_id')
            ->map(static fn (int|string $fileId): int => (int) $fileId)
            ->all();
    }

    /**
     * Rebuild a single partition.
     *
     * Builds a standalone staging table, populates it, then swaps it in for
     * the live partition inside a single transaction (DETACH old / ATTACH
     * new / DROP old) so readers querying the parent table never see a
     * missing or half-built partition. Never call this concurrently for
     * different file_ids on the same table from multiple processes.
     */
    private function rebuildPartition(int $fileId): bool
    {
        $this->info("→ Rebuilding partition for file_id={$fileId}...");

        $buildId = DB::table(self::BUILDS_TABLE)->insertGetId([
            'file_id' => $fileId,
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => 'cli',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start = microtime(true);
        $partitionTable = self::TABLE."_{$fileId}";
        $stagingTable = self::TABLE."_{$fileId}_staging";

        try {
            // Clean up a staging table left behind by a previous failed run
            DB::statement("DROP TABLE IF EXISTS {$stagingTable} CASCADE");

            $this->createStagingTable($stagingTable);

            $rowCount = $this->populateStagingTable($stagingTable, $fileId);

            // Matching CHECK constraint lets ATTACH PARTITION skip re-scanning
            // the table to validate the partition bound
            DB::statement("
                ALTER TABLE {$stagingTable}
                ADD CONSTRAINT {$stagingTable}_file_id_check
                CHECK (file_id = {$fileId})
            ");

            DB::transaction(function () use ($partitionTable, $stagingTable, $fileId): void {
                if ($this->partitionExists($partitionTable)) {
                    DB::statement('ALTER TABLE '.self::TABLE." DETACH PARTITION {$partitionTable}");
                    DB::statement("DROP TABLE {$partitionTable}");
                }

                DB::statement('
                    ALTER TABLE '.self::TABLE.'
                    ATTACH PARTITION '.$stagingTable.'
                    FOR VALUES IN ('.$fileId.')
                ');

                DB::statement("ALTER TABLE {$stagingTable} RENAME TO {$partitionTable}");
            });

            $durationMs = (int) round((microtime(true) - $start) * 1000);

            DB::table(self::BUILDS_TABLE)->where('id', $buildId)->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_ms' => $durationMs,
                'row_count' => $rowCount,
                'updated_at' => now(),
            ]);

            $this->info("  ✓ file_id={$fileId}: {$rowCount} rows in {$durationMs}ms");

            return true;
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            // Never leave a staging table lying around after a failure
            DB::statement("DROP TABLE IF EXISTS {$stagingTable} CASCADE");

            DB::table(self::BUILDS_TABLE)->where('id', $buildId)->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
                'updated_at' => now(),
            ]);

            $this->error("  ✗ file_id={$fileId} failed: ".$e->getMessage());

            Log::error('Empodat Suspect prioritisation partition rebuild failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Whether a table with the given name currently exists (and, by naming
     * convention, is attached as a partition of the parent table).
     */
    private function partitionExists(string $tableName): bool
    {
        $result = DB::selectOne('SELECT to_regclass(?) IS NOT NULL AS exists', [$tableName]);

        return (bool) ($result->exists ?? false);
    }

    /**
     * Create a standalone table with the same column structure as the
     * empodat_suspect_prioritisation parent, ready to be populated and later
     * attached as one of its partitions.
     */
    private function createStagingTable(string $tableName): void
    {
        DB::statement("
            CREATE TABLE {$tableName} (
                id BIGINT NOT NULL,
                file_id BIGINT NOT NULL,
                matrix BIGINT NULL,
                concentration_value DOUBLE PRECISION NULL,
                ip_max DOUBLE PRECISION NULL,
                country VARCHAR(255) NULL,
                station_name BIGINT NULL,
                sampling_date_y SMALLINT NULL,
                latitude_decimal DOUBLE PRECISION NULL,
                longitude_decimal DOUBLE PRECISION NULL,
                sus_id VARCHAR(255) NULL,
                basin_name VARCHAR(255) NULL,
                df_id SMALLINT NULL,
                dsa_id SMALLINT NULL,
                dsgr_id SMALLINT NULL,
                dtiel_id SMALLINT NULL,
                dmeas_id SMALLINT NULL,
                effluent_influent_id SMALLINT NULL,
                PRIMARY KEY (id, file_id)
            )
        ");
    }

    /**
     * Populate the staging table for one file_id and return the number of
     * rows inserted.
     */
    private function populateStagingTable(string $tableName, int $fileId): int
    {
        return DB::affectingStatement("
            INSERT INTO {$tableName} (
                id, file_id, matrix, concentration_value, ip_max, country, station_name,
                sampling_date_y, latitude_decimal, longitude_decimal, sus_id, basin_name,
                df_id, dsa_id, dsgr_id, dtiel_id, dmeas_id, effluent_influent_id
            )
            WITH limited_suspect AS (
                SELECT * FROM empodat_suspect_main WHERE file_id = {$fileId}
            ),
            most_recent_main AS (
                -- Get most recent empodat_main record per station
                -- This prevents Cartesian product and massive memory usage.
                -- Joins on station_id ONLY: joining on substance_id too was
                -- investigated and rejected, it drops 91.5% of rows.
                SELECT DISTINCT ON (station_id)
                    id,
                    station_id,
                    matrix_id,
                    sampling_date_year
                FROM empodat_main
                WHERE station_id IN (SELECT DISTINCT station_id FROM limited_suspect)
                ORDER BY station_id, sampling_date_year DESC NULLS LAST
            )
            SELECT
                -- Primary identifiers
                esm.id,
                esm.file_id,

                -- Core fields from suspect data
                em.matrix_id AS matrix,
                esm.concentration AS concentration_value,
                esm.ip_max,

                -- Geographic and temporal information
                es.country AS country,
                esm.station_id AS station_name,
                em.sampling_date_year AS sampling_date_y,
                es.latitude AS latitude_decimal,
                es.longitude AS longitude_decimal,

                -- Substance information
                ss.code AS sus_id,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) AS basin_name,

                -- df_id (from water_waste only)
                emww.df_id,

                -- dsa_id (from water_waste)
                emww.dsa_id,

                -- dsgr_id (from biota)
                emb.dsgr_id,

                -- dtiel_id (from biota)
                emb.dtiel_id,

                -- dmeas_id (from biota)
                emb.dmeas_id,

                -- effluent_influent_id (from water_waste)
                emww.effluent_influent_id

            FROM limited_suspect esm

            -- Join to stations for geographic data
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to most recent empodat_main record per station
            LEFT JOIN most_recent_main em
                ON em.station_id = esm.station_id

            -- Join to substances for substance code
            LEFT JOIN susdat_substances ss
                ON esm.substance_id = ss.id

            -- Matrix-specific LEFT JOINs based on matrix_id ranges

            -- Biota (matrix_id: 39-47)
            LEFT JOIN empodat_matrix_biota emb
                ON em.id = emb.id
                AND em.matrix_id BETWEEN 39 AND 47

            -- Water waste (matrix_id: 72-74)
            LEFT JOIN empodat_matrix_water_waste emww
                ON em.id = emww.id
                AND em.matrix_id BETWEEN 72 AND 74

            -- Water surface (matrix_id: specific IDs)
            LEFT JOIN empodat_matrix_water_surface emws
                ON em.id = emws.id
                AND em.matrix_id IN (2,3,4,5,6,7,8)

            -- Water ground (matrix_id: 1)
            LEFT JOIN empodat_matrix_water_ground emwg
                ON em.id = emwg.id
                AND em.matrix_id = 1

            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL
        ");
    }

    /**
     * Show statistics about the current contents of the table.
     */
    private function showStatistics(): void
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║         Table Statistics               ║');
        $this->info('╚════════════════════════════════════════╝');

        try {
            // Get row count
            $rowCount = DB::table(self::TABLE)->count();
            $this->line('  Total records:            '.number_format($rowCount));

            // Get unique stations
            $stationCount = DB::table(self::TABLE)
                ->distinct('station_name')
                ->count('station_name');
            $this->line('  Unique stations:          '.number_format($stationCount));

            // Get unique countries
            $countryCount = DB::table(self::TABLE)
                ->distinct('country')
                ->count('country');
            $this->line('  Unique countries:         '.number_format($countryCount));

            // Get unique substances
            $substanceCount = DB::table(self::TABLE)
                ->distinct('sus_id')
                ->count('sus_id');
            $this->line('  Unique substances:        '.number_format($substanceCount));

            // Get matrix distribution
            $matrixDistribution = DB::table(self::TABLE)
                ->select('matrix', DB::raw('count(*) as count'))
                ->groupBy('matrix')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            $this->line("\n  Top 5 matrices by record count:");
            foreach ($matrixDistribution as $matrix) {
                $this->line("    Matrix {$matrix->matrix}: ".number_format($matrix->count));
            }

            // Get records with matrix-specific data
            $biotaCount = DB::table(self::TABLE)
                ->whereNotNull('dsgr_id')
                ->count();
            $this->line("\n  Records with biota data:  ".number_format($biotaCount));

            $waterWasteCount = DB::table(self::TABLE)
                ->whereNotNull('df_id')
                ->count();
            $this->line('  Records with water waste data: '.number_format($waterWasteCount));

            // Sizes must be summed across the partition tree: a partitioned
            // parent holds no storage of its own, so pg_relation_size() and
            // pg_total_relation_size() both report 0 bytes for it.
            $sizeResult = DB::select("
                SELECT pg_size_pretty(SUM(pg_total_relation_size(relid))) AS total_size,
                       pg_size_pretty(SUM(pg_relation_size(relid)))       AS data_size
                FROM pg_partition_tree('".self::TABLE."')
                WHERE isleaf
            ");
            $this->line("\n  Total size (with indexes): ".($sizeResult[0]->total_size ?? 'N/A'));
            $this->line('  Table size (data only):    '.($sizeResult[0]->data_size ?? 'N/A'));

            // Count non-null values for sparse columns
            $this->line("\n  Non-null values in matrix-specific columns:");
            $sparseColumns = ['basin_name', 'df_id', 'dsa_id', 'dsgr_id', 'dtiel_id', 'dmeas_id', 'effluent_influent_id'];
            foreach ($sparseColumns as $column) {
                $nonNullCount = DB::table(self::TABLE)
                    ->whereNotNull($column)
                    ->count();
                $percentage = $rowCount > 0 ? round(($nonNullCount / $rowCount) * 100, 2) : 0;
                $this->line("    {$column}: ".number_format($nonNullCount)." ({$percentage}%)");
            }

        } catch (\Exception $e) {
            $this->warn('  Could not retrieve all statistics: '.$e->getMessage());
        }
    }
}
