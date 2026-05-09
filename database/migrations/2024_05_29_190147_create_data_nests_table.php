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
            // $table->string('id_laboratory')->nullable()->default(null); // Laboratory ID  - deprecated
            // $table->foreignId('organisation_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_organisations'); // Organisation
            $table->foreignId('organisation_id')->nullable()->default(null)->references('id')->on('list_data_source_organisations'); // Organisation
            // Question: 2*1:N OR 1*N:M ???
            // $table->foreignId('laboratory1_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 1
            // $table->foreignId('laboratory2_id')->constrained()->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 2
            $table->foreignId('laboratory1_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 1
            $table->foreignId('laboratory2_id')->nullable()->default(null)->references('id')->on('list_data_source_laboratories'); // Laboratory 2
            $table->string('author')->nullable()->default(null); // Contact person - First name(s) Family name
            $table->string('email')->nullable()->default(null); // Contact person - e-mail
            $table->text('reference1')->nullable()->default(null); // Reference 1 (reference - website/DOI/etc.)
            $table->text('reference2')->nullable()->default(null); // Reference 2 (reference - website/DOI/etc.)
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_minor');
    }
};
