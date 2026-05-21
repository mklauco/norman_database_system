<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazards_substance_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('susdat_substance_id')->index();
            $table->unsignedBigInteger('editor_user_id')->nullable()->index();

            $table->string('P', 50)->nullable();
            $table->integer('p_auto_points')->nullable();
            $table->integer('p_vote_points')->nullable();
            $table->integer('p_total_points')->nullable();
            $table->string('B', 50)->nullable();
            $table->integer('b_auto_points')->nullable();
            $table->integer('b_vote_points')->nullable();
            $table->integer('b_total_points')->nullable();
            $table->string('M', 50)->nullable();
            $table->integer('m_auto_points')->nullable();
            $table->integer('m_vote_points')->nullable();
            $table->integer('m_total_points')->nullable();
            $table->string('T', 50)->nullable();
            $table->integer('t_auto_points')->nullable();
            $table->integer('t_vote_points')->nullable();
            $table->integer('t_total_points')->nullable();

            $table->string('source_type', 50)->nullable();

            $table->boolean('is_current')->default(true);
            $table->string('kind', 30)->default('classification');
            $table->timestamps();

            $table->index(
                ['susdat_substance_id', 'editor_user_id', 'kind'],
                'hazards_substance_classifications_substance_editor_kind_idx'
            );

            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('editor_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("
            CREATE UNIQUE INDEX hazards_substance_classifications_current_unique
            ON hazards_substance_classifications (susdat_substance_id, editor_user_id, kind)
            WHERE is_current = true
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS hazards_substance_classifications_current_unique');
        Schema::dropIfExists('hazards_substance_classifications');
    }
};
