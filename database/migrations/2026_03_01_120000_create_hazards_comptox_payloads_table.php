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
        Schema::create('hazards_comptox_payloads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_run_id')->nullable()->index();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();
            $table->string('dtxid', 64)->unique();

            $table->json('fate')->nullable();
            $table->json('detail')->nullable();
            $table->json('property')->nullable();
            $table->json('synonym')->nullable();

            $table->timestamp('fetched_at')->nullable();
            $table->json('endpoint_status')->nullable();

            $table->timestamps();

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
        Schema::dropIfExists('hazards_comptox_payloads');
    }
};
