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
        Schema::table('empodat_matrix_suspended_matter', function (Blueprint $table) {
            $table->tinyInteger('dtt_id')->nullable()->comment('Advanced treatment steps (data_tertiary_treatment)');
            $table->string('dtt_other', 255)->nullable();
            $table->string('capacity', 255)->nullable()->comment('Capacity (population equivalent)');
            $table->numeric('flow')->nullable()->comment('Daily flow [m3/day]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_matrix_suspended_matter', function (Blueprint $table) {
            $table->dropColumn([
                'dtt_id',
                'dtt_other',
                'capacity',
                'flow',
            ]);
        });
    }
};
