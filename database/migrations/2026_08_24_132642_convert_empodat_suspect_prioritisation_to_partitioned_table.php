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
     * Replaces the empodat_suspect_prioritisation materialized view with a
     * regular table, LIST partitioned by file_id. A materialized view is
     * self-evidently a snapshot; a table is not, so partition freshness is
     * now tracked explicitly in empodat_suspect_prioritisation_builds.
     */
    public function up(): void
    {
        // Drop the existing materialized view
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');

        // Create the partitioned table
        DB::statement('
            CREATE TABLE empodat_suspect_prioritisation (
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
            COMMENT ON TABLE empodat_suspect_prioritisation IS
            'Empodat Suspect prioritisation data, LIST partitioned by file_id.
            Combines suspect screening data with matrix-specific metadata.
            Replaces the former materialized view of the same name; unlike a
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
                CREATE TABLE empodat_suspect_prioritisation_{$fileId}
                    PARTITION OF empodat_suspect_prioritisation
                    FOR VALUES IN ({$fileId})
            ");
        }

        DB::statement('
            CREATE TABLE empodat_suspect_prioritisation_default
                PARTITION OF empodat_suspect_prioritisation
                DEFAULT
        ');
    }

    /**
     * Create all required indexes on the parent table so PostgreSQL
     * propagates them to every existing (and future) partition.
     */
    private function createIndexes(): void
    {
        $indexes = [
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
            'idx_esp_station_name' => 'station_name',
            'idx_esp_file_id' => 'file_id',
        ];

        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX {$indexName} ON empodat_suspect_prioritisation({$column})");
        }

        // Partial indexes for matrix-specific (sparse) fields
        $partialIndexes = [
            'idx_esp_basin_name' => 'basin_name',
            'idx_esp_df_id' => 'df_id',
            'idx_esp_dsa_id' => 'dsa_id',
            'idx_esp_dsgr_id' => 'dsgr_id',
            'idx_esp_dtiel_id' => 'dtiel_id',
            'idx_esp_dmeas_id' => 'dmeas_id',
            'idx_esp_effluent_influent_id' => 'effluent_influent_id',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
        }

        // Compound indexes
        DB::statement('CREATE INDEX idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix, sampling_date_y)');
        DB::statement('CREATE INDEX idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix)');
        DB::statement('CREATE INDEX idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');
        DB::statement('CREATE INDEX idx_esp_matrix_substance ON empodat_suspect_prioritisation(matrix, sus_id)');

        // Unique index must include the partition key (file_id)
        DB::statement('
            CREATE UNIQUE INDEX idx_esp_unique_id
            ON empodat_suspect_prioritisation(id, file_id)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Drops the partitioned table and recreates the materialized view
     * exactly as it stood immediately before this migration (including
     * max_ip_max), so a rollback restores the prior behaviour verbatim.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_prioritisation CASCADE');

        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                SELECT * FROM empodat_suspect_main
            ),
            most_recent_main AS (
                -- Get most recent empodat_main record per station
                -- This prevents Cartesian product and massive memory usage
                SELECT DISTINCT ON (station_id)
                    id,
                    station_id,
                    matrix_id,
                    sampling_date_year
                FROM empodat_main
                WHERE station_id IN (SELECT DISTINCT station_id FROM limited_suspect)
                ORDER BY station_id, sampling_date_year DESC NULLS LAST
            ),
            -- Calculate max_ip_max: maximum ip_max for each matrix + substance combination
            max_ip_by_matrix_substance AS (
                SELECT
                    em.matrix_id,
                    esm.substance_id,
                    MAX(esm.ip_max) AS max_ip_max
                FROM limited_suspect esm
                INNER JOIN most_recent_main em ON em.station_id = esm.station_id
                WHERE esm.ip_max IS NOT NULL
                GROUP BY em.matrix_id, esm.substance_id
            )
            SELECT
                -- Primary identifiers
                esm.id,

                -- Core fields from suspect data
                em.matrix_id AS matrix,
                esm.concentration AS concentration_value,
                esm.ip_max,
                mims.max_ip_max,

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
                emb.dmeas_id

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

            -- Join to pre-calculated max_ip_max
            LEFT JOIN max_ip_by_matrix_substance mims
                ON em.matrix_id = mims.matrix_id
                AND esm.substance_id = mims.substance_id

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
        ');

        $this->createLegacyIndexes();

        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_prioritisation IS
            'Comprehensive materialized view for Empodat Suspect prioritisation analysis.
            Combines suspect screening data with matrix-specific metadata.
            Includes max_ip_max (max ip_max per matrix+substance).
            Refresh command: php artisan empodat-suspect:refresh-prioritisation'
        ");
    }

    /**
     * Recreate the indexes exactly as they existed on the materialized view
     * before this migration, for a faithful rollback.
     */
    private function createLegacyIndexes(): void
    {
        $indexes = [
            'idx_esp_id' => 'id',
            'idx_esp_matrix' => 'matrix',
            'idx_esp_country' => 'country',
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
            'idx_esp_station_name' => 'station_name',
            'idx_esp_max_ip_max' => 'max_ip_max',
        ];

        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column})");
        }

        $partialIndexes = [
            'idx_esp_basin_name' => 'basin_name',
            'idx_esp_df_id' => 'df_id',
            'idx_esp_dsa_id' => 'dsa_id',
            'idx_esp_dsgr_id' => 'dsgr_id',
            'idx_esp_dtiel_id' => 'dtiel_id',
            'idx_esp_dmeas_id' => 'dmeas_id',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix, sampling_date_y)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_substance ON empodat_suspect_prioritisation(matrix, sus_id)');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_esp_unique_id
            ON empodat_suspect_prioritisation(id)
        ');
    }
};
