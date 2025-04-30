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
        Schema::create('prioritisation_monitoring_danube', function (Blueprint $table) {
            $table->id();
            $table->integer('pri_no');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances');
            $table->text('pri_substance');
            $table->text('pri_cas_no');
            $table->text('pri_position_prioritisation_2014');
            $table->tinyInteger('pri_category');
            $table->integer('pri_no_of_sites_where_mecsite_pnec');
            $table->decimal('pri_mecsite_max', 15, 6);
            $table->decimal('pri_95th_mecsite', 15, 6);
            $table->decimal('pri_lowest_pnec', 15, 6);
            $table->text('pri_reference_key_study');
            $table->text('pri_pnec_type');
            $table->text('pri_species');
            $table->integer('pri_af');
            $table->decimal('pri_extent_of_exceedence', 15, 6);
            $table->decimal('pri_score_eoe', 15, 6);
            $table->decimal('pri_score_foe', 15, 6);
            $table->decimal('pri_final_score', 15, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prioritisation_monitoring_danube');
    }
};
