<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Index empodat_suspect_main.xlsx_station_mapping_id on both partitions.
 *
 * `fk_esm_xlsx_mapping` references empodat_suspect_xlsx_stations_mapping(id)
 * from empodat_suspect_main. Without an index on the referencing column,
 * PostgreSQL proves "no child row references this parent" by sequentially
 * scanning both partitions — once per deleted parent row. Measured on the
 * development database, 2026-09-05: deleting 58 mapping rows took 542 seconds.
 * With this index the same delete is instant.
 *
 * Created on the partitioned parent so PostgreSQL builds and attaches a child
 * index on each partition. CONCURRENTLY is not available on a partitioned
 * table, so this takes an ACCESS EXCLUSIVE lock for the duration of the build
 * over ~75M rows — run it in a maintenance window on production.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esm_xlsx_station_mapping_id ON empodat_suspect_main (xlsx_station_mapping_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_esm_xlsx_station_mapping_id');
    }
};
