<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration recreates the empodat_suspect_station_filters materialized view
     * using a permanent helper table (PostgreSQL doesn't allow temp tables in MV definitions).
     *
     * OPTIMIZATION:
     * - Helper table stores ~336 suspect stations
     * - MV joins only those stations with empodat_main (instead of full 98M rows)
     */
    public function up(): void
    {
        // Drop existing view and helper table
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_stations_helper CASCADE');

        // Step 1: Create a permanent helper table with suspect station data
        DB::statement('
            CREATE TABLE empodat_suspect_stations_helper AS
            SELECT DISTINCT
                esm.station_id,
                es.country_id,
                MAX(esm.ip_max) as max_ip_confidence,
                COUNT(esm.id) as suspect_measurement_count
            FROM empodat_suspect_main esm
            INNER JOIN empodat_stations es ON esm.station_id = es.id
            WHERE esm.station_id IS NOT NULL
            GROUP BY esm.station_id, es.country_id
        ');
        DB::statement('CREATE INDEX idx_essh_station_id ON empodat_suspect_stations_helper(station_id)');

        // Step 2: Create the materialized view by joining with empodat_main
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_station_filters AS
            SELECT DISTINCT
                ss.station_id,
                ss.country_id,
                em.matrix_id,
                em.sampling_date_year,
                ss.max_ip_confidence,
                ss.suspect_measurement_count,
                TRUE as has_suspect_data
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            WHERE em.matrix_id IS NOT NULL
        ');

        // Create indexes
        DB::statement('CREATE INDEX idx_essf_station_id ON empodat_suspect_station_filters(station_id)');
        DB::statement('CREATE INDEX idx_essf_country_id ON empodat_suspect_station_filters(country_id)');
        DB::statement('CREATE INDEX idx_essf_matrix_id ON empodat_suspect_station_filters(matrix_id)');
        DB::statement('CREATE INDEX idx_essf_year ON empodat_suspect_station_filters(sampling_date_year)');

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX idx_essf_unique_combo
            ON empodat_suspect_station_filters(
                station_id,
                COALESCE(country_id, 0),
                COALESCE(matrix_id, 0),
                COALESCE(sampling_date_year, 0)
            )
        ');

        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_station_filters IS
            'Optimized filter view for Empodat Suspect searches. Contains station/country/matrix/year combinations.
            Refresh: php artisan empodat-suspect:refresh-filters --create
            Helper table: empodat_suspect_stations_helper'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_stations_helper CASCADE');
    }
};
