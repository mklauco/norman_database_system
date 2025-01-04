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
        Schema::create('list_quality_empodat_analytical_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('min_rating');
            $table->integer('max_rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_quality_empodat_analytical_methods');
    }
};
