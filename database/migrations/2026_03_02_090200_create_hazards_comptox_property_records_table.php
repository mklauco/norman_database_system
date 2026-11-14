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
        Schema::create('hazards_comptox_property_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parse_run_id')->nullable()->index();
            $table->unsignedBigInteger('comptox_payload_id')->nullable()->index();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();

            // Core ARB_ARG-equivalent fields.
            $table->string('property_id', 128);
            $table->string('dtxid', 64)->index();
            $table->string('name');
            $table->text('value')->nullable();
            $table->string('unit', 64)->nullable();
            $table->string('prop_type', 64)->nullable();
            $table->string('source')->nullable();
            $table->string('property_string_id')->nullable();

            // Extra safety/debug context.
            $table->json('source_json')->nullable();

            $table->timestamps();

            $table->unique(['dtxid', 'property_id']);

            $table->foreign('parse_run_id')
                ->references('id')
                ->on('hazards_parse_runs')
                ->nullOnDelete();
            $table->foreign('comptox_payload_id')
                ->references('id')
                ->on('hazards_comptox_payloads')
                ->nullOnDelete();
            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_comptox_property_records');
    }
};

