<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hazards_derivation_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('susdat_substance_id')->index();
            $table->string('bucket', 12)->index();
            $table->unsignedBigInteger('hazards_substance_data_id')->index();
            $table->string('source_label', 32)->nullable();
            $table->string('kind', 16)->default('auto');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['susdat_substance_id', 'bucket'], 'hazards_derivation_sel_substance_bucket_idx');
            $table->index(['kind', 'is_current'], 'hazards_derivation_sel_kind_current_idx');

            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('hazards_substance_data_id')
                ->references('id')
                ->on('hazards_comptox_substance_data')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("
            CREATE UNIQUE INDEX hazards_derivation_selections_unique_current_auto
            ON hazards_derivation_selections (susdat_substance_id, bucket)
            WHERE is_current = TRUE AND kind = 'auto'
        ");

        DB::statement("
            CREATE UNIQUE INDEX hazards_derivation_selections_unique_current_vote
            ON hazards_derivation_selections (susdat_substance_id, bucket)
            WHERE is_current = TRUE AND kind = 'vote'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS hazards_derivation_selections_unique_current_auto');
        DB::statement('DROP INDEX IF EXISTS hazards_derivation_selections_unique_current_vote');

        Schema::dropIfExists('hazards_derivation_selections');
    }
};
