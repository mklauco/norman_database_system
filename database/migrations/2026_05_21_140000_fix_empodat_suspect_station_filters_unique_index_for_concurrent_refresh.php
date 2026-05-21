<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replaces the expression-based unique index on `empodat_suspect_station_filters`
     * with a plain-column unique index so that REFRESH MATERIALIZED VIEW CONCURRENTLY
     * works.
     *
     * Background: the previous index used COALESCE(...) wrappers to deal with
     * potentially-NULL columns:
     *
     *   CREATE UNIQUE INDEX idx_essf_unique_combo
     *     ON empodat_suspect_station_filters
     *     USING btree (
     *       station_id,
     *       COALESCE(country_id, 0::bigint),
     *       COALESCE(matrix_id, 0::bigint),
     *       COALESCE(sampling_date_year::integer, 0)
     *     );
     *
     * PostgreSQL classifies that as an "expression index" and refuses to use it
     * for CONCURRENT refresh:
     *
     *   ERROR:  cannot refresh materialized view ... concurrently
     *   HINT:   Create a unique index with no WHERE clause on one or more columns
     *           of the materialized view.
     *
     * Verified before this migration: 0 NULLs in any of (station_id, country_id,
     * matrix_id, sampling_date_year) and 0 duplicate combos, so a plain-column
     * unique index works for the actual data.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_essf_unique_combo');

        DB::statement('
            CREATE UNIQUE INDEX idx_essf_unique_combo
                ON empodat_suspect_station_filters
                USING btree (station_id, country_id, matrix_id, sampling_date_year)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Restores the original COALESCE-based unique index. Note: doing this also
     * re-introduces the CONCURRENT-refresh limitation.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_essf_unique_combo');

        DB::statement('
            CREATE UNIQUE INDEX idx_essf_unique_combo
                ON empodat_suspect_station_filters
                USING btree (
                    station_id,
                    COALESCE(country_id, 0::bigint),
                    COALESCE(matrix_id, 0::bigint),
                    COALESCE(sampling_date_year::integer, 0)
                )
        ');
    }
};
