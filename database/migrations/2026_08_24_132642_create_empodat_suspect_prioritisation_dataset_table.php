<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * files.id values that currently have empodat_suspect_main rows.
     * One LIST partition is created per value, plus a DEFAULT partition
     * as a safety net for any future file_id not yet listed here.
     *
     * @var list<int>
     */
    private const PARTITION_FILE_IDS = [
        10001, 10002, 10003, 10004, 10005, 10006, 10007, 10008,
        10009, 10010, 10011, 10012, 10013, 10014, 10015,
    ];

    /**
     * Run the migrations.
     *
     * Creates empodat_suspect_prioritisation_dataset, a regular table LIST
     * partitioned by file_id, ALONGSIDE the existing
     * empodat_suspect_prioritisation materialized view — it does not replace
     * or drop it. The two live side by side so this table can be built and
     * verified before any cutover. A materialized view is self-evidently a
     * snapshot; a table is not, so partition freshness is tracked explicitly
     * in empodat_suspect_prioritisation_builds.
     */
    public function up(): void
    {
        // Create the partitioned table
        DB::statement('
            CREATE TABLE empodat_suspect_prioritisation_dataset (
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
            ) PARTITION BY LIST (file_id)
        ');

        $this->createPartitions();
        $this->createIndexes();

        DB::statement("
            COMMENT ON TABLE empodat_suspect_prioritisation_dataset IS
            'Empodat Suspect prioritisation data, LIST partitioned by file_id.
            Combines suspect screening data with matrix-specific metadata.
            Built alongside, and intended to eventually replace, the
            empodat_suspect_prioritisation materialized view. Unlike a
            materialized view this table does not self-evidently expose its own
            staleness, so freshness per partition is tracked in
            empodat_suspect_prioritisation_builds.
            Rebuild command: php artisan empodat-suspect:refresh-prioritisation'
        ");
    }

    /**
     * Create one LIST partition per known files.id, plus a DEFAULT partition
     * as a safety net for any file_id not covered above.
     */
    private function createPartitions(): void
    {
        foreach (self::PARTITION_FILE_IDS as $fileId) {
            DB::statement("
                CREATE TABLE empodat_suspect_prioritisation_dataset_{$fileId}
                    PARTITION OF empodat_suspect_prioritisation_dataset
                    FOR VALUES IN ({$fileId})
            ");
        }

        DB::statement('
            CREATE TABLE empodat_suspect_prioritisation_dataset_default
                PARTITION OF empodat_suspect_prioritisation_dataset
                DEFAULT
        ');
    }

    /**
     * Create all required indexes on the parent table so PostgreSQL
     * propagates them to every existing (and future) partition.
     *
     * Named with the idx_espd_ prefix (not idx_esp_) because the live
     * empodat_suspect_prioritisation materialized view still exists and
     * already owns the idx_esp_ names — reusing them here would collide.
     */
    private function createIndexes(): void
    {
        $indexes = [
            'idx_espd_year' => 'sampling_date_y',
            'idx_espd_sus_id' => 'sus_id',
            'idx_espd_ip_max' => 'ip_max',
            'idx_espd_station_name' => 'station_name',
            'idx_espd_file_id' => 'file_id',
        ];

        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX {$indexName} ON empodat_suspect_prioritisation_dataset({$column})");
        }

        // Partial indexes for matrix-specific (sparse) fields
        $partialIndexes = [
            'idx_espd_basin_name' => 'basin_name',
            'idx_espd_df_id' => 'df_id',
            'idx_espd_dsa_id' => 'dsa_id',
            'idx_espd_dsgr_id' => 'dsgr_id',
            'idx_espd_dtiel_id' => 'dtiel_id',
            'idx_espd_dmeas_id' => 'dmeas_id',
            'idx_espd_effluent_influent_id' => 'effluent_influent_id',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX {$indexName} ON empodat_suspect_prioritisation_dataset({$column}) WHERE {$column} IS NOT NULL");
        }

        // Compound indexes
        DB::statement('CREATE INDEX idx_espd_matrix_year ON empodat_suspect_prioritisation_dataset(matrix, sampling_date_y)');
        DB::statement('CREATE INDEX idx_espd_country_matrix ON empodat_suspect_prioritisation_dataset(country, matrix)');
        DB::statement('CREATE INDEX idx_espd_lat_lon ON empodat_suspect_prioritisation_dataset(latitude_decimal, longitude_decimal)');
        DB::statement('CREATE INDEX idx_espd_matrix_substance ON empodat_suspect_prioritisation_dataset(matrix, sus_id)');

        // Unique index must include the partition key (file_id)
        DB::statement('
            CREATE UNIQUE INDEX idx_espd_unique_id
            ON empodat_suspect_prioritisation_dataset(id, file_id)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Drops the partitioned table. up() no longer drops or replaces the
     * empodat_suspect_prioritisation materialized view, so down() must not
     * recreate it either — that view was never touched by this migration.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_prioritisation_dataset CASCADE');

        // Local-only cleanup, safe on production: an earlier version of this
        // migration (before the side-by-side rework) dropped the materialized
        // view and created a TABLE named empodat_suspect_prioritisation in its
        // place. On any developer database that already ran that version, the
        // view is gone and a table by that name exists instead. DROP TABLE
        // cannot remove a materialized view, so on production — where the view
        // still exists and no such table does — this statement is a no-op. On
        // an affected local database it removes the stale table left over from
        // the old migration, restoring a clean slate.
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_prioritisation CASCADE');
    }
};
