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
        Schema::create('empodat_search_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->nullable()->default(null)->references('id')->on('list_countries'); // Country
            $table->foreignId('country_other')->constrained()->nullable()->default(null)->references('id')->on('list_countries'); // Country - Other
        });

        Schema::create('empodat_search_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained()->nullable()->default(null)->references('id')->on('list_matrices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_search_countries');
        Schema::dropIfExists('empodat_search_matrices');
    }
};
