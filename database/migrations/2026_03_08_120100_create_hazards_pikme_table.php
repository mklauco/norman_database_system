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
        Schema::create('hazards_pikme', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();
            $table->string('dtxid', 64)->unique();

            $table->double('logkoc_pred_opera')->nullable();
            $table->text('koc_predrange_opera')->nullable();
            $table->double('conf_index_koc_opera')->nullable();
            $table->string('ad_koc_opera')->nullable();
            $table->double('ad_index_koc_opera')->nullable();

            $table->double('logbcf_pred_opera')->nullable();
            $table->text('bcf_predrange_opera')->nullable();
            $table->double('conf_index_bcf_opera')->nullable();
            $table->string('ad_bcf_opera')->nullable();
            $table->double('ad_index_bcf_opera')->nullable();

            $table->double('biodeg_loghalflife_pred_opera')->nullable();
            $table->text('biodeg_predrange_opera')->nullable();
            $table->double('conf_index_biodeg_opera')->nullable();
            $table->string('ad_biodeg_opera')->nullable();
            $table->double('ad_index_biodeg_opera')->nullable();

            // Fallback legacy names still referenced in ARB_ARG logic.
            $table->double('loghl_pred_opera')->nullable();
            $table->text('hl_predrange_opera')->nullable();
            $table->double('conf_index_hl_opera')->nullable();
            $table->string('ad_hl_opera')->nullable();

            $table->string('source_file_name')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_pikme');
    }
};
