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
        Schema::create('hazards_janus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('susdat_substance_id')->nullable()->index();
            $table->string('norman_id')->unique();
            $table->string('dtxid', 64)->nullable()->index();
            $table->text('smiles')->nullable();

            $table->string('p_assessment_class')->nullable();
            $table->double('p_assessment_index')->nullable();
            $table->double('p_reliability')->nullable();
            $table->double('p_score')->nullable();

            $table->double('b_assessment_log_units')->nullable();
            $table->double('b_reliability')->nullable();
            $table->double('b_score')->nullable();

            $table->double('t_assessment_mg_l')->nullable();
            $table->double('t_reliability')->nullable();
            $table->double('t_score')->nullable();

            $table->string('c_assessment')->nullable();
            $table->double('c_reliability')->nullable();
            $table->double('c_score')->nullable();

            $table->string('m_assessment')->nullable();
            $table->double('m_reliability')->nullable();
            $table->double('m_score')->nullable();

            $table->string('r_assessment')->nullable();
            $table->double('r_reliability')->nullable();
            $table->double('r_score')->nullable();

            $table->string('ed_assessment_class')->nullable();
            $table->double('ed_assessment_index')->nullable();
            $table->double('ed_reliability')->nullable();
            $table->double('ed_score')->nullable();

            $table->double('score_vpvb')->nullable();
            $table->double('score_svhc')->nullable();
            $table->double('score_pbt')->nullable();
            $table->text('remarks')->nullable();

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
        Schema::dropIfExists('hazards_janus');
    }
};
