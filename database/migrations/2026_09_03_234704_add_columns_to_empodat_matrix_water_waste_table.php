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
            $table->numeric('cod')->nullable()->comment('Chemical Oxygen Demand (COD) [g/m3]');
            $table->numeric('toc')->nullable()->comment('Total organic carbon (TOC) [g/m3]');
            $table->numeric('conductivity')->nullable()->comment('Conductivity [µS/cm]');
            $table->numeric('orthophosphate_po43')->nullable()->comment('Orthophosphate (PO43-) [g/m3]');
            $table->numeric('p_total')->nullable()->comment('P total [g/m3]');
            $table->numeric('n_no2')->nullable()->comment('N(NO2-) [mg/l]');
            $table->numeric('nitrate_no3')->nullable()->comment('Nitrate (NO3-) [g/m3]');
            $table->numeric('ammonium_nh4')->nullable()->comment('Ammonium (NH4+) [g/m3]');
            $table->numeric('n_total')->nullable()->comment('N total [g/m3] [g/m3]');
            $table->numeric('bod5')->nullable()->comment('BOD5 [g/m3]');
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
