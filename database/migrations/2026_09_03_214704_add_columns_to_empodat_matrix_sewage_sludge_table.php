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
        Schema::table('empodat_matrix_sewage_sludge', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->comment('Name of river / estuary / lake / reservoir / sea')->after('id');
            $table->numeric('cod')->nullable()->comment('Chemical Oxygen Demand (COD) [g/m3]');
            $table->numeric('toc')->nullable()->comment('Total organic carbon (TOC) [g/m3]');
            $table->numeric('conductivity')->nullable()->comment('Conductivity [µS/cm]');
            $table->numeric('bod5')->nullable()->comment('BOD5 [g/m3]');
            $table->numeric('orthophosphate_po43')->nullable()->comment('Orthophosphate (PO43-) [g/m3]');
            $table->numeric('p_total')->nullable()->comment('P total [g/m3]');
            $table->numeric('nitrate_no3')->nullable()->comment('Nitrate (NO3-) [g/m3]');
            $table->numeric('ammonium_nh4')->nullable()->comment('Ammonium (NH4+) [g/m3]');
            $table->numeric('n_total')->nullable()->comment('N total [g/m3]');
            $table->numeric('sludge_retention_time')->nullable()->comment('Sludge retention time [day/s]');
            $table->tinyInteger('dtbu_id')->nullable()->comment('Treatment before use');
            $table->numeric('flow')->nullable()->comment('Daily flow [m3/day]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_matrix_sewage_sludge', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'cod',
                'toc',
                'conductivity',
                'bod5',
                'orthophosphate_po43',
                'p_total',
                'nitrate_no3',
                'ammonium_nh4',
                'n_total',
                'sludge_retention_time',
                'dtbu_id',
                'flow',
            ]);
        });
    }
};
