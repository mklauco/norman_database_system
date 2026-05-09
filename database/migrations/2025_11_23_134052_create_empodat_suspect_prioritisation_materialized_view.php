<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates a comprehensive materialized view for Empodat Suspect prioritisation.
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * PURPOSE
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * This view consolidates suspect screening data with matrix-specific metadata
     * for prioritisation analysis. It combines data from:
     * - empodat_suspect_main (suspect screening results)
     * - empodat_main (regular monitoring data)
     * - empodat_stations (geographic information)
     * - Matrix-specific tables (biota, water_waste, sediments, etc.)
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * APPROACH (Option 1: Single Comprehensive View)
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * - Uses LEFT JOINs to all matrix tables with CASE/COALESCE logic
     * - Matrix-specific fields are populated based on matrix_id ranges:
     *   • Water (general): 1-14
     *   • Sediments: 15-22
     *   • Suspended matter: 23-30
     *   • Sewage sludge: 31-34, 76
     *   • Soil: 35-38
     *   • Biota: 39-47
     *   • Air: 48-71, 77
     *   • Water waste: 72-74
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * PERFORMANCE CONSIDERATIONS
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * - Limited to first 100,000 records from empodat_suspect_main for testing
     * - Full implementation would handle 50M+ records
     * - Refresh strategy: CONCURRENT mode to avoid blocking reads
     * - Storage estimate: ~100MB for test dataset, ~10GB for full dataset
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * REFRESH COMMAND
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * php artisan empodat-suspect:refresh-prioritisation
     *
     * ═══════════════════════════════════════════════════════════════════════════
     */
    public function up(): void
    {
        // Drop if exists (for idempotency)
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');

        // Create the comprehensive materialized view
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                -- Limit to first 1,000,000 records for testing
                SELECT * FROM empodat_suspect_main
                WHERE empodat_suspect_main.id <= 1000000
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
            )
            SELECT
                -- Primary identifiers
                esm.id,
                em.id as empodat_main_id,

                -- Core fields from suspect data
                em.matrix_id,
                esm.concentration as concentration_value,
                esm.ip_max,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) as basin_name,

                -- df_id (from water_waste only)
                emww.df_id,

                -- Substance information
                ss.code as sus_id,

                -- Geographic and temporal information
                es.country as country,
                em.sampling_date_year as sampling_date_y,
                es.latitude as latitude_decimal,
                es.longitude as longitude_decimal,

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

            -- Join to MOST RECENT empodat_main record per station
            -- This prevents row explosion and reduces memory usage by 10-100x
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

            -- Water surface (matrix_id: specific IDs to be determined)
            LEFT JOIN empodat_matrix_water_surface emws
                ON em.id = emws.id
                AND em.matrix_id IN (2,3,4,5,6,7,8)

            -- Water ground (matrix_id: specific IDs to be determined)
            LEFT JOIN empodat_matrix_water_ground emwg
                ON em.id = emwg.id
                AND em.matrix_id = 1

            -- Note: Additional matrix tables can be added as needed:
            -- - empodat_matrix_sediments
            -- - empodat_matrix_soil
            -- - empodat_metadata_air
            -- - empodat_metadata_sewage
            -- - empodat_metadata_spm

            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL
        ');

        // Create indexes for query performance
        $this->createIndexes();

        // Add documentation comment
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_prioritisation IS
            'Comprehensive materialized view for Empodat Suspect prioritisation analysis.
            Combines suspect screening data with matrix-specific metadata.
            Limited to first 100,000 suspect records for testing.
            Refresh using: php artisan empodat-suspect:refresh-prioritisation
            Created: 2025-11-23. Option 1 implementation (single comprehensive view).'
        ");
    }

    /**
     * Create indexes on the materialized view
     */
    private function createIndexes(): void
    {
        // Minimal indexes for occasional queries
        DB::statement('CREATE INDEX idx_esp_matrix_id ON empodat_suspect_prioritisation(matrix_id)');
        DB::statement('CREATE INDEX idx_esp_sus_id ON empodat_suspect_prioritisation(sus_id)');

        // UNIQUE index for CONCURRENT refresh capability
        // Using a combination that should be unique for each record
        DB::statement('
            CREATE UNIQUE INDEX idx_esp_unique_combo
            ON empodat_suspect_prioritisation(
                id,
                empodat_main_id
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');
    }
};
