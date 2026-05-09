<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empodat_stations', function (Blueprint $table) {
            $table->boolean('is_deprecated')->default(false)->after('longitude');
            $table->index('is_deprecated');
        });

        // Update existing deprecated stations
        DB::statement('
            UPDATE empodat_stations
            SET is_deprecated = true
            WHERE id IN (
                SELECT DISTINCT deprecated_station_id
                FROM empodat_station_merge_log
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_stations', function (Blueprint $table) {
            $table->dropIndex(['is_deprecated']);
            $table->dropColumn('is_deprecated');
        });
    }
};
