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
            $table->string('title')->nullable()->default(null);
            $table->string('subtitle')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('dct_name')->nullable()->default(null);
            $table->string('unit')->nullable()->default(null);;
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

        // ****************************************************************
        // dct_analytical_methods
        // ****************************************************************

        Schema::create('list_coverage_factors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_sample_preparation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_analytical_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_standardised_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_validated_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_yes_no_questions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_summary_performances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_sampling_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('list_sampling_collection_devices', function (Blueprint $table) {
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
   
        // ****************************************************************
        // dct_analysis_xyz >> dct_analysis_all
        // ****************************************************************

        // Fraction - data_fraction - df_ + other
        // 1 - 3 for wtaer, 4 - 7 for soil, 8 - 9 common
        Schema::create('list_fractions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Proxy pressures
        /*
        Schema::create('list_proxy_pressures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        */
        // Type of depth sampling
        Schema::create('list_depths', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Sampling technique
        Schema::create('list_sampling_techniqueXs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Type of treatment plant associated with the parameter
        Schema::create('list_treatment_plants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Advanced treatment steps
        Schema::create('list_advanced_treatment_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Effluent/Influent
        Schema::create('list_effluent_influents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Location
        Schema::create('list_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Sampling method
        Schema::create('list_sampling_methodXs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Sampling collection device
        Schema::create('list_sampling_collection_deviceXs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Air filtration system
        Schema::create('list_air_filtration_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Soil texture
        Schema::create('list_soil_textures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Grain size distribution [mm]
        Schema::create('list_grain_size_distributions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Species group
        Schema::create('list_species_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Species category
        Schema::create('list_species_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Use category
        Schema::create('list_use_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Treatment before use
        Schema::create('list_treatment_before_uses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Reuse context
        Schema::create('list_reuse_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Basis of measurement
        Schema::create('list_basis_of_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Tissue
        Schema::create('list_tissues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Packing material of samples
        Schema::create('list_packing_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Biota sex
        Schema::create('list_biota_sexs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Geographic range of pooled individuals
        Schema::create('list_geographic_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Did a receive medical treatment prior to death?
        Schema::create('list_medical_treatments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Was the species euthanised?
        Schema::create('list_was_the_species_euthaniseds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Nutrition condition
        Schema::create('list_nutrition_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });


  }
  
  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::dropIfExists('');
  }

};