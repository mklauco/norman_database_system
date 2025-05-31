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
        $matrices = [
            'Air',
            'Biota',
            'Sediments',
            'Sewage sludge',
            'Soil',
            'Suspended matter',
            'Water'
        ];

        // Create a table for each matrix
        foreach ($matrices as $matrix) {
            $tableName = 'empodat_matrix_' . strtolower(str_replace(' ', '_', $matrix));
            
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dct_analysis_id');
                $table->string('code');
                $table->json('meta_data');
                $table->timestamps();
                
                $table->index('dct_analysis_id');
                $table->index('code');
            });
        }
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $matrices = [
            'Air',
            'Biota',
            'Sediments',
            'Sewage sludge',
            'Soil',
            'Suspended matter',
            'Water'
        ];

        // Drop each matrix table
        foreach ($matrices as $matrix) {
            $tableName = 'empodat_matrix_' . strtolower(str_replace(' ', '_', $matrix));
            Schema::dropIfExists($tableName);
        }
    }
};