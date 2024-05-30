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
        
        Schema::create('substances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country');
            $table->string('country_other');
            $table->string('national_name');
            $table->string('short_sample_code');
            $table->string('sample_code');
            $table->string('provider_code');
            $table->string('code_ec_wise');
            $table->string('code_ec_other');
            $table->string('code_other');
            $table->string('specific_locations');
            $table->float('latitude', precision: 53);
            $table->float('longitude', precision: 53);
            $table->timestamps();
        });
        
        Schema::create('matrices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('concentration_indicators', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        }); 

        Schema::create('sampling_techniques', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('methods', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        
        Schema::create('data_nests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_id')->constrained()->nullable()->default(null)->references('id')->on('substances');
            $table->foreignId('station_id')->constrained()->nullable()->default(null)->references('id')->on('stations');
            $table->float('altitude', 10)->nullable()->default(null);
            $table->foreignId('matrix_id')->constrained()->nullable()->default(null)->references('id')->on('matrices');
            $table->foreignId('concentration_indicator_id')->constrained()->nullable()->default(null)->references('id')->on('concentration_indicators');
            $table->float('concentration_value', 10)->nullable()->default(null);
            $table->string('unit_extra')->nullable()->default(null);
            $table->foreignId('sampling_technique_id')->constrained()->nullable()->default(null)->references('id')->on('sampling_techniques');
            $table->date('sampling_date')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_date_year')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_date_month')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_date_date')->nullable()->default(null);
            $table->time('sampling_date_t')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_duration_day')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_duration_hour')->nullable()->default(null);
            $table->foreignId('method_id')->constrained()->nullable()->default(null)->references('id')->on('methods');
            $table->foreignId('data_source_id')->constrained()->nullable()->default(null)->references('id')->on('data_sources');
            $table->string('description')->nullable()->default(null);
            $table->json('remarks')->nullable()->default(null);
            $table->foreignId('availability_id')->nullable()->default(null)->references('id')->on('availabilities');
            $table->foreignId('added_by')->nullable()->default(null)->references('id')->on('users');
            $table->timestamps();
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('stations');
        Schema::dropIfExists('data_nests');
    }
};
