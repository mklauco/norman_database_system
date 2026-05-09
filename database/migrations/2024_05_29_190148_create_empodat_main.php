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
        Schema::create('empodat_main', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('dct_analysis_id')->nullable();

            $table->unsignedBigInteger('station_id')->nullable();
            $table->unsignedBigInteger('matrix_id')->nullable();
            $table->unsignedBigInteger('substance_id')->nullable();
            $table->smallInteger('sampling_date_year')->nullable();
            $table->unsignedBigInteger('concentration_indicator_id')->nullable();
            $table->float('concentration_value')->nullable()->default(null);
            $table->unsignedBigInteger('method_id')->nullable();
            $table->unsignedBigInteger('data_source_id')->nullable();

            $table->timestamps();

            // Indexes for PostgreSQL optimization
            $table->index('dct_analysis_id');
            $table->index('station_id');
            $table->index('matrix_id');
            $table->index('substance_id');
            $table->index('sampling_date_year');
            $table->index('concentration_indicator_id');
            $table->index('method_id');
            $table->index('data_source_id');

            // Composite indexes for common search patterns
            // $table->index(['station_id', 'sampling_date_year']);
            // $table->index(['substance_id', 'matrix_id']);
            // $table->index(['sampling_date_year', 'substance_id']);
            // $table->index(['matrix_id', 'sampling_date_year']);

            // Partial index for non-null concentration values (PostgreSQL specific)
            // $table->index('concentration_value')->where('concentration_value IS NOT NULL');

            // Foreign key constraints for data consistency
            $table->foreign('station_id')
                ->references('id')
                ->on('empodat_stations')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('matrix_id')
                ->references('id')
                ->on('list_matrices')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('concentration_indicator_id')
                ->references('id')
                ->on('list_concentration_indicators')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('method_id')
                ->references('id')
                ->on('empodat_analytical_methods')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('data_source_id')
                ->references('id')
                ->on('empodat_data_sources')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_main');
    }
};
