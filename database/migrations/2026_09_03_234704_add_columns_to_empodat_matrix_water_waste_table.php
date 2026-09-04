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
        Schema::table('empodat_matrix_water_waste', function (Blueprint $table) {
            $table->string('cod', 100)->nullable()->comment('Chemical Oxygen Demand (COD) [g/m3]');
            $table->string('toc', 100)->nullable()->comment('Total organic carbon (TOC) [g/m3]');
            $table->string('conductivity', 100)->nullable()->comment('Conductivity [µS/cm]');
            $table->string('orthophosphate_po43', 100)->nullable()->comment('Orthophosphate (PO43-) [g/m3]');
            $table->string('p_total', 100)->nullable()->comment('P total [g/m3]');
            $table->string('n_no2', 100)->nullable()->comment('N(NO2-) [mg/l]');
            $table->string('nitrate_no3', 100)->nullable()->comment('Nitrate (NO3-) [g/m3]');
            $table->string('ammonium_nh4', 100)->nullable()->comment('Ammonium (NH4+) [g/m3]');
            $table->string('n_total', 100)->nullable()->comment('N total [g/m3] [g/m3]');
            $table->string('bod5', 100)->nullable()->comment('BOD5 [g/m3]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_matrix_water_waste', function (Blueprint $table) {
            $table->dropColumn([
                'cod',
                'toc',
                'conductivity',
                'orthophosphate_po43',
                'p_total',
                'n_no2',
                'nitrate_no3',
                'ammonium_nh4',
                'n_total',
                'bod5',
            ]);
        });
    }
};
