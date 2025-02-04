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
        /**
         * CCA: common lists - start 
         */
        Schema::create('list_analytical_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Name of country - data_country country_ + other
        Schema::create('list_countries', function (Blueprint $table) {
            $table->id();
            //$table->string('code');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_concentration_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Fraction - data_fraction - df_ + other
        // 1 - 3 for water, 4 - 7 for soil, 8 - 9 common
        Schema::create('list_fractions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_matrix
        Schema::create('list_matrixes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_passive_sampler    
        Schema::create('list_passive_samplers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_precision_coordinates
        Schema::create('list_coordinate_precisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_protocols
        Schema::create('list_validated_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_proxy_pressures / data_pressures
        Schema::create('list_proxy_pressures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_sample_preparation_method
        Schema::create('list_sample_preparation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_standardised_method
        Schema::create('', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_type_data_source
        Schema::create('', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_type_monitoring
        Schema::create('', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // data_type_sampling
        Schema::create('', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        /**
         * CCA: common lists - end 
         */

        // data_basin_name
        Schema::create('list_basin_names', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Sampler type - data_sampler_type
        Schema::create('list_sampler_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // ****************************************************************
        // dct_data_source
        // ****************************************************************

        // Type of data source - data_type_source - dts_ + other
        Schema::create('list_type_data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
       
        // Type of monitoring - data_type_monitoring - dtm_ + other
        Schema::create('list_type_monitorings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_data_source_organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null); // English name
            //$table->string('local_name')->nullable()->default(null); // Local name - deprecated
            $table->string('acronym')->nullable()->default(null); // Acronym
            //$table->string('department'); // Department - deprecated
            //$table->string('street')->nullable()->default(null); // Address - Street - deprecated
            //$table->string('pobox'); // POBox - deprecated
            $table->string('city')->nullable()->default(null); // Address - City
            //$table->string('zip')->nullable()->default(null); // Zip - deprecated       
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->timestamps();
        });   

        Schema::create('list_data_source_laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null); // Laboratory - Name
            $table->string('city')->nullable()->default(null); // Laboratory - City      
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries'); // Laboratory - Country
            $table->timestamps();
        });  

        Schema::create('passive_sampling_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_data_source_id')->nullable()->default(null)->references('id')->on('list_type_data_sources'); // Type of data source
            $table->string('type_data_source_other')->nullable()->default(null); // Type of data source - other
            $table->foreignId('type_monitoring_id')->nullable()->default(null)->references('id')->on('list_type_monitorings'); // Type of monitoring
            $table->string('type_monitoring_other')->nullable()->default(null); // Type of monitoring - other
            $table->foreignId('data_accessibility_id')->nullable()->default(null)->references('id')->on('list_data_accessibilities'); // Data accessibility
            $table->string('data_accessibility_other')->nullable()->default(null); // Data accessibility - other
            $table->string('project_title')->nullable()->default(null); // Title of project
            $table->foreignId('organisation_id')->nullable()->default(null)->references('id')->on('list_data_source_organisations'); // Organisation
            $table->foreignId('laboratory1_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 1
            $table->foreignId('laboratory2_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 2
            $table->string('author')->nullable()->default(null); // Contact person - First name(s) Family name
            $table->string('email')->nullable()->default(null); // Contact person - e-mail
            $table->timestamps();
        });


        Schema::create('passive_sampling_analytical_methods', function (Blueprint $table) {
            $table->id();
            /*
            $table->double('lod')->nullable(); // Limit of Detection (LoD)
            $table->double('loq')->nullable(); // Limit of Quantification (LoQ)
            
            $table->decimal('uncertainty_loq')->nullable(); // Uncertainty at LoQ [%] 
            $table->foreignId('coverage_factor_id')->nullable()->default(null)->references('id')->on('list_coverage_factors'); // Coverage factor
            */
            //$table->string('unit')->nullable(); // Concentration [ng/sampler]
            $table->foreignId('sample_preparation_method_id')->nullable()->default(null)->references('id')->on('list_sample_preparation_methods'); // Sample preparation method
            $table->string('sample_preparation_method_other')->nullable(); // Sample preparation method - other
            $table->foreignId('analytical_method_id')->nullable()->default(null)->references('id')->on('list_analytical_methods'); // Analytical method
            $table->string('analytical_method_other')->nullable(); // Analytical method - other
            $table->foreignId('standardised_method_id')->nullable()->default(null)->references('id')->on('list_standardised_methods'); // Has standardised analytical method been used? Code
            $table->string('standardised_method_number')->nullable(); // Has standardised analytical method been used? Number of analytical method
            $table->string('standardised_method_other')->nullable(); // Has standardised analytical method been used? Other
            $table->foreignId('validated_method_id')->nullable()->default(null)->references('id')->on('list_validated_methods'); // Has the used method been validated according to one of the below protocols?
            $table->foreignId('corrected_recovery_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Have the results been corrected for extraction recovery?
            $table->foreignId('field_blank_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Was a field blank checked?
            $table->foreignId('iso_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Is the laboratory accredited according to ISO 17025?
            $table->foreignId('given_analyte_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Is the laboratory accredited for the given analyte?
            $table->foreignId('laboratory_participate_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Does the laboratory participate in interlaboratory studies for the given determinand?
            $table->foreignId('summary_performance_id')->nullable()->default(null)->references('id')->on('list_summary_performances'); // Summary of performance of the laboratory in interlaboratory studies for the given determinand
            $table->foreignId('control_charts_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Are control charts recorded for the given determinand?
            $table->foreignId('authority_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Are the data controlled by a competent authority (apart from accreditation bodies)?
            $table->text('remarks')->nullable(); // Remarks
            $table->timestamps();
        });

        Schema::create('passive_sampling_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->foreignId('country_other_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country - Other
            //$table->string('country')->nullable()->default(null);
            //$table->string('country_other')->nullable()->default(null);
            //$table->string('national_name')->nullable()->default(null);
            $table->string('short_sample_code')->nullable()->default(null);
            $table->string('sample_code')->nullable()->default(null);
            $table->string('provider_code')->nullable()->default(null);
            $table->string('national_code')->nullable()->default(null);
            $table->string('code_ec_wise')->nullable()->default(null);
            $table->string('code_ec_other')->nullable()->default(null);
            $table->string('code_other')->nullable()->default(null);
            $table->string('specific_locations')->nullable()->default(null);
            $table->float('latitude', precision: 53)->nullable()->default(null);
            $table->float('longitude', precision: 53)->nullable()->default(null);
            $table->timestamps();
        });        

        Schema::create('passive_sampling_mains', function (Blueprint $table) {
            $table->id();
            // Filter:  Country, Sampling site/station 
            $table->foreignId('station_id')->constrained()->nullable()->default(null)->references('id')->on('passive_sampling_stations');
            // Filter: Ecosystems/matrices
            $table->foreignId('matrix_id')->constrained()->nullable()->default(null)->references('id')->on('list_matrices');
            // Filter: Substance
            $table->foreignId('substance_id')->constrained()->nullable()->default(null)->references('id')->on('susdat_substances');
            // Filter: Year from, Year to
            $table->unsignedSmallInteger('sampling_date_year')->nullable()->default(null);
            // Filter: Concentration data
            // Filter: Concentration equal or higher than
            $table->foreignId('concentration_indicator_id')->constrained()->nullable()->default(null)->references('id')->on('list_concentration_indicators');
            $table->float('concentration_value', 10)->nullable()->default(null);
            // $table->string('unit_extra')->nullable()->default(null); // ???????????
            $table->foreignId('method_id')->constrained()->nullable()->default(null)->references('id')->on('passive_sampling_analytical_methods');
            $table->foreignId('data_source_id')->constrained()->nullable()->default(null)->references('id')->on('passive_sampling_data_sources');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passive_samplings');
    }
};
