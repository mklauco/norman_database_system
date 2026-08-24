<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rebuild the LIST-partitioned `empodat_suspect_prioritisation_dataset`
 * table, which lives alongside — and is intended to eventually replace —
 * the `empodat_suspect_prioritisation` materialized view.
 *
 * WHICH empodat_main ROW SUPPLIES matrix, year AND THE MATRIX-SPECIFIC COLUMNS
 * ---------------------------------------------------------------------------
 * `empodat_suspect_prioritisation_dataset` carries columns that do not exist
 * in the suspect data at all — `matrix`, `sampling_date_y`, `basin_name`, `df_id`,
 * `dsa_id`, `dsgr_id`, `dtiel_id`, `dmeas_id`, `effluent_influent_id`. They are
 * borrowed from the legacy EMPODAT data along this path:
 *
 *   empodat_suspect_main.station_id
 *        = empodat_main.station_id
 *          -> empodat_main.id
 *             = empodat_matrix_<X>.id  ->  the column
 *
 * `empodat_stations` is NOT a hop in that chain. It is joined in parallel and
 * supplies only `country`, `latitude_decimal` and `longitude_decimal`.
 *
 * The chain is lossy at its first step, and this is the whole design question:
 * one station has MANY `empodat_main` rows — up to 2 863 in the current data,
 * across different years and different matrices — but each `empodat_matrix_<X>`
 * row belongs to exactly one of them. So a single row must be chosen per
 * station, and that choice decides every borrowed column.
 *
 * DECISION: take the FIRST row, i.e. the lowest `empodat_main.id`
 * ---------------------------------------------------------------
 * Previously this was the MOST RECENT row, ordered by
 * `sampling_date_year DESC NULLS LAST`. That was changed deliberately.
 *
 * "Most recent" made already-built partitions go stale without their own source
 * file changing: importing legacy EMPODAT data for a station could introduce a
 * newer row, change which row won, and silently alter the matrix, year and
 * matrix-specific columns of suspect rows belonging to entirely different
 * files. Every suspect station has more than one `empodat_main` row (measured:
 * all 750, up to 2 863), so the tie-break is always live.
 *
 * "First" removes that: `empodat_main.id` is a monotonically increasing
 * sequence, so any row added later gets a HIGHER id and can never displace the
 * existing choice. A partition built today stays correct tomorrow, which is
 * what makes per-file rebuilds sound.
 *
 * The trade-off is explicit and accepted: the borrowed matrix and year are the
 * station's OLDEST legacy measurement rather than its newest.
 *
 * WHY basin_name IS RESOLVED SEPARATELY
 * -------------------------------------
 * `basin_name` is a property of the PLACE, not of the measurement — measured
 * across the whole database it is identical for 99.05% of stations (935 of
 * 97 943 disagree). Routing it through the chosen row therefore threw data away
 * for no reason: if that row happened to sit in a matrix table the query did not
 * join, the station lost its basin even though a dozen sibling rows carried it.
 * It is now resolved per station from any of the eight matrix tables that have
 * the column (all except `empodat_matrix_air`), ties broken on the lowest
 * `empodat_main.id` so rebuilds are reproducible.
 *
 * The other six borrowed columns are genuinely measurement-level — a station's
 * 2019 sediment sample and its 2025 biota sample legitimately differ — so they
 * stay tied to the chosen row.
 *
 * WHY NOT JOIN ON substance_id
 * ----------------------------
 * Adding `empodat_main.substance_id` to the station join was measured on
 * 2026-08-24 and rejected: only 10 816 of 122 763 suspect
 * (station, substance) pairs exist in `empodat_main`, because suspect screening
 * exists precisely to find substances legacy monitoring never measured. The
 * closing `WHERE ... em.id IS NOT NULL` would then discard 91.5% of all rows.
 *
 * @see Empodat-Suspect-1-database.md in the internal documentation repository
 */
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
    protected $description = 'Rebuild one or all partitions of the empodat_suspect_prioritisation_dataset table';

    /**
     * Name of the LIST-partitioned parent table.
     */
    private const TABLE = 'empodat_suspect_prioritisation_dataset';

    /**
     * Name of the table tracking one row per partition rebuild.
     */
    private const BUILDS_TABLE = 'empodat_suspect_prioritisation_builds';

    /**
     * Per-station basin lookup, rebuilt once at the start of every run by
     * {@see buildBasinHelper()}. Derived data with no migration of its own,
     * following the same pattern as `empodat_suspect_stations_helper`.
     */
    private const BASIN_HELPER_TABLE = 'empodat_suspect_prioritisation_basin_helper';

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

        $this->buildBasinHelper();

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
     * empodat_suspect_prioritisation_dataset parent, ready to be populated
     * and later attached as one of its partitions.
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
    /**
     * Build the per-station basin lookup, once per command run.
     *
     * This deliberately does NOT live inside the per-partition INSERT. Its
     * result depends only on `empodat_main` and the eight matrix tables that
     * carry a `basin_name`, so it is identical for every partition — but the
     * UNION over those tables spans roughly 100 million rows (96 M in
     * `empodat_matrix_water_surface` alone). Computing it inline made a full
     * 15-partition rebuild pay that scan fifteen times, taking the run from
     * 7 seconds to 1 minute 50. Materialising it once collapses that back to a
     * single scan feeding a lookup of one row per station.
     *
     * Scoped to stations that actually appear in `empodat_suspect_main`, so the
     * result stays small regardless of how large the matrix tables grow.
     *
     * Ties are broken on the lowest `empodat_main.id` so rebuilds are
     * reproducible — 935 of 97 943 stations carry more than one distinct
     * `basin_name` across their legacy rows.
     */
    private function buildBasinHelper(): void
    {
        $this->info('→ Building per-station basin lookup...');
        $start = microtime(true);

        DB::statement('DROP TABLE IF EXISTS '.self::BASIN_HELPER_TABLE);

        DB::statement('
            CREATE TABLE '.self::BASIN_HELPER_TABLE.' AS
            SELECT DISTINCT ON (em.station_id)
                em.station_id,
                mx.basin_name
            FROM empodat_main em
            INNER JOIN (
                SELECT id, basin_name FROM empodat_matrix_biota            WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_sediments        WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_soil             WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_water_surface    WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_water_ground     WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_water_waste      WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_suspended_matter WHERE basin_name IS NOT NULL
                UNION ALL SELECT id, basin_name FROM empodat_matrix_sewage_sludge    WHERE basin_name IS NOT NULL
            ) mx ON mx.id = em.id
            WHERE em.station_id IN (SELECT DISTINCT station_id FROM empodat_suspect_main WHERE station_id IS NOT NULL)
            ORDER BY em.station_id, em.id ASC
        ');

        DB::statement('CREATE UNIQUE INDEX idx_espbh_station_id ON '.self::BASIN_HELPER_TABLE.'(station_id)');

        $count = DB::table(self::BASIN_HELPER_TABLE)->count();
        $duration = round(microtime(true) - $start, 2);

        $this->info("  ✓ {$count} station(s) with a basin ({$duration}s)");
    }

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
            first_main AS (
                -- ONE empodat_main record per station: the FIRST one, i.e. the
                -- lowest empodat_main.id. See the class docblock for why this
                -- row must be chosen at all, and why 'first' rather than 'most
                -- recent'. Collapsing to one row per station also prevents a
                -- Cartesian product.
                --
                -- Joins on station_id ONLY. Adding substance_id was measured
                -- and rejected: only 10 816 of 122 763 suspect
                -- (station, substance) pairs exist in empodat_main, so it would
                -- drop 91.5% of rows.
                SELECT DISTINCT ON (station_id)
                    id,
                    station_id,
                    matrix_id,
                    sampling_date_year
                FROM empodat_main
                WHERE station_id IN (SELECT DISTINCT station_id FROM limited_suspect)
                ORDER BY station_id, id ASC
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

                -- Station-level: independent of which empodat_main row was chosen
                sb.basin_name,

                -- Measurement-level: these come from the matrix table that owns
                -- first_main's chosen row, so exactly one of the joins below can
                -- contribute and the rest are NULL.
                emww.df_id,
                emww.dsa_id,
                emb.dsgr_id,
                emb.dtiel_id,
                emb.dmeas_id,
                emww.effluent_influent_id

            FROM limited_suspect esm

            -- Join to stations for geographic data
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to the chosen empodat_main record per station
            LEFT JOIN first_main em
                ON em.station_id = esm.station_id

            -- Station-level basin, resolved independently of first_main.
            -- Built once per command run by buildBasinHelper().
            LEFT JOIN ".self::BASIN_HELPER_TABLE." sb
                ON sb.station_id = esm.station_id

            -- Join to substances for substance code
            LEFT JOIN susdat_substances ss
                ON esm.substance_id = ss.id

            -- Matrix-specific LEFT JOINs, keyed purely on empodat_main.id.
            --
            -- There is deliberately NO 'AND em.matrix_id ...' predicate here.
            -- The previous implementation carried one per join and three of the
            -- four were wrong against the data: water_ground filtered on
            -- matrix_id = 1, which has zero rows in empodat_main (ground water is
            -- matrix 10); water_surface omitted matrix 9; water_waste omitted
            -- matrices 11-14. The predicates are also unnecessary — the matrix
            -- tables partition empodat_main.id almost perfectly, with only 43 ids
            -- in the entire database appearing in more than one of them.
            LEFT JOIN empodat_matrix_biota emb            ON emb.id  = em.id
            LEFT JOIN empodat_matrix_water_waste emww     ON emww.id = em.id

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
