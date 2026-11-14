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
        Schema::create('hazards_comptox_substance_data', function (Blueprint $table) {
            $table->id();

            // Provenance / run linkage.
            $table->unsignedBigInteger('parse_run_id')->nullable()->index();
            $table->unsignedBigInteger('comptox_payload_id')->nullable()->index();
            $table->string('source_record_type', 32)->nullable(); // fate|property|detail
            $table->unsignedBigInteger('source_record_id')->nullable();

            // Explicit domain classification for later display/filtering.
            $table->string('data_domain', 32)->index(); // physchem|fate_transport

            // Core columns aligned with ARB_ARG pbmt_phys_chem.
            $table->string('data_source')->nullable();
            $table->unsignedBigInteger('editor')->nullable();
            $table->string('date')->nullable();
            $table->string('reference_type')->nullable();
            $table->text('title')->nullable();
            $table->string('authors')->nullable();
            $table->string('year')->nullable();
            $table->text('bibliographic_source')->nullable();
            $table->string('physico_chemical_source_doi')->nullable();
            $table->string('test_type')->nullable();
            $table->boolean('performed_under_glp')->nullable();
            $table->boolean('standard_test')->default(false);

            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();
            $table->string('dtxid', 64)->nullable()->index();
            $table->string('substance_name')->nullable();
            $table->string('cas_no')->nullable();
            $table->string('inchikey')->nullable();
            $table->text('smiles')->nullable();

            $table->boolean('radio_labeled_substance')->nullable();
            $table->string('standard_qualifier')->nullable();
            $table->string('standard_used')->nullable();
            $table->string('test_matrix')->nullable();
            $table->string('test_species')->nullable();

            $table->double('duration_days')->nullable();
            $table->double('exposure_concentration')->nullable();
            $table->double('ph')->nullable();
            $table->double('temperature_c')->nullable();
            $table->double('total_organic_carbon')->nullable();

            $table->string('original_parameter_name')->nullable();
            $table->string('original_qualifier')->nullable();
            $table->double('original_value')->nullable();
            $table->text('original_value_range')->nullable();
            $table->string('original_unit')->nullable();

            $table->string('norman_parameter_name')->nullable()->index();
            $table->string('specific_parameter_name')->nullable();
            $table->string('assessment_qualifier')->nullable();
            $table->string('assessment_class')->nullable()->index();
            $table->double('value_assessment_index')->nullable();
            $table->double('value_standardised_score')->nullable();
            $table->string('unit')->nullable();

            $table->text('general_comment')->nullable();
            $table->text('applicability_domain')->nullable();
            $table->double('applicability_domain_score')->nullable();
            $table->double('reliability_score')->nullable();
            $table->string('reliability_score_system')->nullable();
            $table->text('reliability_rational')->nullable();
            $table->string('institution_of_reliability_score')->nullable();
            $table->text('regulatory_purpose')->nullable();
            $table->string('use_of_study')->nullable();

            $table->timestamps();

            // Idempotent upsert support for fill job.
            $table->unique(
                ['source_record_type', 'source_record_id', 'data_domain'],
                'hazards_comptox_substance_data_source_unique'
            );

            $table->foreign('parse_run_id')
                ->references('id')
                ->on('hazards_parse_runs')
                ->nullOnDelete();
            $table->foreign('comptox_payload_id')
                ->references('id')
                ->on('hazards_comptox_payloads')
                ->nullOnDelete();
            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->nullOnDelete();
            $table->foreign('editor')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_comptox_substance_data');
    }
};

