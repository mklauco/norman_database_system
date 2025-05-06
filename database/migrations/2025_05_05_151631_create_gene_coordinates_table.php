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
        Schema::create('arbg_gene_coordinates', function (Blueprint $table) {
            $table->id();
            
            $table->char('country_id', 2)->default('')->nullable()->comment('Name of country');
            $table->char('country_other', 2)->nullable()->comment('Name of other country (trans-boundary sites)');
            $table->string('station_name')->nullable()->comment('Station name - Name');
            $table->string('national_code')->nullable()->comment('Station name - National code');
            $table->string('relevant_ec_code_wise')->nullable()->comment('Station name - Relevant EC code - WISE');
            $table->string('relevant_ec_code_other')->nullable()->comment('Station name - Relevant EC code - Other');
            $table->string('other_code')->nullable()->comment('Station name - Other code');
            
            $table->string('east_west')->nullable()->comment('Longitude - East / West');
            $table->string('longitude1')->nullable()->comment('Longitude - deg');
            $table->string('longitude2')->nullable()->comment('Longitude - min');
            $table->string('longitude3')->nullable()->comment('Longitude - sec');
            $table->string('longitude_decimal')->nullable()->comment('Longitude - Decimal');
            
            $table->string('north_south')->nullable()->comment('Latitude - North / South');
            $table->string('latitude1')->nullable()->comment('Latitude - deg');
            $table->string('latitude2')->nullable()->comment('Latitude - min');
            $table->string('latitude3')->nullable()->comment('Latitude - sec');
            $table->string('latitude_decimal')->nullable()->comment('Latitude - Decimal');
            
            $table->tinyInteger('precision_coordinates_id')->default(0)->comment('Precision of coordinates');
            $table->string('altitude')->nullable()->comment('Altitude [m]');
            $table->timestamps();

            $table->index('country_id');
            $table->index('precision_coordinates_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_gene_coordinates');
    }
};
