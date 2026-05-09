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
     * This migration:
     * 1. Migrates existing pivot data to the direct file_id column on literature_temp_main
     * 2. Drops the pivot table file_literature_temp_main
     */
    public function up(): void
    {
        // First, migrate data from pivot table to direct file_id column
        // Only update records where file_id is currently NULL
        if (Schema::hasTable('file_literature_temp_main')) {
            DB::statement('
                UPDATE literature_temp_main
                SET file_id = pivot.file_id
                FROM file_literature_temp_main pivot
                WHERE literature_temp_main.id = pivot.literature_temp_main_id
                  AND literature_temp_main.file_id IS NULL
            ');

            // Drop the pivot table
            Schema::dropIfExists('file_literature_temp_main');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the pivot table
        Schema::create('file_literature_temp_main', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('literature_temp_main_id')->constrained('literature_temp_main')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['file_id', 'literature_temp_main_id'], 'file_literature_unique');
        });

        // Migrate data back from direct column to pivot table
        DB::statement('
            INSERT INTO file_literature_temp_main (file_id, literature_temp_main_id, created_at, updated_at)
            SELECT file_id, id, NOW(), NOW()
            FROM literature_temp_main
            WHERE file_id IS NOT NULL
        ');
    }
};
