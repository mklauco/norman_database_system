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
        Schema::create('hazards_api_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 32)->default('manual');
            $table->string('status', 32)->default('running');

            $table->unsignedInteger('total_dtxids')->default(0);
            $table->unsignedInteger('processed_dtxids')->default(0);
            $table->unsignedInteger('successful_dtxids')->default(0);
            $table->unsignedInteger('failed_dtxids')->default(0);

            $table->json('failed_endpoints')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_api_runs');
    }
};

