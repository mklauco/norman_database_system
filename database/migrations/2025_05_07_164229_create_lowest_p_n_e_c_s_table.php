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
        Schema::create('ecotox_lowest_pnec', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sus_id');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances');
            $table->double('lowest_pnec_value_1')->nullable()->comment('Lowest PNECfw [µg/l]');
            $table->double('lowest_pnec_value_2')->nullable()->comment('Lowest PNECmarine [µg/l]');
            $table->double('lowest_pnec_value_3')->nullable()->comment('Lowest PNECfw*2.6*(0.615+0.019*Koc) [µg/kg dw]');
            $table->double('lowest_pnec_value_4')->nullable()->comment('PNECfw*BCF [µg/kg ww]');
            $table->double('lowest_pnec_value_5')->nullable()->comment('PNECfw*BCF/10 [µg/kg ww]');
            $table->double('lowest_pnec_value_6')->nullable()->comment('PNECfw*BCF/4 [µg/kg ww]');
            $table->double('lowest_pnec_value_7')->nullable()->comment('PNECfw*BCF/10/4 [µg/kg ww]');
            $table->double('lowest_pnec_value_8')->nullable()->comment('Lowest PNEC Biota (WFD) [ug/kg ww]');
            $table->unsignedTinyInteger('lowest_exp_pred')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_lowest_pnec');
    }
};