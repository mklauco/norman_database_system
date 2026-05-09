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
        Schema::create('ecotox_derivation', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('der_id');
            $table->text('vote_matrix')->nullable()->default(null)->comment('1-fresh, 2-marine');
            $table->text('acute_or_chronic')->nullable()->default(null);
            $table->tinyText('der_concentration')->nullable()->default(null);
            $table->integer('der_color')->nullable()->default(null);
            $table->integer('der_active')->nullable()->default(null);
            $table->dateTime('der_date')->nullable()->default(null);
            $table->integer('der_save')->nullable()->default(null);
            $table->integer('der_base')->nullable()->default(null);
            $table->integer('der_order')->nullable()->default(null);
            $table->string('norman_pnec_id', 30)->nullable()->default(null)->comment('NORMAN Dataset ID');
            $table->text('norman_dataset_id')->nullable()->default(null)->comment('NORMAN PNEC ID');
            $table->text('data_source_name')->nullable()->default(null)->comment('Data source name');
            $table->text('data_source_link')->nullable()->default(null)->comment('Data source link');
            $table->text('data_source_id')->nullable()->default(null)->comment('Data source ID');
            $table->text('study_title')->nullable()->default(null)->comment('Study title');
            $table->text('authors')->nullable()->default(null)->comment('Author(s)');
            $table->text('date')->nullable()->default(null)->comment('Year >> 22.5.2020 zmenene na Date');
            $table->text('bibliographic_source')->nullable()->default(null)->comment('Bibliographic source');
            $table->text('dossier_available')->nullable()->default(null)->comment('Dossier available?');
            $table->unsignedInteger('sus_id')->nullable()->default(null)->comment('NORMAN Substance ID');
            $table->text('cas')->nullable()->default(null)->comment('CAS Number');
            $table->text('substance_name')->nullable()->default(null)->comment('Substance Name');
            $table->text('country_or_region')->nullable()->default(null)->comment('Country or Region');
            $table->text('institution')->nullable()->default(null)->comment('Institution / Authority');
            $table->text('matrix_habitat')->nullable()->default(null)->comment('Compartment');
            $table->text('legal_status')->nullable()->default(null)->comment('Legal status');
            $table->text('protected_asset')->nullable()->default(null)->comment('Protected asset');
            $table->text('pnec_type')->nullable()->default(null)->comment('PNEC type');
            $table->text('monitoring_frequency')->nullable()->default(null)->comment('Monitoring Frequency');
            $table->text('concentration_specification')->nullable()->default(null)->comment('Concentration specification');
            $table->text('taxonomic_group')->nullable()->default(null)->comment('Taxonomic group');
            $table->text('scientific_name')->nullable()->default(null)->comment('Species name');
            $table->text('endpoint')->nullable()->default(null)->comment('Key endpoint');
            $table->text('effect_measurement')->nullable()->default(null)->comment('Effect');
            $table->text('duration')->nullable()->default(null)->comment('Duration');
            $table->text('exposure_regime')->nullable()->default(null)->comment('Exposure Regime');
            $table->text('measured_or_nominal')->nullable()->default(null)->comment('Measured or nominal concentrations');
            $table->text('test_item')->nullable()->default(null)->comment('Test item');
            $table->text('purity')->nullable()->default(null)->comment('Purity [%] ');
            $table->text('AF')->nullable()->default(null)->comment('Applied AF');
            $table->text('justification')->nullable()->default(null)->comment('Justification');
            $table->text('derivation_method')->nullable()->default(null)->comment('Derivation method');
            $table->text('value')->nullable()->default(null)->comment('Value');
            $table->string('ecotox_id', 30)->nullable()->default(null)->comment('Biotest ID');
            $table->text('remarks')->nullable()->default(null)->comment('Remarks');
            $table->unsignedInteger('reliability_study')->nullable()->default(null)->comment('Reliabilty of the key study');
            $table->text('reliability_score')->nullable()->default(null)->comment('Reliability score system used');
            $table->text('institution_study')->nullable()->default(null)->comment('Institution (key study)');
            $table->text('vote')->nullable()->default(null)->comment('Vote');
            $table->text('regulatory_context')->nullable()->default(null)->comment('Regulatory context');
            $table->text('concentration_qualifier')->nullable()->default(null)->comment('Concentration qualifier');
            $table->text('concentration_value')->nullable()->default(null)->comment('Concentration value');
            $table->text('link_directive')->nullable()->default(null);
            $table->char('use_study', 1)->nullable()->default(null)->comment('CCA >> all are Y');
            $table->unsignedInteger('der_editor')->nullable()->default(null)->comment('CCA >> Editor alias');
            $table->unsignedTinyInteger('color_tx')->nullable()->default(null)->comment('CCA >> Edit mode color');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('sus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_derivation');
    }
};
