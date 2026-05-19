<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to the files table to optimize Empodat access control queries.
     * With 95M records in empodat_main and ~600 files, these indexes will improve
     * performance when filtering by file protection status.
     */
    public function up(): void
    {
        // Add single-column index on is_protected for WHERE clause filtering
        DB::statement('CREATE INDEX IF NOT EXISTS idx_files_is_protected ON files(is_protected)');

        // Add composite index for JOIN and WHERE operations
        // Useful when joining on files.id and filtering by is_protected
        DB::statement('CREATE INDEX IF NOT EXISTS idx_files_id_protected ON files(id, is_protected)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        DB::statement('DROP INDEX IF EXISTS idx_files_id_protected');
        DB::statement('DROP INDEX IF EXISTS idx_files_is_protected');
    }
};
