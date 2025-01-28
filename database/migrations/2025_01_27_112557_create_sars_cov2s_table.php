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
        Schema::create('sars_cov2s', function (Blueprint $table) {
            $table->id();
            $table->string('type_of_data')->nullable()->default(null); // Type of data 
            $table->string('data_provider')->nullable()->default(null); // Data provider 
            $table->string('contact_person')->nullable()->default(null); // Contact person 
            $table->string('address_of_contact')->nullable()->default(null); // Address of contact 
            $table->string('email')->nullable()->default(null); // E-mail 
            $table->string('laboratory')->nullable()->default(null); // Laboratory: 


            $table->string('name_of_country')->nullable()->default(null); // Name of country 
            $table->string('name_of_city')->nullable()->default(null); // Name of the City / Municipality 
            $table->string('station_name')->nullable()->default(null); // Station name and codes: Name
            $table->string('national_code')->nullable()->default(null); // - National code
            $table->string('relevant_ec_code_wise')->nullable()->default(null); // - Relevant EC code – WISE
            $table->string('relevant_ec_code_other')->nullable()->default(null); // - Relevant EC code - Other
            $table->string('other_code')->nullable()->default(null); // - Other code
            $table->string('latitude')->nullable()->default(null); // Longitude: East/West
            $table->string('latitude_d')->nullable()->default(null); // - °
            $table->string('latitude_m')->nullable()->default(null); // - `
            $table->string('latitude_s')->nullable()->default(null); // - ``
            $table->float('latitude_decimal', precision: 53)->nullable()->default(null); // - Decimal
            $table->string('longitude')->nullable()->default(null); // Latitude: North/South
            $table->string('longitude_d')->nullable()->default(null); // - °
            $table->string('longitude_m')->nullable()->default(null); // - `
            $table->string('longitude_s')->nullable()->default(null); // - ``
            $table->float('longitude_decimal', precision: 53)->nullable()->default(null); // - Decimal
            $table->string('altitude')->nullable()->default(null); // Altitude: [m]
            $table->string('design_capacity')->nullable()->default(null); // Design capacity: [P.E.]
            $table->string('population_served')->nullable()->default(null); // Population served : [P.E.]
            $table->string('catchment_size')->nullable()->default(null); // Catchment size: [m2]
            $table->string('gdp')->nullable()->default(null); // GDP: [EUR]
            $table->string('people_positive')->nullable()->default(null); // Prevalence data: No. of people SARS-CoV-2 POSITIVE
            $table->string('people_recovered')->nullable()->default(null); // - No. of people RECOVERED
            $table->string('people_positive_past')->nullable()->default(null); // - No. of people SARS-CoV-2 POSITIVE_PAST
            $table->string('people_recovered_past')->nullable()->default(null); // - No. of people RECOVERED_PAST

            $table->string('sample_matrix')->nullable()->default(null); // Sample matrix: Untreated wastewater
            $table->string('sample_from_hour')->nullable()->default(null); // Sampling date FROM: Hour [HH:MM]
            $table->tinyInteger('sample_from_day')->nullable()->default(null); // - Day [DD]
            $table->tinyInteger('sample_from_month')->nullable()->default(null); // - Month [MM]
            $table->year('sample_from_year')->nullable()->default(null); // - Year [YYYY]
            $table->string('sample_to_hour')->nullable()->default(null); // Sampling date TO: Hour [HH:MM]
            $table->tinyInteger('sample_to_day')->nullable()->default(null); // - Day [DD]
            $table->tinyInteger('sample_to_month')->nullable()->default(null); // - Month [MM]
            $table->year('sample_to_year')->nullable()->default(null); // - Year [YYYY]
            $table->string('type_of_sample')->nullable()->default(null); // Sampling procedure: Type of sample
            $table->string('type_of_composite_sample')->nullable()->default(null); // - Type of sample (if composite)
            $table->string('sample_interval')->nullable()->default(null); // - Interval (if composite)
            $table->string('flow_total')->nullable()->default(null); // Flow: Total [m³]
            $table->string('flow_minimum')->nullable()->default(null); // - Minimum [m³/h]
            $table->string('flow_maximum')->nullable()->default(null); // - Maximum [m³/h]
            $table->string('temperature')->nullable()->default(null); // Inflow characteristics: Temperature [°C]
            $table->string('cod')->nullable()->default(null); // - COD [mg/L]
            $table->string('total_n_nh4_n')->nullable()->default(null); // - Total N / NH4-N [mg N/L]
            $table->string('tss')->nullable()->default(null); // - TSS [mg/L]
            $table->string('dry_weather_conditions')->nullable()->default(null); // Rain: Dry weather conditions
            $table->string('last_rain_event')->nullable()->default(null); // - Last rain event [No. of days]

            $table->string('associated_phenotype')->nullable()->default(null); // Determinant: Associated phenotype
            $table->string('genetic_marker')->nullable()->default(null); // - Genetic marker?
            $table->string('date_of_sample_preparation')->nullable()->default(null); // Sample preparation: Date of sample preparation [DD/MM/YYYY]
            $table->string('storage_of_sample')->nullable()->default(null); // - Storage of sample - temperature [°C]
            $table->string('volume_of_sample')->nullable()->default(null); // - Volume of sample [mL]
            $table->string('internal_standard_used1')->nullable()->default(null); // - Internal standard used? [Yes/No - text]
            $table->string('method_used_for_sample_preparation')->nullable()->default(null); // - Method used for sample preparation
            $table->string('date_of_rna_extraction')->nullable()->default(null); // RNA extraction: Date of RNA extraction [DD/MM/YYYY]
            $table->string('method_used_for_rna_extraction')->nullable()->default(null); // - Method used for RNA extraction
            $table->string('internal_standard_used2')->nullable()->default(null); // - Internal standard used? [Yes/No - text]
            $table->string('rna1')->nullable()->default(null); // - RNA [μL]
            $table->string('rna2')->nullable()->default(null); // - RNA [ng/μL]
            $table->string('replicates1')->nullable()->default(null); // QA: Replicates? [number]
            $table->string('analytical_method_type')->nullable()->default(null); // Analytical method: Type
            $table->string('analytical_method_type_other')->nullable()->default(null); // - If OTHER - specify
            $table->string('date_of_analysis')->nullable()->default(null); // - Date of analysis [DD/MM/YYYY]
            $table->string('lod1')->nullable()->default(null); // - LoD [number of copies/mL of sample]
            $table->string('lod2')->nullable()->default(null); // - LoD [number of copies/ng of RNA]
            $table->string('loq1')->nullable()->default(null); // - LoQ [number of copies/mL of sample]
            $table->string('loq2')->nullable()->default(null); // - LoQ [number of copies/ng of RNA]
            $table->string('uncertainty_of_the_quantification')->nullable()->default(null); // - Uncertainty of the quantification [%]
            $table->string('efficiency')->nullable()->default(null); // - Efficiency
            $table->string('rna3')->nullable()->default(null); // QA: RNA [ng/μL]
            $table->string('pos_control_used')->nullable()->default(null); // - Pos-control used
            $table->string('replicates2')->nullable()->default(null); // - Replicates? [number]
            $table->string('ct')->nullable()->default(null); // Concentration/Abundance: Ct # [number]
            $table->string('gene1')->nullable()->default(null); // - Gene copy [number/mL of sample]
            $table->string('gene2')->nullable()->default(null); // - Gene copy [number/ng of RNA]
            $table->string('comment')->nullable()->default(null); // - Text [max. 255 characters]
            $table->float('latitude_decimal_show', precision: 53)->nullable()->default(null); //
            $table->float('longitude_decimal_show', precision: 53)->nullable()->default(null); //
            $table->foreignId('sars_cov2_source_id')->nullable()->default(null)->references('id')->on('sars_cov2_source'); // Country
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sars_cov2s');
    }
};
