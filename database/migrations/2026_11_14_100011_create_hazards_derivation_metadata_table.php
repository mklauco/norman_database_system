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
        Schema::create('hazards_derivation_metadata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('selection_id')->nullable()->index();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();
            $table->string('bucket', 12)->nullable();
            $table->unsignedBigInteger('hazards_substance_data_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->text('data_source')->nullable();
            $table->text('editor')->nullable();
            $table->dateTime('record_date')->nullable();

            $table->text('reference_type')->nullable();
            $table->text('title')->nullable();
            $table->text('authors')->nullable();
            $table->smallInteger('year')->nullable();
            $table->text('bibliographic_source')->nullable();
            $table->text('hazards_file_doi')->nullable();

            $table->text('test_type')->nullable();
            $table->text('performed_under_glp')->nullable();
            $table->text('standard_test')->nullable();

            $table->text('substance_name')->nullable();
            $table->text('cas_number')->nullable();
            $table->text('radio_labeled_substance')->nullable();

            $table->text('standard_qualifier')->nullable();
            $table->text('standard_used')->nullable();
            $table->text('test_matrix')->nullable();
            $table->text('test_species')->nullable();
            $table->double('duration_days')->nullable();
            $table->double('exposure_concentration')->nullable();
            $table->double('ph')->nullable();
            $table->double('temperature_c')->nullable();
            $table->double('total_organic_carbon')->nullable();

            $table->text('original_parameter_name')->nullable();
            $table->text('original_qualifier')->nullable();
            $table->double('original_value')->nullable();
            $table->text('original_value_range')->nullable();
            $table->text('original_unit')->nullable();

            $table->text('assessment_parameter_name')->nullable();
            $table->text('assessment_qualifier')->nullable();
            $table->double('assessment_value')->nullable();
            $table->text('assessment_unit')->nullable();

            $table->string('hazard_criterion', 10)->nullable();
            $table->text('original_classification')->nullable();
            $table->double('classification_score')->nullable();

            $table->text('general_comment')->nullable();
            $table->text('applicability_domain')->nullable();
            $table->double('applicability_domain_score')->nullable();
            $table->double('reliability_score')->nullable();
            $table->text('reliability_score_system')->nullable();
            $table->text('reliability_rational')->nullable();
            $table->text('institution_of_reliability_score')->nullable();
            $table->text('regulatory_context')->nullable();
            $table->text('institution_original_classification')->nullable();

            $table->text('norman_classification')->nullable();
            $table->tinyInteger('norman_vote')->nullable();
            $table->text('automated_expert_vote')->nullable();

            $table->timestamps();

            $table->index(['susdat_substance_id', 'bucket'], 'hazards_derivation_meta_substance_bucket_idx');

            $table->foreign('selection_id')
                ->references('id')
                ->on('hazards_derivation_selections')
                ->nullOnDelete();
            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('hazards_substance_data_id')
                ->references('id')
                ->on('hazards_comptox_substance_data')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('user_id')
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
        Schema::dropIfExists('hazards_derivation_metadata');
    }
};
