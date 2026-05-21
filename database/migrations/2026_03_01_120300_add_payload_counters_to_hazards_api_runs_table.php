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
        Schema::table('hazards_api_runs', function (Blueprint $table) {
            $table->unsignedInteger('new_payloads')->default(0)->after('failed_dtxids');
            $table->unsignedInteger('updated_payloads')->default(0)->after('new_payloads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hazards_api_runs', function (Blueprint $table) {
            $table->dropColumn(['new_payloads', 'updated_payloads']);
        });
    }
};

