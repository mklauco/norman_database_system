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
        Schema::create('hazards_comptox_fate_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parse_run_id')->nullable()->index();
            $table->unsignedBigInteger('comptox_payload_id')->nullable()->index();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();

            // Core ARB_ARG-equivalent fields.
            $table->string('fate_id', 128);
            $table->string('dtxid', 64)->index();
            $table->string('endpoint_name');
            $table->text('result_value')->nullable();
            $table->string('model_source')->nullable();
            $table->string('unit', 64)->nullable();
            $table->string('value_type', 64)->nullable();

            // Extra safety/debug context.
            $table->json('source_json')->nullable();

            $table->timestamps();

            $table->unique(['dtxid', 'fate_id']);

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
        Schema::dropIfExists('hazards_comptox_fate_records');
    }
};

