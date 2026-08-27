<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce one provenance row per EMPODAT Suspect source file.
 *
 * `empodat_suspect_data_source` previously had only a non-unique index on
 * `file_id`, so nothing at the database level stopped a second (or
 * duplicate) provenance row for the same file. That made it possible for
 * EmpodatSuspectDataSourceSeeder — which bulk-inserts a row per file with no
 * deduplication — to be re-run and silently double up every existing row.
 *
 * Replacing the plain index with a UNIQUE index makes that class of bug
 * impossible: any insert or update that would create a second row for the
 * same `file_id` is rejected by Postgres.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empodat_suspect_data_source', function (Blueprint $table): void {
            $table->dropIndex('empodat_suspect_data_source_file_id_index');
            $table->unique('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_suspect_data_source', function (Blueprint $table): void {
            $table->dropUnique('empodat_suspect_data_source_file_id_unique');
            $table->index('file_id');
        });
    }
};
