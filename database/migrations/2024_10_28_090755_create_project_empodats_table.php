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
        Schema::create('project_empodats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->references('id')->constrained()->on('projects');
            $table->foreignId('empodat_id')->references('id')->constrained()->on('empodat_main');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_empodats');
    }
};
