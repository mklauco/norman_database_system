<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * empodat_suspect_prioritisation is now a plain partitioned table, not a
     * materialized view, so it no longer self-evidently exposes its own
     * staleness. This table records one row per partition rebuild so
     * freshness can be checked explicitly.
     */
    public function up(): void
    {
        Schema::create('empodat_suspect_prioritisation_builds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->timestamp('started_at')->nullable()->default(null);
            $table->timestamp('finished_at')->nullable()->default(null);
            $table->unsignedBigInteger('duration_ms')->nullable()->default(null);
            $table->unsignedBigInteger('row_count')->nullable()->default(null);
            $table->string('status', 20);
            $table->text('error')->nullable()->default(null);
            $table->string('triggered_by', 50)->nullable()->default(null);
            $table->timestamps();

            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_prioritisation_builds');
    }
};
