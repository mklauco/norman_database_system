<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop dependent materialized view if it exists
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters');

        // Check if foreign key constraint exists and drop it
        $foreignKeyExists = DB::select("
            SELECT constraint_name
            FROM information_schema.table_constraints
            WHERE table_name = 'empodat_main'
            AND constraint_name = 'empodat_main_dct_analysis_id_foreign'
            AND constraint_type = 'FOREIGN KEY'
        ");

        if (! empty($foreignKeyExists)) {
            DB::statement('ALTER TABLE empodat_main DROP CONSTRAINT empodat_main_dct_analysis_id_foreign');
        }

        Schema::table('empodat_main', function (Blueprint $table) {
            // Drop dct_analysis_id column if it exists
            if (Schema::hasColumn('empodat_main', 'dct_analysis_id')) {
                $table->dropColumn('dct_analysis_id');
            }

            // Add new columns
            $table->unsignedBigInteger('country_id')->nullable()->after('id');
            $table->unsignedBigInteger('file_id')->nullable()->after('country_id');

            $table->foreign('country_id')->references('id')->on('list_countries')->onDelete('set null');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_main', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['file_id']);
            $table->dropColumn(['country_id', 'file_id']);

            // Restore dct_analysis_id column only if it doesn't exist
            if (! Schema::hasColumn('empodat_main', 'dct_analysis_id')) {
                $table->unsignedBigInteger('dct_analysis_id')->nullable();
            }
        });
    }
};
