<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Add a composite (file_id, id) index so queries of the shape
        //   WHERE file_id = X ORDER BY id LIMIT N
        // can scan within a single file_id in id order, instead of using
        // empodat_main_pkey (id) and filtering by file_id — which costs a
        // near-full table scan when the targeted file_id is concentrated
        // outside the leading id range (44s+ on 100M rows; observed during
        // /empodat/search/search with super_admin + fileSearch[]=636).
        //
        // The pre-existing single-column index `empodat_main_file_id_index`
        // is redundant once this lands (the new composite supports lookups
        // on `file_id` alone via the leading column). Drop it to save disk
        // and avoid the planner picking the worse one.
        //
        // CONCURRENTLY: avoid taking AccessExclusiveLock on a 100M-row
        // table while the dev server still serves requests.
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS empodat_main_file_id_id_idx ON empodat_main (file_id, id)');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS empodat_main_file_id_index');
    }

    public function down(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS empodat_main_file_id_index ON empodat_main (file_id)');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS empodat_main_file_id_id_idx');
    }
};
