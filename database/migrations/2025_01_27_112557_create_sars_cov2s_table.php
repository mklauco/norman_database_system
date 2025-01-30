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
            $table->text('type_of_data')->nullable()->default(null); // Type of data 
            $table->text('data_provider')->nullable()->default(null); // Data provider 
            $table->text('contact_person')->nullable()->default(null); // Contact person 
            $table->text('address_of_contact')->nullable()->default(null); // Address of contact 
            $table->text('email')->nullable()->default(null); // E-mail 
            $table->text('laboratory')->nullable()->default(null); // Laboratory: 

            $table->text('name_of_country')->nullable()->default(null); // Name of country 
            $table->text('name_of_city')->nullable()->default(null); // Name of the City / Municipality 
            $table->text('station_name')->nullable()->default(null); // Station name and codes: Name
            $table->text('national_code')->nullable()->default(null); // - National code
            $table->text('relevant_ec_code_wise')->nullable()->default(null); // - Relevant EC code – WISE
            $table->text('relevant_ec_code_other')->nullable()->default(null); // - Relevant EC code - Other
            $table->text('other_code')->nullable()->default(null); // - Other code
            $table->text('latitude')->nullable()->default(null); // Longitude: East/West
            $table->text('latitude_d')->nullable()->default(null); // - °
            $table->text('latitude_m')->nullable()->default(null); // - `
            $table->text('latitude_s')->nullable()->default(null); // - ``
            $table->float('latitude_decimal', precision: 53)->nullable()->default(null); // - Decimal
            $table->text('longitude')->nullable()->default(null); // Latitude: North/South
            $table->text('longitude_d')->nullable()->default(null); // - °
            $table->text('longitude_m')->nullable()->default(null); // - `
            $table->text('longitude_s')->nullable()->default(null); // - ``
            $table->float('longitude_decimal', precision: 53)->nullable()->default(null); // - Decimal
            $table->text('altitude')->nullable()->default(null); // Altitude: [m]
            $table->text('design_capacity')->nullable()->default(null); // Design capacity: [P.E.]
            $table->text('population_served')->nullable()->default(null); // Population served : [P.E.]
            $table->text('catchment_size')->nullable()->default(null); // Catchment size: [m2]
            $table->text('gdp')->nullable()->default(null); // GDP: [EUR]
            $table->text('people_positive')->nullable()->default(null); // Prevalence data: No. of people SARS-CoV-2 POSITIVE
            $table->text('people_recovered')->nullable()->default(null); // - No. of people RECOVERED
            $table->text('people_positive_past')->nullable()->default(null); // - No. of people SARS-CoV-2 POSITIVE_PAST
            $table->text('people_recovered_past')->nullable()->default(null); // - No. of people RECOVERED_PAST

            $table->text('sample_matrix')->nullable()->default(null); // Sample matrix: Untreated wastewater
            $table->text('sample_from_hour')->nullable()->default(null); // Sampling date FROM: Hour [HH:MM]
            $table->tinyInteger('sample_from_day')->nullable()->default(null); // - Day [DD]
            $table->tinyInteger('sample_from_month')->nullable()->default(null); // - Month [MM]
            $table->year('sample_from_year')->nullable()->default(null); // - Year [YYYY]
            $table->text('sample_to_hour')->nullable()->default(null); // Sampling date TO: Hour [HH:MM]
            $table->tinyInteger('sample_to_day')->nullable()->default(null); // - Day [DD]
            $table->tinyInteger('sample_to_month')->nullable()->default(null); // - Month [MM]
            $table->year('sample_to_year')->nullable()->default(null); // - Year [YYYY]
            $table->text('type_of_sample')->nullable()->default(null); // Sampling procedure: Type of sample
            $table->text('type_of_composite_sample')->nullable()->default(null); // - Type of sample (if composite)
            $table->text('sample_interval')->nullable()->default(null); // - Interval (if composite)
            $table->text('flow_total')->nullable()->default(null); // Flow: Total [m³]
            $table->text('flow_minimum')->nullable()->default(null); // - Minimum [m³/h]
            $table->text('flow_maximum')->nullable()->default(null); // - Maximum [m³/h]
            $table->text('temperature')->nullable()->default(null); // Inflow characteristics: Temperature [°C]
            $table->text('cod')->nullable()->default(null); // - COD [mg/L]
            $table->text('total_n_nh4_n')->nullable()->default(null); // - Total N / NH4-N [mg N/L]
            $table->text('tss')->nullable()->default(null); // - TSS [mg/L]
            $table->text('dry_weather_conditions')->nullable()->default(null); // Rain: Dry weather conditions
            $table->text('last_rain_event')->nullable()->default(null); // - Last rain event [No. of days]

            $table->text('associated_phenotype')->nullable()->default(null); // Determinant: Associated phenotype
            $table->text('genetic_marker')->nullable()->default(null); // - Genetic marker?
            $table->text('date_of_sample_preparation')->nullable()->default(null); // Sample preparation: Date of sample preparation [DD/MM/YYYY]
            $table->text('storage_of_sample')->nullable()->default(null); // - Storage of sample - temperature [°C]
            $table->text('volume_of_sample')->nullable()->default(null); // - Volume of sample [mL]
            $table->text('internal_standard_used1')->nullable()->default(null); // - Internal standard used? [Yes/No - text]
            $table->text('method_used_for_sample_preparation')->nullable()->default(null); // - Method used for sample preparation
            $table->text('date_of_rna_extraction')->nullable()->default(null); // RNA extraction: Date of RNA extraction [DD/MM/YYYY]
            $table->text('method_used_for_rna_extraction')->nullable()->default(null); // - Method used for RNA extraction
            $table->text('internal_standard_used2')->nullable()->default(null); // - Internal standard used? [Yes/No - text]
            $table->text('rna1')->nullable()->default(null); // - RNA [μL]
            $table->text('rna2')->nullable()->default(null); // - RNA [ng/μL]
            $table->text('replicates1')->nullable()->default(null); // QA: Replicates? [number]
            $table->text('analytical_method_type')->nullable()->default(null); // Analytical method: Type
            $table->text('analytical_method_type_other')->nullable()->default(null); // - If OTHER - specify
            $table->text('date_of_analysis')->nullable()->default(null); // - Date of analysis [DD/MM/YYYY]
            $table->text('lod1')->nullable()->default(null); // - LoD [number of copies/mL of sample]
            $table->text('lod2')->nullable()->default(null); // - LoD [number of copies/ng of RNA]
            $table->text('loq1')->nullable()->default(null); // - LoQ [number of copies/mL of sample]
            $table->text('loq2')->nullable()->default(null); // - LoQ [number of copies/ng of RNA]
            $table->text('uncertainty_of_the_quantification')->nullable()->default(null); // - Uncertainty of the quantification [%]
            $table->text('efficiency')->nullable()->default(null); // - Efficiency
            $table->text('rna3')->nullable()->default(null); // QA: RNA [ng/μL]
            $table->text('pos_control_used')->nullable()->default(null); // - Pos-control used
            $table->text('replicates2')->nullable()->default(null); // - Replicates? [number]
            $table->text('ct')->nullable()->default(null); // Concentration/Abundance: Ct # [number]
            $table->text('gene1')->nullable()->default(null); // - Gene copy [number/mL of sample]
            $table->text('gene2')->nullable()->default(null); // - Gene copy [number/ng of RNA]
            $table->text('comment')->nullable()->default(null); // - Text [max. 255 characters]
            $table->float('latitude_decimal_show', precision: 53)->nullable()->default(null); //
            $table->float('longitude_decimal_show', precision: 53)->nullable()->default(null); //
            $table->foreignId('sars_cov2_source_id')->nullable()->default(null)->references('id')->on('sars_cov2_sources'); // Country
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
