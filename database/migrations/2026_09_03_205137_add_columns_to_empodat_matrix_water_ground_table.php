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
            $table->string('alkalinity', 100)->nullable()->comment('Alkalinity [mmol/l]');
            $table->string('nh4', 100)->nullable()->comment('Ammonium (NH4+) [mg/l]');
            $table->string('dissolved_o2', 100)->nullable()->comment('Dissolved O2 [mg/l]');
            $table->string('cod', 100)->nullable()->comment('Chemical Oxygen Demand (COD) [mg/l]');
            $table->string('so42', 100)->nullable()->comment('Sulphate (SO42-) [mg/l]');
            $table->string('hco3', 100)->nullable()->comment('Hydrocarbonate (HCO3-) [mg/l]');
            $table->string('toc', 100)->nullable()->comment('Total organic carbon (TOC) [mg/l]');
            $table->string('cl', 100)->nullable()->comment('Chloride (Cl-) [mg/l]');
            $table->string('po43', 100)->nullable()->comment('Orthophosphate (PO43-) [mg/l]');
            $table->string('calcium', 100)->nullable()->comment('Calcium [mg/l]');
            $table->string('iron', 100)->nullable()->comment('Iron [mg/l]');
            $table->string('magnesium', 100)->nullable()->comment('Magnesium [mg/l]');
            $table->string('manganese', 100)->nullable()->comment('Manganese [mg/l]');
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
