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
        Schema::create('hazards_parse_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_api_run_id')->nullable()->index();
            $table->string('trigger', 32)->default('manual');
            $table->string('status', 32)->default('running');

            $table->unsignedInteger('total_payloads')->default(0);
            $table->unsignedInteger('processed_payloads')->default(0);
            $table->unsignedInteger('successful_payloads')->default(0);
            $table->unsignedInteger('failed_payloads')->default(0);

            $table->unsignedInteger('new_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('unchanged_records')->default(0);
            $table->json('counts_by_type')->nullable();

            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('source_api_run_id')
                ->references('id')
                ->on('hazards_api_runs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_parse_runs');
    }
};

