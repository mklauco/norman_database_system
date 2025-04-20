<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bioassay_monitor_sample_data', function (Blueprint $table) {
            $table->id(); // This replaces m_sd_id

            // Foreign key to data source
            $table->foreignId('m_ds_id')->nullable()->default(null)
                ->constrained('bioassay_monitor_data_source')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('auxiliary_sample_identification');

            // Foreign key to country
            $table->unsignedTinyInteger('m_country_id')->nullable()->default(null);
            $table->foreign('m_country_id')
                ->references('id')
                ->on('monitor_x_country')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('country_other');
            $table->text('station_name');
            $table->text('station_national_code');
            $table->text('station_ec_code_wise');
            $table->text('station_ec_code_other');
            $table->text('other_station_code');
            $table->text('longitude');
            $table->text('latitude');

            // Foreign key to precision coordinates
            $table->unsignedTinyInteger('m_precision_coordinates_id')->nullable()->default(null);
            $table->foreign('m_precision_coordinates_id')
                ->references('id')
                ->on('monitor_x_precision_coordinates')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('altitude');

            // Foreign key to sample matrix
            $table->unsignedTinyInteger('m_sample_matrix_id')->nullable()->default(null);
            $table->foreign('m_sample_matrix_id')
                ->references('id')
                ->on('monitor_x_sample_matrix')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('sample_matrix_other');

            // Foreign key to type sampling
            $table->unsignedTinyInteger('m_type_sampling_id')->nullable()->default(null);
            $table->foreign('m_type_sampling_id')
                ->references('id')
                ->on('monitor_x_type_sampling')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // Foreign key to sampling technique
            $table->unsignedTinyInteger('m_sampling_technique_id')->nullable()->default(null);
            $table->foreign('m_sampling_technique_id')
                ->references('id')
                ->on('monitor_x_sampling_technique')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('sampling_technique_other');
            $table->tinyInteger('sampling_start_day');
            $table->tinyInteger('sampling_start_month');
            $table->smallInteger('sampling_start_year');
            $table->tinyInteger('sampling_start_hour');
            $table->tinyInteger('sampling_start_minute');
            $table->smallInteger('sampling_duration_days');
            $table->decimal('sampling_duration_hours', 10, 2);

            // Foreign key to fraction
            $table->unsignedTinyInteger('m_fraction_id')->nullable()->default(null);
            $table->foreign('m_fraction_id')
                ->references('id')
                ->on('monitor_x_fraction')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('fraction_other');
            $table->text('name');
            $table->text('river_basin_name');
            $table->text('river_km');

            // Foreign key to proxy pressures
            $table->unsignedTinyInteger('m_proxy_pressures_id')->nullable()->default(null);
            $table->foreign('m_proxy_pressures_id')
                ->references('id')
                ->on('monitor_x_proxy_pressures')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->text('proxy_pressures_other');
            $table->text('sampling_depth');
            $table->text('surface_area');
            $table->text('salinity_mean');
            $table->text('spm_concentration');
            $table->text('ph');
            $table->text('temperature');
            $table->text('dissolved_organic_carbon');
            $table->text('conductivity');
            $table->text('guideline');
            $table->text('reference');

            // Add timestamps
            $table->timestamps();

            // Adding user tracking (commented out as per your original code)
            // $table->foreignId('created_by')->nullable()->constrained('users');
            // $table->foreignId('updated_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bioassay_monitor_sample_data');
    }
};
