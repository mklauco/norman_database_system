<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bioassay_field_studies', function (Blueprint $table) {
            $table->id();

            // Foreign key to bioassay_monitor_sample_data
            $table->foreignId('m_sd_id')->nullable()->default(null)
                ->constrained('bioassay_monitor_sample_data')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Foreign key to data source
            $table->foreignId('m_ds_id')->nullable()->default(null)
                ->constrained('bioassay_monitor_data_source')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('m_auxiliary_sample_identification');

            // Foreign key to bioassay type
            $table->unsignedTinyInteger('m_bioassay_type_id')->nullable()->default(null);
            $table->foreign('m_bioassay_type_id')
                ->references('id')
                ->on('monitor_x_bioassay_type')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('m_bioassay_type_other');

            // Foreign key to bioassay name
            $table->unsignedTinyInteger('m_bioassay_name_id')->nullable()->default(null);
            $table->foreign('m_bioassay_name_id')
                ->references('id')
                ->on('monitor_x_bioassay_name')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('bioassay_name_other');

            // Foreign key to adverse outcome
            $table->unsignedTinyInteger('m_adverse_outcome_id')->nullable()->default(null);
            $table->foreign('m_adverse_outcome_id')
                ->references('id')
                ->on('monitor_x_adverse_outcome')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('adverse_outcome_other');

            // Foreign key to test organism
            $table->unsignedTinyInteger('m_test_organism_id')->nullable()->default(null);
            $table->foreign('m_test_organism_id')
                ->references('id')
                ->on('monitor_x_test_organism')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('test_organism_other');

            // Foreign key to cell line strain
            $table->unsignedTinyInteger('m_cell_line_strain_id')->nullable()->default(null);
            $table->foreign('m_cell_line_strain_id')
                ->references('id')
                ->on('monitor_x_cell_line_strain')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('cell_line_strain_other');

            // Foreign key to endpoint
            $table->unsignedTinyInteger('m_endpoint_id')->nullable()->default(null);
            $table->foreign('m_endpoint_id')
                ->references('id')
                ->on('monitor_x_endpoint')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('endpoint_other');

            // Foreign key to effect
            $table->unsignedTinyInteger('m_effect_id')->nullable()->default(null);
            $table->foreign('m_effect_id')
                ->references('id')
                ->on('monitor_x_effect')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('effect_other');

            // Foreign key to measured parameter
            $table->unsignedTinyInteger('m_measured_parameter_id')->nullable()->default(null);
            $table->foreign('m_measured_parameter_id')
                ->references('id')
                ->on('monitor_x_measured_parameter')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('measured_parameter_other');
            $table->text('exposure_duration');
            $table->text('effect_significantly');
            $table->text('maximal_tested_ref');
            $table->text('dose_response_relationship');

            // Foreign key to main determinand
            $table->unsignedTinyInteger('m_main_determinand_id')->nullable()->default(null);
            $table->foreign('m_main_determinand_id')
                ->references('id')
                ->on('monitor_x_main_determinand')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('main_determinand_other');
            $table->text('value_determinand');

            // Foreign key to effect equivalent
            $table->unsignedTinyInteger('m_effect_equivalent_id')->nullable()->default(null);
            $table->foreign('m_effect_equivalent_id')
                ->references('id')
                ->on('monitor_x_effect_equivalent')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('effect_equivalent_other');
            $table->text('value_effect_equivalent');

            // Foreign key to standard substance
            $table->unsignedTinyInteger('m_standard_substance_id')->nullable()->default(null);
            $table->foreign('m_standard_substance_id')
                ->references('id')
                ->on('monitor_x_standard_substance')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('standard_substance_other');
            $table->text('limit_of_detection');
            $table->text('limit_of_quantification');
            $table->text('date_performed_month');
            $table->text('date_performed_year');
            $table->text('bioassay_performed');
            $table->text('guideline');
            $table->text('deviation');
            $table->text('describe_deviation');

            // Foreign key to assay format
            $table->unsignedTinyInteger('m_assay_format_id')->nullable()->default(null);
            $table->foreign('m_assay_format_id')
                ->references('id')
                ->on('monitor_x_assay_format')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('assay_format_other');

            // Foreign key to solvent
            $table->unsignedTinyInteger('m_solvent_id')->nullable()->default(null);
            $table->foreign('m_solvent_id')
                ->references('id')
                ->on('monitor_x_solvent')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('solvent_other');
            $table->text('max_solvent_concentration');
            $table->text('test_medium');

            // Foreign key to test system
            $table->unsignedTinyInteger('m_test_system_id')->nullable()->default(null);
            $table->foreign('m_test_system_id')
                ->references('id')
                ->on('monitor_x_test_system')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('test_system_other');
            $table->text('no_organisms');
            $table->text('age_organisms');

            // Foreign key to life stage
            $table->unsignedTinyInteger('m_life_stage_id')->nullable()->default(null);
            $table->foreign('m_life_stage_id')
                ->references('id')
                ->on('monitor_x_life_stage')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('life_stage_other');
            $table->text('no_experiment_repetitions');
            $table->text('no_replicates_per_treatment');
            $table->text('no_concentration_treatments');

            // Foreign key to effect level
            $table->unsignedTinyInteger('m_effect_level_id')->nullable()->default(null);
            $table->foreign('m_effect_level_id')
                ->references('id')
                ->on('monitor_x_effect_level')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('effect_level_other');
            $table->text('cv_main_determinand');
            $table->text('average_cv_resopnse');
            $table->text('statistical_assessment');
            $table->text('significance_level');
            $table->text('statistical_calculation');
            $table->text('positive_control_tested');

            // Foreign key to positive control
            $table->unsignedTinyInteger('m_positive_control_id')->nullable()->default(null);
            $table->foreign('m_positive_control_id')
                ->references('id')
                ->on('monitor_x_positive_control')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('positive_control_other');
            $table->text('compliance_guideline_values');
            $table->text('compliance_long_term');
            $table->text('solvent_control_tested');
            $table->text('respective_blank_sample');
            $table->text('temperature_test');
            $table->text('temperature_compliance');
            $table->text('ph_sample_test');
            $table->text('ph_sample_adjusted');
            $table->text('ph_compliance');
            $table->text('do_sample_test');
            $table->text('do_compliance');
            $table->text('conductivity_sample_test');
            $table->text('conductivity_compliance');
            $table->text('ammonium_measured');
            $table->text('ammonium_compliance');
            $table->text('light_intensity');
            $table->text('photoperiod');
            $table->text('reference_method');
            $table->text('reference_paper');

            // Add timestamps
            $table->timestamps();

            // Add user tracking
            // $table->foreignId('created_by')->nullable()->constrained('users');
            // $table->foreignId('updated_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bioassay_field_studies');
    }
};
