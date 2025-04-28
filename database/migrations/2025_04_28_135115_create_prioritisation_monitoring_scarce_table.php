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
        Schema::create('prioritisation_monitoring_scarce', function (Blueprint $table) {
            $table->id();
            $table->integer('pri_nr');
            $table->text('pri_use_for_priority_list');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances');
            $table->text('pri_substance');
            $table->text('pri_cas_no');
            $table->integer('pri_no_sites_new');
            $table->integer('pri_no_sites_where_mecsite_pnec_new');
            $table->decimal('pri_mec95_new', 15, 6);
            $table->decimal('pri_mecsite_max_new', 15, 6);
            $table->decimal('pri_loq_min', 15, 6);
            $table->tinyInteger('pri_cat');
            $table->decimal('pri_lowest_pnec', 15, 6);
            $table->text('pri_pnec_type');
            $table->text('pri_reference_pnec');
            $table->decimal('pri_max_exceedance', 15, 6);
            $table->decimal('pri_extent_of_exceedence', 15, 6);
            $table->decimal('pri_score_eoe', 15, 6);
            $table->decimal('pri_score_foe', 15, 6);
            $table->decimal('pri_score_total', 15, 6);
            $table->decimal('pri_loq_exceedance', 15, 6);
            $table->text('pri_substance_new');
            $table->integer('pri_no_of_sites_mecsite_pnec_new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prioritisation_monitoring_scarce');
    }
};
