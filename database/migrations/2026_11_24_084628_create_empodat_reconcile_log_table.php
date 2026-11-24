<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empodat_reconcile_log', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id');
            $table->string('step', 100);
            $table->string('table_name', 64);
            $table->string('action', 16);
            $table->jsonb('columns')->nullable();
            $table->bigInteger('id_min')->nullable();
            $table->bigInteger('id_max')->nullable();
            $table->jsonb('ids')->nullable();
            $table->jsonb('before_rows')->nullable();
            $table->integer('row_count');
            $table->integer('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('run_id')->references('id')->on('empodat_legacy_import_runs');
            $table->index('run_id');
            $table->index('table_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empodat_reconcile_log');
    }
};
