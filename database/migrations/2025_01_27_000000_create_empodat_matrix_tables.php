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
        $tables = [
            'empodat_matrix_air',
            'empodat_matrix_biota',
            'empodat_matrix_sediments',
            'empodat_matrix_sewage_sludge',
            'empodat_matrix_soil',
            'empodat_matrix_suspended_matter',
            'empodat_matrix_water',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('dct_analysis_id')->nullable(false);
                    $table->string('code', 255)->nullable(false);
                    $table->json('meta_data')->nullable(false);
                    $table->timestamps();

                    // Add index on dct_analysis_id
                    $table->index('dct_analysis_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'empodat_matrix_air',
            'empodat_matrix_biota',
            'empodat_matrix_sediments',
            'empodat_matrix_sewage_sludge',
            'empodat_matrix_soil',
            'empodat_matrix_suspended_matter',
            'empodat_matrix_water',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
