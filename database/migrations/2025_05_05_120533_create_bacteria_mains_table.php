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
        Schema::create('arbg_bacteria_main', function (Blueprint $table) {
            $table->id();
            
            // Analysis fields
            $table->unsignedTinyInteger('sample_matrix_id')->default(0)->comment('Sample matrix');
            $table->string('sample_matrix_other')->nullable()->comment('Sample matrix - other');
            $table->unsignedTinyInteger('bacterial_group_id')->default(0)->comment('Bacterial group');
            $table->string('bacterial_group_other')->nullable()->comment('Bacterial group - other');
            $table->unsignedTinyInteger('concentration_data_id')->nullable();
            $table->string('ar_phenotype')->nullable()->comment('AR Phenotype');
            $table->string('ar_phenotype_class')->nullable()->comment('AR Phenotype - class');
            $table->string('abundance')->nullable()->comment('Abundance (CFUs/mL)');
            $table->string('value')->nullable()->comment('Value');
            
            // Sampling date fields
            $table->unsignedTinyInteger('sampling_date_day')->default(0)->nullable()->comment('Sampling date Day');
            $table->unsignedTinyInteger('sampling_date_month')->default(0)->nullable()->comment('Sampling date Month');
            $table->year('sampling_date_year')->default(0)->nullable()->comment('Sampling date Year');
            $table->string('sampling_date_hour')->nullable()->comment('Sampling date Hour');
            $table->string('sampling_date_minute')->nullable()->comment('Sampling date Minute');
            
            // Soil sampling fields
            $table->string('name_of_the_wider_area_of_sampling')->nullable()->comment('Soil: Name of the wider area of sampling (e.g. town/city, region etc.)');
            $table->string('river_basin_name')->nullable()->comment('Soil: River basin name');
            $table->tinyInteger('type_of_depth_sampling_id')->default(0)->comment('Soil: Sampling depth - Type of depth sampling');
            $table->string('depth')->nullable()->comment('Soil: Depth [m]');
            $table->unsignedTinyInteger('soil_type_id')->nullable()->default(0)->comment('Soil: Soil type');
            $table->unsignedTinyInteger('soil_texture_id')->nullable()->default(0)->comment('Soil: Soil texture');
            $table->string('concentration_normalised')->nullable()->comment('Soil: Concentration normalised for the particle size');
            $table->unsignedTinyInteger('grain_size_distribution_id')->nullable()->default(0)->comment('Soil: Grain size distribution');
            $table->string('grain_size_distribution_other')->nullable()->comment('Soil: Grain size distribution - other');
            $table->string('dry_wet_ratio')->nullable()->comment('Soil: Dry Wet Ratio [weight %]');
            $table->string('ph')->nullable()->comment('Soil: pH');
            $table->string('total_organic_carbon')->nullable()->comment('Soil: Total organic carbon [mg/kg] or [% of total dry weight]');
            
            // Reference fields
            $table->unsignedInteger('method_id')->comment('Serial number in Analytical method worksheet');
            $table->unsignedInteger('source_id')->comment('Serial number in Data source worksheet');
            $table->unsignedInteger('coordinate_id')->comment('Data from Analysis XLSX sheet');
            $table->string('remark')->nullable()->comment('REMARK');
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('sample_matrix_id')->references('id')->on('arbg_data_sample_matrix');
            $table->foreign('bacterial_group_id')->references('id')->on('arbg_data_bacterial_group');
            $table->foreign('concentration_data_id')->references('id')->on('arbg_data_concentration_data');
            // $table->foreign('grain_size_distribution_id')->references('id')->on('arbg_data_grain_size_distribution');
            // $table->foreign('soil_texture_id')->references('id')->on('arbg_data_soil_texture');
            // $table->foreign('soil_type_id')->references('id')->on('arbg_data_soil_type');
            // $table->foreign('type_of_depth_sampling_id')->references('id')->on('arbg_data_type_of_depth_sampling');
            // $table->foreign('method_id')->references('id')->on('arbg_data_methods');
            // $table->foreign('source_id')->references('id')->on('arbg_data_sources');
            
            // Indexes
            $table->index('sample_matrix_id');
            $table->index('bacterial_group_id');
            $table->index('concentration_data_id');
            $table->index('coordinate_id');
            $table->index('grain_size_distribution_id');
            $table->index('method_id');
            $table->index('soil_texture_id');
            $table->index('soil_type_id');
            $table->index('source_id');
            $table->index('type_of_depth_sampling_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_bacteria_main');
    }
};