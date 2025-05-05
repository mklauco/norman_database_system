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
        Schema::create('arbg_gene_main', function (Blueprint $table) {
            $table->id();
            
            // Sample matrix fields
            $table->unsignedTinyInteger('sample_matrix_id')->default(0);
            $table->string('sample_matrix_other')->nullable();
            
            // Gene information fields
            $table->string('gene_name')->nullable();
            $table->string('gene_description')->nullable();
            $table->string('gene_family')->nullable();
            $table->string('associated_phenotype')->nullable();
            $table->string('monogenic_phenotype')->nullable();
            
            // Primer and probe fields
            $table->text('forward_primer')->nullable()->comment('Forward Primer');
            $table->text('reverse_primer')->nullable()->comment('Reverse Primer');
            $table->text('dye_probe_based')->nullable()->comment('Dye-Based or Probe-Based');
            $table->text('probe_sequence')->nullable()->comment('Probe Sequence (for Probe-Based Analysis)');
            $table->text('plasmid_genome_standards')->nullable()->comment('Plasmid Standards or Genome Standards');
            
            // Phenotype and marker fields
            $table->string('multi_drug_resistance_phenotype')->nullable();
            $table->string('genetic_marker')->nullable();
            $table->string('genetic_marker_specify')->nullable();
            $table->string('common_bacterial_host')->nullable();
            
            // Concentration fields
            $table->unsignedTinyInteger('concentration_data_id')->nullable()->default(0);
            $table->unsignedTinyInteger('concentration_id')->nullable()->default(0);
            $table->string('concentration_abundance_per_ml')->nullable();
            $table->string('concentration_abundance_per_ng')->nullable();
            $table->string('concentration_abundance')->nullable();
            $table->string('prevalence')->nullable();
            
            // Sampling date fields
            $table->unsignedTinyInteger('sampling_date_day')->nullable()->default(0);
            $table->unsignedTinyInteger('sampling_date_month')->nullable()->default(0);
            $table->year('sampling_date_year')->nullable()->default(0);
            $table->string('sampling_date_hour')->nullable();
            $table->string('sampling_date_minute')->nullable();
            
            // Reference fields
            $table->unsignedInteger('method_id')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->unsignedInteger('coordinate_id')->nullable();
            $table->string('remark')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            // $table->foreign('sample_matrix_id')->references('id')->on('arbg_data_sample_matrix');
            // $table->foreign('concentration_data_id')->references('id')->on('arbg_data_concentration_data');
            // $table->foreign('coordinate_id')->references('id')->on('arbg_data_coordinates');
            // $table->foreign('method_id')->references('id')->on('arbg_data_methods');
            // $table->foreign('source_id')->references('id')->on('arbg_data_sources');
            
            // Indexes
            $table->index('sample_matrix_id');
            $table->index('concentration_data_id');
            $table->index('coordinate_id');
            $table->index('method_id');
            $table->index('source_id');
            $table->index('gene_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_gene_main');
    }
};