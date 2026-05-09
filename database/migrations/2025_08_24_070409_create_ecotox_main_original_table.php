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
        Schema::create('ecotox_main_original', function (Blueprint $table) {
            $table->id();
            $table->string('ecotox_id', 30)->primary();

            // Data source information
            $table->text('data_source')->nullable();
            $table->text('data_source_id')->nullable();
            $table->text('data_source_ref')->nullable();
            $table->text('data_protection')->nullable();
            $table->text('data_source_link')->nullable();

            // Edit information
            $table->text('edit_editor')->nullable();
            $table->date('edit_date')->nullable();

            // Reference information
            $table->text('reference_type')->nullable();
            $table->text('reference_id')->nullable();
            $table->text('study_title')->nullable();
            $table->text('authors')->nullable();
            $table->string('year_publication', 255)->nullable();
            $table->text('bibliographic_source')->nullable();

            // Test information
            $table->text('testing_laboratory')->nullable();
            $table->text('matrix_habitat')->nullable();
            $table->text('test_type')->nullable();
            $table->text('acute_or_chronic')->nullable();

            // Substance information
            $table->unsignedInteger('sus_id')->nullable();
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            $table->text('substance_name')->nullable();
            $table->text('cas_number')->nullable();
            $table->text('ec_number')->nullable();
            $table->text('purity_qualifier')->nullable();
            $table->text('purity')->nullable();
            $table->text('supplier')->nullable();
            $table->text('vehicle_substance')->nullable();
            $table->text('known_concentrations')->nullable();
            $table->text('radio_substance')->nullable();
            $table->text('preparation_solutions')->nullable();

            // Standard information
            $table->text('standard_qualifier')->nullable();
            $table->text('standard_used')->nullable();
            $table->text('principles')->nullable();
            $table->text('glp_certificate')->nullable();

            // Effect information
            $table->text('effect')->nullable();
            $table->text('effect_measurement')->nullable();
            $table->text('endpoint')->nullable();
            $table->text('duration')->nullable();
            $table->text('duration_unit')->nullable();
            $table->text('total_test_duration')->nullable();
            $table->text('recovery_considered')->nullable();

            // Organism information
            $table->text('scientific_name')->nullable();
            $table->text('common_name')->nullable();
            $table->text('taxonomic_group')->nullable();
            $table->text('body_length')->nullable();
            $table->text('length_unit')->nullable();
            $table->text('body_weight')->nullable();
            $table->text('weight_unit')->nullable();
            $table->text('initial_cell_density')->nullable();
            $table->text('reproductive_condition')->nullable();
            $table->text('other_effects')->nullable();
            $table->text('lipid')->nullable();
            $table->text('age')->nullable();
            $table->text('age_unit')->nullable();
            $table->text('life_stage')->nullable();
            $table->text('gender')->nullable();
            $table->text('strain_clone')->nullable();
            $table->text('organism_source')->nullable();
            $table->text('culture_handling')->nullable();
            $table->text('acclimation')->nullable();

            // Concentration and exposure information
            $table->text('nominal_concentrations')->nullable();
            $table->text('measured_or_nominal')->nullable();
            $table->text('limit_test')->nullable();
            $table->text('range_finding_study')->nullable();

            // Analytical information
            $table->text('analytical_matrix')->nullable();
            $table->text('analytical_schedule')->nullable();
            $table->text('analytical_method')->nullable();
            $table->text('analytical_recovery')->nullable();
            $table->text('limit_of_quantification')->nullable();

            // Exposure details
            $table->text('exposure_regime')->nullable();
            $table->text('exposure_duration')->nullable();
            $table->text('exposure_duration_unit')->nullable();
            $table->text('application_freq')->nullable();
            $table->text('application_freq_unit')->nullable();
            $table->text('exposure_route')->nullable();

            // Control information
            $table->text('positive_control_used')->nullable();
            $table->text('positive_control_substance')->nullable();
            $table->text('effects_control')->nullable();
            $table->text('vehicle_control')->nullable();
            $table->text('effects_vehicle')->nullable();

            // Environmental conditions
            $table->text('intervals_water')->nullable();
            $table->text('intervals_water_unit')->nullable();
            $table->text('ph')->nullable();
            $table->text('adjustment_ph')->nullable();
            $table->text('temperature')->nullable();
            $table->text('temperature_unit')->nullable();
            $table->text('conductivity')->nullable();
            $table->text('conductivity_unit')->nullable();
            $table->text('light_intensity')->nullable();
            $table->text('light_intensity_unit')->nullable();
            $table->text('light_quality')->nullable();
            $table->text('photo_period')->nullable();
            $table->text('hardness')->nullable();
            $table->text('hardness_unit')->nullable();
            $table->text('chlorine')->nullable();
            $table->text('chlorine_unit')->nullable();
            $table->text('alkalinity')->nullable();
            $table->text('alkalinity_unit')->nullable();
            $table->text('salinity')->nullable();
            $table->text('salinity_unit')->nullable();
            $table->text('organic_carbon')->nullable();
            $table->text('organic_carbon_unit')->nullable();
            $table->text('dissolved_oxygen')->nullable();
            $table->text('dissolved_oxygen_unit')->nullable();

            // Test setup
            $table->text('material_vessel')->nullable();
            $table->text('volume_vessel')->nullable();
            $table->text('open_closed')->nullable();
            $table->text('aeration')->nullable();
            $table->text('description_medium')->nullable();
            $table->text('culture_medium')->nullable();
            $table->text('feeding_protocols')->nullable();
            $table->text('type_amount_food')->nullable();
            $table->text('number_organisms')->nullable();
            $table->text('number_replicates')->nullable();

            // Statistical and result information
            $table->text('statistical_method')->nullable();
            $table->text('trend')->nullable();
            $table->text('significance_result')->nullable();
            $table->text('significance_level')->nullable();
            $table->text('concentration_qualifier')->nullable();
            $table->string('concentration_value', 255)->nullable();
            $table->text('concentration_unit')->nullable();
            $table->text('estimate_variability')->nullable();
            $table->text('test_item')->nullable();
            $table->text('result_comment')->nullable();
            $table->text('dose_response')->nullable();
            $table->text('availability_raw_data')->nullable();
            $table->text('study_available')->nullable();
            $table->text('general_comment')->nullable();

            // Quality and reliability information
            $table->text('reliability_study')->nullable();
            $table->text('reliability_score')->nullable();
            $table->text('existing_rational_reliability')->nullable();
            $table->text('regulatory_purpose')->nullable();

            // Additional fields
            $table->text('final_cell_density')->nullable();
            $table->text('used_for_regulaltory_purpose')->nullable();
            $table->text('institution_study')->nullable();
            $table->text('deformed_or_abnormal_cells')->nullable();
            $table->text('negative_control_used')->nullable();
            $table->text('response_site')->nullable();
            $table->text('final_body_length_of_control')->nullable();
            $table->text('unit_concentration')->nullable();
            $table->text('biotest_id')->nullable();
            $table->text('standard_test')->nullable();
            $table->text('final_body_weight_of_control')->nullable();
            $table->string('use_study', 255)->nullable();
            $table->foreignId('added_by')
                ->nullable()
                ->default(null)
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Laravel timestamps
            $table->timestamps();

            // Indexes
            $table->index('sus_id');
            $table->index('ecotox_id');
            $table->index('matrix_habitat');
            $table->index('taxonomic_group');
            $table->index('endpoint');
            $table->index('acute_or_chronic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_main_original');
    }
};
