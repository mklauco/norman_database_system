<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds optimized indexes for the Empodat Suspect search functionality.
     * These indexes support the hybrid search strategy using a small materialized view
     * for filter metadata combined with direct queries on the main tables.
     *
     * Context:
     * - empodat_suspect_main table contains up to 100M records (currently 4M)
     * - ~1,000 stations have suspect data
     * - Each station has 10-100 measurements per substance/condition
     * - Queries filter by country, matrix, substance, year, and other criteria
     *
     * Strategy:
     * 1. Add index on empodat_stations.country_id for geography filtering
     * 2. Add compound index on empodat_suspect_main(station_id, substance_id)
     *    for optimal query performance when filtering by both
     */
    public function up(): void
    {
        // Index 1: empodat_stations.country_id
        // Purpose: Speed up country-based filtering (one of the most common filter criteria)
        // Note: Foreign keys in PostgreSQL don't automatically create indexes on the referencing column
        if (! $this->indexExists('empodat_stations', 'empodat_stations_country_id_index')) {
            Schema::table('empodat_stations', function (Blueprint $table) {
                $table->index('country_id', 'empodat_stations_country_id_index');
            });
        }

        // Index 2: empodat_suspect_main(station_id, substance_id)
        // Purpose: Compound index for queries that filter by both station and substance
        // This is critical because:
        // - Station filtering happens first (narrowing ~1k stations)
        // - Then substance filtering within those stations
        // - PostgreSQL can use this for index-only scans in many cases
        if (! $this->indexExists('empodat_suspect_main', 'empodat_suspect_main_station_substance_index')) {
            Schema::table('empodat_suspect_main', function (Blueprint $table) {
                $table->index(['station_id', 'substance_id'], 'empodat_suspect_main_station_substance_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops the indexes created in the up() method.
     * Safe to run multiple times due to IF EXISTS checks.
     */
    public function down(): void
    {
        Schema::table('empodat_stations', function (Blueprint $table) {
            $table->dropIndex('empodat_stations_country_id_index');
        });

        Schema::table('empodat_suspect_main', function (Blueprint $table) {
            $table->dropIndex('empodat_suspect_main_station_substance_index');
        });
    }

    /**
     * Check if an index exists on a table
     *
     * @param  string  $table  The table name
     * @param  string  $index  The index name
     * @return bool True if index exists, false otherwise
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaName = $connection->getConfig('schema') ?: 'public';

        $result = DB::select(
            'SELECT EXISTS (
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = ?
                AND tablename = ?
                AND indexname = ?
            ) as exists',
            [$schemaName, $table, $index]
        );

        return $result[0]->exists ?? false;
    }
};
