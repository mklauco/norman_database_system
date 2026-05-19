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
        Schema::create('factsheet_substance_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('substance_id');
            $table->json('meta_data')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('substance_id')->references('id')->on('susdat_substances')->onDelete('cascade');
            $table->index('substance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factsheet_substance_statistics');
    }
};
