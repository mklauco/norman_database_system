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
        Schema::create('prioritisation_modelling_danube', function (Blueprint $table) {
            $table->id();
            $table->integer('pri_id');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            $table->text('pri_cas');
            $table->text('pri_name');
            $table->text('pri_emissions');
            $table->text('pri_correct');
            $table->decimal('pri_score1', 3, 2);
            $table->decimal('pri_score2', 3, 2);
            $table->decimal('pri_score3', 3, 2);
            $table->decimal('pri_score4', 3, 2);
            $table->decimal('pri_score5', 3, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prioritisation_modelling_danube');
    }
};
