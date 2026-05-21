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
        Schema::table('hazards_comptox_payloads', function (Blueprint $table) {
            $table->foreign('api_run_id')
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
        Schema::table('hazards_comptox_payloads', function (Blueprint $table) {
            $table->dropForeign(['api_run_id']);
        });
    }
};

