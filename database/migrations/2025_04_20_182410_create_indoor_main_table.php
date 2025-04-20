<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndoorMainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indoor_main', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sus_id')->zerofill(8);
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances');

            
            // Countries
            $table->char('country', 2);
            $table->char('country_other', 2);
            // $table->foreign('country')->references('id')->on('indoor_data_country')->onDelete('restrict');
            // $table->foreign('country_other')->references('id')->on('indoor_data_country_other')->onDelete('restrict');
            
            $table->string('station_name', 255);
            $table->string('national_name', 255);
            $table->text('short_sample_code');
            $table->text('sample_code');
            $table->text('provider_code');
            $table->string('code_ec', 255);
            $table->string('code_other', 255);
            
            // Location data
            $table->string('east_west', 10);
            $table->string('longitude_d', 10);
            $table->string('longitude_m', 10);
            $table->string('longitude_s', 10);
            $table->string('longitude_decimal', 20);
            $table->string('north_south', 10);
            $table->string('latitude_d', 10);
            $table->string('latitude_m', 10);
            $table->string('latitude_s', 10);
            $table->string('latitude_decimal', 20);
            
            // Reference to data_dpc (purpose code)
            $table->unsignedTinyInteger('dpc_id')->default(0);
            $table->foreign('dpc_id')->references('id')->on('indoor_data_dpc')->onDelete('restrict');
            
            $table->string('altitude', 20);
            
            // Reference to matrix
            $table->unsignedTinyInteger('matrix_id')->default(0);
            $table->foreign('matrix_id')->references('id')->on('indoor_data_matrix')->onDelete('restrict');
            $table->string('matrix_other', 255);
            
            // Reference to dcot (observation type)
            $table->unsignedTinyInteger('dcot_id')->default(0);
            // $table->foreign('dcot_id')->references('id')->on('indoor_data_dcot')->onDelete('restrict');
            
            // Reference to dic (collection code)
            $table->unsignedTinyInteger('dic_id')->default(0);
            $table->foreign('dic_id')->references('id')->on('indoor_data_dic')->onDelete('restrict');
            
            $table->double('concentration_value');
            $table->string('concentration_unit', 20);
            $table->double('estimated_age');
            $table->year('sampling_date_y');
            $table->unsignedTinyInteger('sampling_date_m')->zerofill(2);
            $table->unsignedTinyInteger('sampling_date_d')->zerofill(2);
            $table->time('sampling_date_t');
            $table->text('sampling_duration');
            
            // Reference to dtoe (type of environment)
            $table->unsignedTinyInteger('dtoe_id')->comment('Type of environment');
            $table->foreign('dtoe_id')->references('id')->on('indoor_data_dtoe')->onDelete('restrict');
            
            // Reference to dcoe (category of environment)
            $table->unsignedTinyInteger('dcoe_id')->comment('Category of environment');
            $table->foreign('dcoe_id')->references('id')->on('indoor_data_dcoe')->onDelete('restrict');
            $table->string('dcoe_other', 255)->comment('Category of environment / Other');
            
            // Method and data references
            $table->integer('id_method')->default(0);
            $table->integer('id_data')->default(0);
            $table->mediumText('remark');
            
            // Adding timestamps for Laravel's created_at and updated_at
            $table->timestamps();
            
            // Indexes (converting from the original DDL)
            $table->index('country');
            $table->index('dcot_id');
            $table->index('dic_id');
            $table->index('dpc_id');
            $table->index('id_data');
            $table->index('id_method');
            $table->index('matrix_id');
            $table->index('sus_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('indoor_main');
    }
}