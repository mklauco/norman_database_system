<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates a SMALL materialized view for Empodat Suspect search filtering.
     *
     * IMPORTANT: This is NOT a full data view - it only contains filter metadata.
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * WHY A MATERIALIZED VIEW?
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * Problem:
     * - empodat_suspect_main will contain up to 100M records (currently 4M)
     * - Users need to filter by country, matrix, substance, year, etc.
     * - These filters require joining 3 tables: empodat_suspect_main → empodat_stations → empodat_main
     * - Direct joins on 100M records are slow, even with indexes
     *
     * Solution:
     * - Pre-compute which stations are associated with which filter values
     * - Store only DISTINCT combinations (station_id + filter fields)
     * - Result: ~100k rows instead of 100M rows
     * - Storage: ~10-20MB instead of ~40GB
     * - Refresh time: <1 minute instead of 1-2 hours
     *
     * Strategy:
     * 1. User applies filters (country, matrix, substance, year, etc.)
     * 2. Query this small MV to find matching station_ids (FAST)
     * 3. Query empodat_suspect_main for those station_ids only (FAST with index)
     * 4. Display results with pagination
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * DATA FRESHNESS
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * - Data updates: Once every 2-3 months
     * - Refresh method: Manual after data import or scheduled job
     * - Refresh command: REFRESH MATERIALIZED VIEW empodat_suspect_station_filters
     * - Refresh command (concurrent): REFRESH MATERIALIZED VIEW CONCURRENTLY empodat_suspect_station_filters
     *   (requires unique index, allows reads during refresh)
     *
     * ═══════════════════════════════════════════════════════════════════════════
     * VIEW STRUCTURE
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * The view contains DISTINCT combinations of:
     * - station_id: Links back to empodat_suspect_main
     * - country_id: For geography filtering
     * - matrix_id: For ecosystem filtering
     * - substance_id: For substance filtering
     * - sampling_date_year: For year range filtering
     * - concentration_indicator_id: For concentration type filtering
     * - data_source_id: For data source filtering
     * - method_id: For analytical method filtering
     *
     * Additional fields for convenience:
     * - dct_analysis_id: Links to empodat_main
     * - ip_max: Maximum identification confidence from suspect data
     * - has_suspect_data: Flag indicating station has suspect measurements
     *
     * ═══════════════════════════════════════════════════════════════════════════
     */
    public function up(): void
    {
        // Drop if exists (for idempotency)
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');

        // Create the materialized view
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_station_filters AS
            SELECT DISTINCT
                -- Primary key: station_id is the core linking field
                esm.station_id,

                -- Filter fields from empodat_stations (via join)
                es.country_id,

                -- Filter fields from empodat_main (via station link)
                em.matrix_id,
                em.substance_id,
                em.sampling_date_year,
                em.concentration_indicator_id,
                em.data_source_id,
                em.method_id,
                em.dct_analysis_id,

                -- Metadata for convenience
                MAX(esm.ip_max) as max_ip_confidence,
                COUNT(DISTINCT esm.id) as suspect_measurement_count,
                TRUE as has_suspect_data

            FROM empodat_suspect_main esm

            -- Join to stations to get country_id
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to empodat_main to get all filter fields
            -- This links via station_id since empodat_main also references stations
            INNER JOIN empodat_main em
                ON em.station_id = es.id

            -- Only include stations that have actual suspect measurements
            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL

            -- Group by all filter fields to get DISTINCT combinations
            GROUP BY
                esm.station_id,
                es.country_id,
                em.matrix_id,
                em.substance_id,
                em.sampling_date_year,
                em.concentration_indicator_id,
                em.data_source_id,
                em.method_id,
                em.dct_analysis_id
        ');

        // Create indexes on filter fields for fast lookups
        // These indexes make the MV queries blazing fast

        // Index 1: station_id (most important - used in final join)
        DB::statement('CREATE INDEX idx_essf_station_id ON empodat_suspect_station_filters(station_id)');

        // Index 2: country_id (geography filtering)
        DB::statement('CREATE INDEX idx_essf_country_id ON empodat_suspect_station_filters(country_id)');

        // Index 3: matrix_id (ecosystem filtering)
        DB::statement('CREATE INDEX idx_essf_matrix_id ON empodat_suspect_station_filters(matrix_id)');

        // Index 4: substance_id (substance filtering)
        DB::statement('CREATE INDEX idx_essf_substance_id ON empodat_suspect_station_filters(substance_id)');

        // Index 5: sampling_date_year (year range filtering)
        DB::statement('CREATE INDEX idx_essf_year ON empodat_suspect_station_filters(sampling_date_year)');

        // Index 6: concentration_indicator_id (concentration type filtering)
        DB::statement('CREATE INDEX idx_essf_conc_indicator ON empodat_suspect_station_filters(concentration_indicator_id)');

        // Index 7: data_source_id (data source filtering)
        DB::statement('CREATE INDEX idx_essf_data_source ON empodat_suspect_station_filters(data_source_id)');

        // Index 8: method_id (analytical method filtering)
        DB::statement('CREATE INDEX idx_essf_method ON empodat_suspect_station_filters(method_id)');

        // Index 9: Compound index for station + substance (common query pattern)
        DB::statement('CREATE INDEX idx_essf_station_substance ON empodat_suspect_station_filters(station_id, substance_id)');

        // UNIQUE Index 10: Required for REFRESH MATERIALIZED VIEW CONCURRENTLY
        // This allows the view to be refreshed without blocking read queries
        // Composite unique constraint on the main grouping fields
        DB::statement('
            CREATE UNIQUE INDEX idx_essf_unique_combo
            ON empodat_suspect_station_filters(
                station_id,
                country_id,
                matrix_id,
                substance_id,
                sampling_date_year,
                dct_analysis_id
            )
        ');

        // Add comment to the materialized view for documentation
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_station_filters IS
            'Lightweight materialized view containing filter metadata for Empodat Suspect searches.
            Contains ~100k rows representing DISTINCT station/filter combinations.
            Refresh after data imports using: REFRESH MATERIALIZED VIEW CONCURRENTLY empodat_suspect_station_filters;
            Created: 2025-10-28. Update frequency: Every 2-3 months.'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Drops the materialized view and all its indexes.
     * CASCADE option ensures dependent objects are also dropped.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');
    }
};
