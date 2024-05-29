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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country');
            $table->float('latitude', precision: 53);
            $table->float('longitude', precision: 53);
            $table->timestamps();
        });

        Schema::create('matrices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('data_nests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->nullable()->default(null);
            $table->foreignId('matrix_id')->constrained()->nullable()->default(null);
            $table->datetime('sampling_date')->nullable()->default(null);
            $table->unsignedSmallInteger('sampling_year')->nullable()->default(null);
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
