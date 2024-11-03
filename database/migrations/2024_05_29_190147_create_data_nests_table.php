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
        
        // Schema::create('substances', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });

        Schema::create('list_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_coordinate_precisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_proxy_pressures', function (Blueprint $table) { // cca
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('list_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->timestamps();
        });
        
        Schema::create('list_concentration_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        }); 

        Schema::create('list_sampling_techniques', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('empodat_analytical_methods', function (Blueprint $table) {
            $table->id();
            $table->double('lod');
            $table->double('loq');
            $table->decimal('uncertainty_loq', 4, 2); // Uncertainty at LoQ [%] ?? 5,3
            $table->timestamps();
        });

        // dct_data_source

        // Type of data source - data_type_source - dts_ + other
        Schema::create('list_type_data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Data source - data_source - ds_ + other
        Schema::create('list_data_sources', function (Blueprint $table) {
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

        // Data accessibility - data_accessibility - dda_ + other
        Schema::create('list_data_accessibilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_data_source_organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null); // English name
            //$table->string('local_name')->nullable()->default(null); // Local name - deprecated
            //$table->string('acronym')->nullable()->default(null); // Acronym - deprecated
            //$table->string('department'); // Department - deprecated
            //$table->string('street')->nullable()->default(null); // Address - Street - deprecated
            //$table->string('pobox'); // POBox - deprecated
            $table->string('city')->nullable()->default(null); // Address - City
            //$table->string('zip')->nullable()->default(null); // Zip - deprecated       
            $table->foreignId('country_id')->constrained()->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->timestamps();
        });   

        Schema::create('list_data_source_laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null); // Laboratory - Name
            $table->string('city')->nullable()->default(null); // Laboratory - City      
            $table->foreignId('country_id')->constrained()->nullable()->default(null)->references('id')->on('list_countries'); // Laboratory - Country
            $table->timestamps();
        });  

        Schema::create('empodat_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_data_source_id')->nullable()->default(null)->references('id')->on('list_type_data_sources'); // Type of data source
            $table->string('type_data_source_other')->nullable()->default(null); // Type of data source - other
            $table->foreignId('type_monitoring_id')->nullable()->default(null)->references('id')->on('list_type_monitorings'); // Type of monitoring
            $table->string('type_monitoring_other')->nullable()->default(null); // Type of monitoring - other
            $table->foreignId('data_accessibility_id')->nullable()->default(null)->references('id')->on('list_data_accessibilities'); // Data accessibility
            $table->string('data_accessibility_other')->nullable()->default(null); // Data accessibility - other
            $table->string('project_title')->nullable()->default(null); // Title of project
            //$table->string('id_laboratory')->nullable()->default(null); // Laboratory ID  - deprecated  
            //$table->foreignId('organisation_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_organisations'); // Organisation
            $table->foreignId('organisation_id')->nullable()->default(null)->references('id')->on('list_data_source_organisations'); // Organisation
            // Question: 2*1:N OR 1*N:M ???
            //$table->foreignId('laboratory1_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 1
            //$table->foreignId('laboratory2_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 2
            $table->foreignId('laboratory1_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 1
            $table->foreignId('laboratory2_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 2
            $table->string('author')->nullable()->default(null); // Contact person - First name(s) Family name
            $table->string('email')->nullable()->default(null); // Contact person - e-mail
            $table->text('reference1')->nullable()->default(null); // Reference 1 (reference - website/DOI/etc.)
            $table->text('reference2')->nullable()->default(null); // Reference 2 (reference - website/DOI/etc.)
            $table->timestamps();
        });

        Schema::create('list_treatment_less', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('file_sources', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });


        Schema::create('empodat_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->foreignId('country_other')->nullable()->default(null)->references('id')->on('list_countries'); // Country - Other
            $table->string('national_name')->nullable()->default(null);
            $table->string('short_sample_code')->nullable()->default(null);
            $table->string('sample_code')->nullable()->default(null);
            $table->string('provider_code')->nullable()->default(null);
            $table->string('code_ec_wise')->nullable()->default(null);
            $table->string('code_ec_other')->nullable()->default(null);
            $table->string('code_other')->nullable()->default(null);
            $table->string('specific_locations')->nullable()->default(null);
            $table->float('latitude', precision: 53)->nullable()->default(null);
            $table->float('longitude', precision: 53)->nullable()->default(null);
            $table->timestamps();
        });
        
        Schema::create('empodat_main', function (Blueprint $table) {
            $table->id();
            // Filter:  Country, Sampling site/station 
            $table->foreignId('station_id')->constrained()->nullable()->default(null)->references('id')->on('empodat_stations');
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
            $table->string('unit_extra')->nullable()->default(null); // ???????????

            $table->foreignId('method_id')->constrained()->nullable()->default(null)->references('id')->on('empodat_analytical_methods');
            $table->foreignId('data_source_id')->constrained()->nullable()->default(null)->references('id')->on('empodat_data_sources');
        });

        Schema::create('empodat_minor', function (Blueprint $table) {

            $table->foreignId('coordinate_precision_id')->constrained()->nullable()->default(null)->references('id')->on('list_coordinate_precisions');
            $table->foreignId('proxy_pressure_id')->constrained()->nullable()->default(null)->references('id')->on('list_proxy_pressures'); // cca
            $table->float('altitude', 10)->nullable()->default(null);
            $table->foreignId('sampling_technique_id')->constrained()->nullable()->default(null)->references('id')->on('list_sampling_techniques');

            $table->date('sampling_date')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_date_month')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_date_date')->nullable()->default(null);
            $table->time('sampling_date_time')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_duration_day')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_duration_hour')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->json('remarks')->nullable()->default(null);
            $table->foreignId('treatment_less_id')->nullable()->default(null)->references('id')->on('list_treatment_less');
            $table->foreignId('availability_id')->nullable()->default(null)->references('id')->on('availabilities');
            $table->foreignId('file_source_id')->nullable()->default(null)->references('id')->on('file_sources');
            $table->foreignId('added_by')->nullable()->default(null)->references('id')->on('users');

            $table->timestamps();
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('empodat_stations');
        Schema::dropIfExists('empodat_main');
    }
};
