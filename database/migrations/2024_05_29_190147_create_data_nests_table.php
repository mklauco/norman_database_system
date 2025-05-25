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
        
        Schema::create('empodat_analytical_methods', function (Blueprint $table) {
            $table->id();
            $table->double('lod')->nullable(); // Limit of Detection (LoD)
            $table->double('loq')->nullable(); // Limit of Quantification (LoQ)
            $table->decimal('uncertainty_loq')->nullable(); // Uncertainty at LoQ [%] 
            $table->foreignId('coverage_factor_id')->nullable()->default(null)->references('id')->on('list_coverage_factors'); // Coverage factor
            $table->foreignId('sample_preparation_method_id')->nullable()->default(null)->references('id')->on('list_sample_preparation_methods'); // Sample preparation method
            $table->string('sample_preparation_method_other')->nullable(); // Sample preparation method - other
            $table->foreignId('analytical_method_id')->nullable()->default(null)->references('id')->on('list_analytical_methods'); // Analytical method
            $table->string('analytical_method_other')->nullable(); // Analytical method - other

            $table->foreignId('standardised_method_id')->nullable()->default(null)->references('id')->on('list_standardised_methods'); // Has standardised analytical method been used? Code
            $table->string('standardised_method_other')->nullable(); // Has standardised analytical method been used? Other
            $table->string('standardised_method_number')->nullable(); // Has standardised analytical method been used? Number
            $table->foreignId('validated_method_id')->nullable()->default(null)->references('id')->on('list_validated_methods'); // Has the used method been validated according to one of the below protocols?
            $table->foreignId('corrected_recovery_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Have the results been corrected for extraction recovery?
            $table->foreignId('field_blank_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Was a field blank checked?
            $table->foreignId('iso_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Is the laboratory accredited according to ISO 17025?
            $table->foreignId('given_analyte_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Is the laboratory accredited for the given analyte?
            $table->foreignId('laboratory_participate_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Has the laboratory participated in any interlaboratory comparison study?
            $table->foreignId('summary_performance_id')->nullable()->default(null)->references('id')->on('list_summary_performances'); // Summary of performance of the laboratory in interlaboratory study for the given determinand
            $table->foreignId('control_charts_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Are control charts used?
            $table->foreignId('internal_standards_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Are internal standards used?
            $table->foreignId('authority_id')->nullable()->default(null)->references('id')->on('list_yes_no_questions'); // Are the data controlled by competent authority (apart from accreditation body)?
            $table->integer('rating')->nullable(); // Rating
            $table->text('remark')->nullable(); // Remark
            $table->foreignId('sampling_method_id')->nullable()->default(null)->references('id')->on('list_sampling_methods'); // Sampling method (Outdoor Air)
            $table->foreignId('sampling_collection_device_id')->nullable()->default(null)->references('id')->on('list_sampling_collection_devices'); // Sampling collection device (Outdoor Air)
            $table->float('foa')->nullable(); // FOA <- UoA_EUDust_DCT_target_IndoorAir.xlsb           
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


        Schema::create('empodat_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->foreignId('country_other_id')->nullable()->default(null)->references('id')->on('list_countries'); // Country - Other
            $table->string('country')->nullable()->default(null);
            $table->string('country_other')->nullable()->default(null);
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

        
        // dct_analysis_water_ground
        Schema::create('empodat_water_grounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraction_id')->nullable()->default(null)->references('id')->on('list_fractions'); // Fraction
            $table->string('fraction_other')->nullable()->default(null); // Fraction - other
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            // proxy_pressure >>  asi do miror
            //$table->foreignId('proxy_pressure_id')->nullable()->default(null)->references('id')->on('list_proxy_pressures'); // Proxy pressures
            // Ground Water
            $table->foreignId('type_of_depth_sampling_id')->nullable()->default(null)->references('id')->on('list_depths'); // Type of depth sampling            
            $table->string('type_of_depth_sampling_other')->nullable()->default(null); // Type of depth sampling - other
            $table->string('depth')->nullable()->default(null); // Depth [m]     
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->string('dilution_factor_in_use_category')->nullable()->default(null); // Dilution factor in the Use category
            $table->foreignId('advanced_treatment_steps_id')->nullable()->default(null)->references('id')->on('list_advanced_treatment_steps'); // Advanced treatment steps
            $table->string('advanced_treatment_steps_other')->nullable()->default(null); // Advanced treatment steps - other
            $table->string('ph')->nullable()->default(null); // pH
            $table->string('temperature')->nullable()->default(null); // Temperature [°C]
            $table->string('spm_concentration')->nullable()->default(null); // Suspended particulate matter (SPM) conc. [mg/l]
            $table->string('conductivity')->nullable()->default(null); // Conductivity µS/cm
            $table->string('doc')->nullable()->default(null); // Dissolved organic carbon (DOC) [mg/l]
            $table->string('hardness')->nullable()->default(null); // Hardness [mg/l] CaCO3
            $table->string('o2_m')->nullable()->default(null); // Oxygen (O2) [mg/l]
            $table->string('o2_p')->nullable()->default(null); // Oxygen (O2) [%]
            $table->string('bod5')->nullable()->default(null); // Biochemical oxygen demand (BOD5) [mg/l]
            $table->string('h2s')->nullable()->default(null); // Hydrogen sulfide (H2S) [mg/l]
            $table->string('p_po4')->nullable()->default(null); // Phosphates (P (PO4)) [mg/l]
            $table->string('n_no2')->nullable()->default(null); // Nitrite (N (NO2)) [mg/l]
            $table->string('tss')->nullable()->default(null); // Total suspended solid (TSS) [mg/l]
            $table->string('p_total')->nullable()->default(null); // Total phosphorus (P total) [mg/l]
            $table->string('n_no3')->nullable()->default(null); // Nitrate (N (NO3)) [mg/l]
            $table->string('n_total')->nullable()->default(null); // N total [mg/l]
            $table->string('remark1')->nullable()->default(null); // REMARK_1 - years of pooled samples etc.
            $table->string('remark2')->nullable()->default(null); // REMARK_2 - state of autolysis etc.            
            $table->string('total_organic_carbon')->nullable()->default(null); // Total organic carbon            
            $table->timestamps();
        });            
        // dct_analysis_water_waste
        Schema::create('empodat_water_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraction_id')->nullable()->default(null)->references('id')->on('list_fractions'); // Fraction
            $table->string('fraction_other')->nullable()->default(null); // Fraction - other
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            $table->foreignId('sampling_technique_id')->nullable()->default(null)->references('id')->on('list_sampling_techniqueXs'); // Sampling technique
            $table->string('sampling_technique_other')->nullable()->default(null); // Sampling technique - other
            // WWTP
            $table->foreignId('type_of_treatment_plant_id')->nullable()->default(null)->references('id')->on('list_treatment_plants'); // Type of treatment plant associated with the parameter
            $table->string('type_of_treatment_plant_other')->nullable()->default(null); // Type of treatment plant associated with the parameter - other
            $table->foreignId('advanced_treatment_steps_id')->nullable()->default(null)->references('id')->on('list_advanced_treatment_steps'); // Advanced treatment steps
            $table->string('advanced_treatment_steps_other')->nullable()->default(null); // Advanced treatment steps - other
            $table->string('capacity')->nullable()->default(null); // Capacity (population equivalent)
            $table->string('daily_flow')->nullable()->default(null); // Daily flow [m3/day]
            // Waste Water
            $table->foreignId('effluent_influent_id')->nullable()->default(null)->references('id')->on('list_effluent_influents'); // Effluent/Influent
            $table->string('effluent_influent_other')->nullable()->default(null); // Effluent/Influent - other
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->string('dilution_factor_in_use_category')->nullable()->default(null); // Dilution factor in the Use category
            // Waste Water - parameters
            $table->string('ph')->nullable()->default(null); // pH
            $table->string('temperature')->nullable()->default(null); // Temperature [°C]
            $table->string('spm_concentration')->nullable()->default(null); // Suspended particulate matter (SPM) conc. [mg/l]
            $table->string('doc')->nullable()->default(null); // Dissolved organic carbon (DOC) [mg/l]
            $table->string('conductivity')->nullable()->default(null); // Conductivity µS/cm
            $table->string('hardness')->nullable()->default(null); // Hardness [mg/l] CaCO3
            $table->string('salinity')->nullable()->default(null); // Salinity [psu]
            $table->string('o2_m')->nullable()->default(null); // Oxygen (O2) [mg/l]
            $table->string('o2_p')->nullable()->default(null); // Oxygen (O2) [%]
            $table->string('bod5')->nullable()->default(null); // Biochemical oxygen demand (BOD5) [mg/l]
            $table->string('h2s')->nullable()->default(null); // Hydrogen sulfide (H2S) [mg/l]
            $table->string('p_po4')->nullable()->default(null); // Phosphates (P (PO4)) [mg/l]
            $table->string('n_no2')->nullable()->default(null); // Nitrite (N (NO2)) [mg/l]
            $table->string('tss')->nullable()->default(null); // Total suspended solid (TSS) [mg/l]
            $table->string('p_total')->nullable()->default(null); // Total phosphorus (P total) [mg/l]
            $table->string('n_no3')->nullable()->default(null); // Nitrate (N (NO3)) [mg/l]
            $table->string('n_total')->nullable()->default(null); // N total [mg/l]
            // Sewage Sludge
            $table->string('sludge_retention_time')->nullable()->default(null); // Sludge retention time [day/s]
            $table->string('volume_of_reactor')->nullable()->default(null); // Volume of the reactor for biological treatment [m3]
            //?? duplicita //$table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            //?? duplicita //$table->string('use_category_other')->nullable()->default(null); // Use category - other
            // duplicita //$table->string('dilution_factor_in_use_category')->nullable()->default(null); // Dilution factor in the Use category
            $table->foreignId('treatment_before_use_id')->nullable()->default(null)->references('id')->on('list_treatment_before_uses'); // Treatment before use
            $table->string('treatment_before_use_other')->nullable()->default(null); // Treatment before use - other          
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });
        // dct_analysis_water_surface
        Schema::create('empodat_water_surfaces', function (Blueprint $table) {
            $table->id();
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            $table->foreignId('type_of_depth_sampling_id')->nullable()->default(null)->references('id')->on('list_depths'); // Type of depth sampling            
            $table->string('type_of_depth_sampling_other')->nullable()->default(null); // Type of depth sampling - other
            $table->string('depth')->nullable()->default(null); // Depth [m]     

            $table->foreignId('fraction_id')->nullable()->default(null)->references('id')->on('list_fractions'); // Fraction
            $table->string('fraction_other')->nullable()->default(null); // Fraction - other
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->foreignId('treatment_before_use_id')->nullable()->default(null)->references('id')->on('list_treatment_before_uses'); // Treatment before use
            $table->string('treatment_before_use_other')->nullable()->default(null); // Treatment before use - other          
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });           
        // dct_analysis_water_sediments
        Schema::create('empodat_water_sediments', function (Blueprint $table) {
            $table->id();
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            $table->string('ph')->nullable()->default(null); // pH
            $table->string('temperature')->nullable()->default(null); // Temperature [°C]
            $table->string('spm_concentration')->nullable()->default(null); // Suspended particulate matter (SPM) conc. [mg/l]
            $table->string('salinity')->nullable()->default(null); // Salinity [psu]
            $table->string('doc')->nullable()->default(null); // Dissolved organic carbon (DOC) [mg/l]
            $table->string('hardness')->nullable()->default(null); // Hardness [mg/l] CaCO3
            $table->string('conductivity')->nullable()->default(null); // Conductivity µS/cm
            $table->string('total_organic_carbon')->nullable()->default(null); // Total organic carbon            
            // Sediment
            $table->foreignId('fraction_id')->nullable()->default(null)->references('id')->on('list_fractions'); // Fraction
            $table->string('fraction_other')->nullable()->default(null); // Fraction - other
            $table->string('depth')->nullable()->default(null); // Depth [m]     
            $table->string('surface_of_sampled_area')->nullable()->default(null); // Surface of the sampled area [cm2]
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->string('dilution_factor_in_use_category')->nullable()->default(null); // Dilution factor in the Use category
            $table->foreignId('treatment_before_use_id')->nullable()->default(null)->references('id')->on('list_treatment_before_uses'); // Treatment before use
            $table->string('treatment_before_use_other')->nullable()->default(null); // Treatment before use - other          
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });
        // dct_analysis_water_biota
        Schema::create('empodat_water_biotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_group_id')->nullable()->default(null)->references('id')->on('list_species_groups'); // Species group
            $table->string('species_group_other')->nullable()->default(null); // Species group - other
            // nie su ciselniky
            //???$table->foreignId('species_category_id')->nullable()->default(null)->references('id')->on('list_species_categories'); // Species category
            //???$table->string('species_category_other')->nullable()->default(null); // Species category - other
            $table->string('species_category')->nullable()->default(null); // Species category
            $table->string('species_name_in_latin')->nullable()->default(null); // Species name (in Latin)
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->foreignId('reuse_context_id')->nullable()->default(null)->references('id')->on('list_reuse_contexts'); // Reuse context
            $table->foreignId('basis_of_measurement_id')->nullable()->default(null)->references('id')->on('list_basis_of_measurements'); // Basis of measurement
            $table->string('basis_of_measurement_other')->nullable()->default(null); // Basis of measurement - other
            $table->foreignId('tissue_id')->nullable()->default(null)->references('id')->on('list_tissues'); // Tissue
            $table->string('tissue_other')->nullable()->default(null); // Tissue - other
            $table->foreignId('packing_material_id')->nullable()->default(null)->references('id')->on('list_packing_materials'); // Packing material of samples
            $table->string('packing_material_other')->nullable()->default(null); // Packing material of samples - other
            $table->string('biota_size')->nullable()->default(null); // Biota size [mm]
            $table->string('biota_length')->nullable()->default(null); // Biota length [mm]
            $table->string('biota_weight')->nullable()->default(null); // Biota weight [kg]
            $table->foreignId('biota_sex_id')->nullable()->default(null)->references('id')->on('list_biota_sexs'); // Biota sex
            $table->string('biota_age')->nullable()->default(null); // Biota age (years)
            $table->string('agegroup')->nullable()->default(null); // Agegroup
            $table->string('number_of_pooled_individuals')->nullable()->default(null); // No. of pooled individuals
            $table->foreignId('geographic_range_id')->nullable()->default(null)->references('id')->on('list_geographic_ranges'); // Geographic range of pooled individuals
            $table->string('water_content_of_tissue')->nullable()->default(null); // Water content of tissue [%]
            $table->string('fat_content_of_tissue')->nullable()->default(null); // Fat content of tissue [%]
            $table->string('was_species_alive_terrestrial_and_marine_mammals')->nullable()->default(null); // Was species alive (terrestrial and marine mammals)?
            $table->foreignId('medical_treatment_id')->nullable()->default(null)->references('id')->on('list_medical_treatments'); // Did a receive medical treatment prior to death?
            $table->foreignId('was_the_species_euthanised_id')->nullable()->default(null)->references('id')->on('list_was_the_species_euthaniseds'); // Was the species euthanised?
            $table->string('cause_of_death')->nullable()->default(null); // Cause of death
            $table->string('year_of_death')->nullable()->default(null); // Year of death - YYYY
            $table->foreignId('nutrition_condition_id')->nullable()->default(null)->references('id')->on('list_nutrition_conditions'); // Nutrition condition
            $table->string('remark1')->nullable()->default(null); // REMARK_1 - years of pooled samples etc.
            $table->string('remark2')->nullable()->default(null); // REMARK_2 - state of autolysis etc.          
            $table->timestamps();
        });   
        // dct_analysis_water_air
        Schema::create('empodat_water_airs', function (Blueprint $table) {
            $table->id();       
            $table->foreignId('location_id')->nullable()->default(null)->references('id')->on('list_locations'); // Location
            $table->string('location_other')->nullable()->default(null); // Location - other
            $table->foreignId('proxy_pressure_id')->constrained()->nullable()->default(null)->references('id')->on('list_proxy_pressures'); // Proxy pressure
            $table->string('proxy_pressure_other')->nullable()->default(null); // Proxy pressure - other
            // Outdoor Environment - Ambient Air / Air Emissions
            $table->string('flow_rate')->nullable()->default(null); // Flow rate [m3/h]
            $table->foreignId('sampling_method_id')->nullable()->default(null)->references('id')->on('list_sampling_methodXs'); // Sampling method
            $table->string('sampling_method_other')->nullable()->default(null); // Other
            $table->foreignId('sampling_collection_device_id')->nullable()->default(null)->references('id')->on('list_sampling_collection_deviceXs'); // Sampling collection device
            $table->string('sampling_collection_device_other')->nullable()->default(null); // Other
            $table->string('sampling_height_above_ground_level')->nullable()->default(null);  // Sampling height above ground level [m] 
            $table->string('sampling_height_above_sea_level')->nullable()->default(null);  // Sampling height above sea level [m] 
            $table->string('temperature')->nullable()->default(null); // Temperature [°C]
            $table->string('barometric_pressure')->nullable()->default(null); // Barometric pressure [kPA]
            $table->string('relative_humidity')->nullable()->default(null); // Relative humidity [%]
            $table->string('wind_speed')->nullable()->default(null); // Wind speed [km/h]
            $table->string('wind_direction')->nullable()->default(null); // Wind direction
            $table->string('load')->nullable()->default(null); // Load  [g/s]
            $table->foreignId('air_filtration_system_id')->nullable()->default(null)->references('id')->on('list_air_filtration_systems'); // Air filtration system
            $table->string('air_filtration_system_other')->nullable()->default(null); // Other
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });    
        // dct_analysis_water_soil
        Schema::create('empodat_water_soils', function (Blueprint $table) {
            $table->id();  
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            // Soil
            $table->foreignId('soil_texture_id')->nullable()->default(null)->references('id')->on('list_soil_textures'); // Soil texture
            $table->string('soil_texture_other')->nullable()->default(null); // Soil texture - other
            $table->foreignId('grain_size_distribution_id')->nullable()->default(null)->references('id')->on('list_grain_size_distributions'); // Grain size distribution [mm]
            $table->string('grain_size_distribution_other')->nullable()->default(null); // Grain size distribution - other
            $table->foreignId('use_category_id')->nullable()->default(null)->references('id')->on('list_use_categories'); // Use category
            $table->string('use_category_other')->nullable()->default(null); // Use category - other
            $table->string('dilution_factor_in_use_category')->nullable()->default(null); // Dilution factor in the Use category
            $table->foreignId('treatment_before_use_id')->nullable()->default(null)->references('id')->on('list_treatment_before_uses'); // Treatment before use
            $table->string('treatment_before_use_other')->nullable()->default(null); // Treatment before use - other          
            $table->string('concentration_normalised_for_particle_size')->nullable()->default(null); // Concentration normalised for the particle size
            $table->foreignId('type_of_depth_sampling_id')->nullable()->default(null)->references('id')->on('list_depths'); // Type of depth sampling            
            $table->string('type_of_depth_sampling_other')->nullable()->default(null); // Type of depth sampling - other
            $table->string('depth')->nullable()->default(null); // Depth [m]     
            $table->string('ph')->nullable()->default(null); // pH
            $table->string('dry_wet_ratio')->nullable()->default(null); // Dry Wet Ratio [%]
            $table->string('total_organic_carbon')->nullable()->default(null); // Total organic carbon [% of total dry weight]
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });  
        // dct_analysis_water_spm
        Schema::create('empodat_water_spms', function (Blueprint $table) {
            $table->id();
            $table->string('spm_concentration')->nullable()->default(null); // Suspended particulate matter (SPM) concentration [mg/l]
            $table->string('river_name')->nullable()->default(null); // Name of river / estuary / lake / reservoir / sea
            $table->string('river_basin_name')->nullable()->default(null); // River Basin / Sea Region Name
            $table->string('river_km')->nullable()->default(null); // River-km
            $table->foreignId('proxy_pressure_id')->constrained()->nullable()->default(null)->references('id')->on('list_proxy_pressures'); // Proxy pressure
            $table->string('proxy_pressure_other')->nullable()->default(null); // Proxy pressure - other
            $table->foreignId('type_of_depth_sampling_id')->nullable()->default(null)->references('id')->on('list_depths'); // Type of depth sampling            
            $table->string('type_of_depth_sampling_other')->nullable()->default(null); // Type of depth sampling - other
            $table->string('depth')->nullable()->default(null); // Depth [m]     
            $table->string('collection_distance')->nullable()->default(null); // Collection distance
            $table->string('total_organic_carbon')->nullable()->default(null); // Total organic carbon [% of total dry weight]
            $table->string('remark1')->nullable()->default(null); // REMARK_1
            $table->string('remark2')->nullable()->default(null); // REMARK_2            
            $table->timestamps();
        });  

    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('empodat_stations');
        Schema::dropIfExists('empodat_minor');
    }
};
