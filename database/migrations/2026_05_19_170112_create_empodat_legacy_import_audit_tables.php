<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit tables for the EmpodatLegacy seeder.
     *
     * One row per orchestrator run lives in `empodat_legacy_import_runs`.
     * Each step writes one row to `empodat_legacy_import_log` recording
     * what it inserted, so a failed run can be rolled back precisely via
     * `php artisan empodat-legacy:rollback {run_id}`.
     *
     * Two id-tracking modes by step volume:
     *   - inserted_ids (jsonb array) — small steps (files, methods, etc.)
     *   - id_min / id_max (range)    — bulk steps (empodat_main, _minor, _matrix_biota)
     */
    public function up(): void
    {
        Schema::create('empodat_legacy_import_runs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('since_id')->comment('Legacy id cutoff; rows with id > since_id were imported');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('running')->comment('running | completed | failed | rolled_back');
            $table->text('notes')->nullable();
        });

        Schema::create('empodat_legacy_import_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('empodat_legacy_import_runs');
            $table->string('step_name', 100);
            $table->string('table_name', 100);
            $table->jsonb('inserted_ids')->nullable()->comment('Exact id list (small steps); NULL for bulk steps');
            $table->bigInteger('id_min')->nullable()->comment('Range marker for bulk steps');
            $table->bigInteger('id_max')->nullable();
            $table->integer('inserted_count')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->string('status', 20)->comment('completed | failed | rolled_back');
            $table->text('error')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();

            $table->index('run_id');
            $table->index('table_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empodat_legacy_import_log');
        Schema::dropIfExists('empodat_legacy_import_runs');
    }
};
