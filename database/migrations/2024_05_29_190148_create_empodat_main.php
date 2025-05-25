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

            $table->unsignedBigInteger('dct_analysis_id')->nullable()->index();

            $table->unsignedBigInteger('station_id')->nullable()->index();
            $table->unsignedBigInteger('matrix_id')->nullable()->index();
            $table->unsignedBigInteger('substance_id')->nullable()->index();
            $table->smallInteger('sampling_date_year')->nullable()->index();
            $table->unsignedBigInteger('concentration_indicator_id')->nullable()->index();
            $table->float('concentration_value')->nullable()->index();
            $table->unsignedBigInteger('method_id')->nullable()->index();
            $table->unsignedBigInteger('data_source_id')->nullable()->index();

            // $table->timestamps();

            // Foreign key constraints
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
