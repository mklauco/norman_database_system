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
        Schema::table('empodat_matrix_water_ground', function (Blueprint $table) {
            $table->numeric('alkalinity')->nullable()->comment('Alkalinity [mmol/l]');
            $table->numeric('nh4')->nullable()->comment('Ammonium (NH4+) [mg/l]');
            $table->numeric('dissolved_o2')->nullable()->comment('Dissolved O2 [mg/l]');
            $table->numeric('cod')->nullable()->comment('Chemical Oxygen Demand (COD) [mg/l]');
            $table->numeric('so42')->nullable()->comment('Sulphate (SO42-) [mg/l]');
            $table->numeric('hco3')->nullable()->comment('Hydrocarbonate (HCO3-) [mg/l]');
            $table->numeric('toc')->nullable()->comment('Total organic carbon (TOC) [mg/l]');
            $table->numeric('cl')->nullable()->comment('Chloride (Cl-) [mg/l]');
            $table->numeric('po43')->nullable()->comment('Orthophosphate (PO43-) [mg/l]');
            $table->numeric('calcium')->nullable()->comment('Calcium [mg/l]');
            $table->numeric('iron')->nullable()->comment('Iron [mg/l]');
            $table->numeric('magnesium')->nullable()->comment('Magnesium [mg/l]');
            $table->numeric('manganese')->nullable()->comment('Manganese [mg/l]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_matrix_water_ground', function (Blueprint $table) {
            $table->dropColumn([
                'alkalinity',
                'nh4',
                'dissolved_o2',
                'cod',
                'so42',
                'hco3',
                'toc',
                'cl',
                'po43',
                'calcium',
                'iron',
                'magnesium',
                'manganese',
            ]);
        });
    }
};
