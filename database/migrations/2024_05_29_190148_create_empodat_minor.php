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

        Schema::create('empodat_minor', function (Blueprint $table) {
            $table->id();
            
            // Basic fields
            $table->tinyInteger('dpc_id')->unsigned()->default(0)->nullable();
            $table->string('altitude', 20)->nullable();
            $table->string('matrix_other')->default('')->nullable();
            $table->integer('compound')->unsigned()->nullable();
            $table->tinyInteger('dcod_id')->unsigned()->default(1)->nullable();
            $table->string('unit_extra', 20)->default('')->nullable();
            $table->tinyInteger('tier')->nullable();
            $table->tinyInteger('sampling_technique')->nullable();
            
            // Date and time fields
            $table->string('sampling_date')->nullable();
            $table->string('sampling_date_t')->nullable();
            $table->string('sampling_date1_y', 4)->nullable(); // year as string
            $table->string('sampling_date1_m')->nullable();
            $table->string('sampling_date1_d')->nullable();
            $table->string('sampling_date1_t')->nullable();
            $table->datetime('sampling_date1')->nullable();
            
            // Analysis date fields
            $table->string('analysis_date_y', 4)->nullable(); // year as string
            $table->tinyInteger('analysis_date_m')->nullable();
            $table->tinyInteger('analysis_date_d')->nullable();
            
            // Duration fields
            $table->string('sampling_duration_day', 20)->default('')->nullable();
            $table->string('sampling_duration_hour', 20)->default('')->nullable();
            
            // Text fields
            $table->text('description')->default('')->nullable();
            $table->text('remark')->default('')->nullable();
            $table->text('remark_add')->default('')->nullable();
            
            // Other fields
            $table->integer('show_date')->nullable();
            $table->integer('dtod_id')->unsigned()->nullable();
            $table->string('dtod_other')->default('')->nullable();
            $table->string('agg_uncertainty')->default('')->nullable();
            $table->integer('dmm_id')->unsigned()->nullable();
            $table->string('agg_max')->default('')->nullable();
            $table->string('agg_min')->default('')->nullable();
            $table->string('agg_number')->default('')->nullable();
            $table->string('agg_deviation')->default('')->nullable();
            $table->tinyInteger('dtl_id')->nullable();
            $table->string('dtl_other')->default('')->nullable();
            $table->tinyInteger('dst_id')->nullable();
            $table->string('dst_other')->default('')->nullable();
            $table->tinyInteger('dtos_id')->default(0)->nullable();
            $table->tinyInteger('dplu_id')->nullable();
            $table->tinyInteger('noexport')->unsigned()->default(0)->nullable();
            $table->integer('list_id')->nullable();

            $table->foreignId('added_by')->nullable()->default(null)->on('users')->onUpdate('cascade')->onDelete('restrict'); // User who added the record
            
            // $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('id')->references('id')->on('empodat_main')->onUpdate('cascade')->onDelete('restrict');
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('empodat_minor');
    }
};
