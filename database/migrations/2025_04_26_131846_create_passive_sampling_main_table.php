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
        Schema::create('passive_sampling_main', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sus_id');
            $table->char('country_id', 2);
            $table->char('country_other', 2)->nullable();
            $table->string('station_name', 255)->nullable();
            $table->text('short_sample_code')->nullable();
            $table->text('sample_code')->nullable();
            $table->text('provider_code')->nullable();
            $table->text('national_code')->nullable();
            $table->string('code_ec_wise', 255)->nullable();
            $table->string('code_ec_other', 255)->nullable();
            $table->string('code_other', 255)->nullable();
            $table->text('specific_locations')->nullable();
            $table->string('longitude_decimal', 20)->nullable();
            $table->string('latitude_decimal', 20)->nullable();
            $table->unsignedTinyInteger('dpc_id')->nullable();
            $table->string('altitude', 20)->nullable();
            $table->unsignedTinyInteger('dpr_id')->nullable();
            $table->string('dpr_other', 255)->nullable();
            $table->string('ds_passive_sampling_stretch', 255)->nullable();
            $table->mediumText('ds_stretch_start_and_end');
            $table->string('ds_longitude_start_point_decimal', 20)->nullable();
            $table->string('ds_latitude_start_point_decimal', 20)->nullable();
            $table->string('ds_longitude_end_point_decimal', 20)->nullable();
            $table->string('ds_latitude_end_point_decimal', 20)->nullable();
            $table->unsignedTinyInteger('ds_dpc_id')->nullable();
            $table->string('ds_altitude', 20)->nullable();
            $table->unsignedTinyInteger('ds_dpr_id')->nullable();
            $table->string('ds_dpr_other', 255)->nullable();
            $table->unsignedTinyInteger('matrix_id')->nullable();
            $table->string('matrix_other', 30);
            $table->tinyInteger('type_sampling_id');
            $table->string('type_sampling_other', 255);
            $table->unsignedTinyInteger('passive_sampler_id')->nullable();
            $table->string('passive_sampler_other', 255);
            $table->unsignedTinyInteger('sampler_type_id')->nullable();
            $table->string('sampler_type_other', 255)->nullable();
            $table->string('sampler_mass', 20);
            $table->string('sampler_surface_area', 20);
            $table->tinyInteger('date_sampling_start_day')->nullable();
            $table->tinyInteger('date_sampling_start_month')->nullable();
            $table->year('date_sampling_start_year');
            $table->string('exposure_time_days', 20);
            $table->string('exposure_time_hours', 20);
            $table->date('date_of_analysis')->nullable();
            $table->time('time_of_analysis')->nullable();
            $table->string('name', 255)->nullable();
            $table->unsignedTinyInteger('basin_name_id')->nullable();
            $table->string('basin_name_other', 255)->nullable();
            $table->tinyInteger('dts_id')->nullable();
            $table->string('dts_other', 255)->nullable();
            $table->tinyInteger('dtm_id')->nullable();
            $table->string('dtm_other', 255)->nullable();
            $table->unsignedTinyInteger('dic_id');
            $table->float('concentration_value');
            $table->string('unit', 20);
            $table->string('title_of_project', 255)->nullable();
            $table->string('ph', 255)->nullable();
            $table->string('temperature', 255)->nullable();
            $table->string('spm_conc', 255)->nullable();
            $table->string('salinity', 255)->nullable();
            $table->string('doc', 255)->nullable();
            $table->string('hardness', 255)->nullable();
            $table->string('o2_1', 255)->nullable();
            $table->string('o2_2', 255)->nullable();
            $table->string('bod5', 255)->nullable();
            $table->string('h2s', 255)->nullable();
            $table->string('p_po4', 255)->nullable();
            $table->string('n_no2', 255)->nullable();
            $table->string('tss', 255)->nullable();
            $table->string('p_total', 255)->nullable();
            $table->string('n_no3', 255)->nullable();
            $table->string('n_total', 255)->nullable();
            $table->string('remark_1', 255)->nullable();
            $table->string('remark_2', 255)->nullable();
            $table->unsignedInteger('am_id');
            $table->unsignedInteger('org_id');
            $table->string('orig_compound', 255);
            $table->string('orig_cas_no', 255);
            $table->string('p_determinand_id', 255);
            $table->mediumText('p_a_exposure_time');
            $table->mediumText('p_a_cruise_dates');
            $table->mediumText('p_a_river_km');
            $table->mediumText('p_a_sampler_sheets_disks_nr');
            $table->mediumText('p_a_sample_code');
            $table->timestamps();
            
            // Create indexes
            $table->index('sus_id');
            $table->index('country_id');
            $table->index('country_other');
            $table->index('dpc_id');
            $table->index('dpr_id');
            $table->index('ds_dpc_id');
            $table->index('ds_dpr_id');
            $table->index('matrix_id');
            $table->index('type_sampling_id');
            $table->index('passive_sampler_id');
            $table->index('sampler_type_id');
            $table->index('basin_name_id');
            $table->index('dts_id');
            $table->index('dtm_id');
            $table->index('dic_id');
            $table->index('am_id');
            $table->index('org_id');
            $table->index('p_determinand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passive_sampling_main');
    }
};