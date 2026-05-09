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
        if (Schema::hasTable('empodat_stations')) {
            return;
        }

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

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_stations');
    }
};
