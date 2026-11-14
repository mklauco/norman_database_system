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
        Schema::create('hazards_classification_supports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('substance_classification_id');
            $table->unsignedBigInteger('susdat_substance_id');
            $table->string('criterion', 1)->index();
            $table->string('classification_code', 50)->index();
            $table->unsignedInteger('points');
            $table->string('source_type', 50)->nullable()->index();
            $table->string('origin_type', 30)->index();
            $table->unsignedBigInteger('origin_user_id')->nullable();
            $table->unsignedBigInteger('derivation_selection_id')->nullable();
            $table->unsignedBigInteger('classification_vote_id')->nullable();
            $table->boolean('is_winner')->default(false)->index();
            $table->timestamps();

            $table->index(['substance_classification_id', 'criterion'], 'hazards_class_supports_classification_criterion_idx');

            $table->foreign('substance_classification_id', 'hazards_class_supports_classification_fk')
                ->references('id')
                ->on('hazards_substance_classifications')
                ->cascadeOnDelete();

            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('origin_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('derivation_selection_id')
                ->references('id')
                ->on('hazards_derivation_selections')
                ->nullOnDelete();
            $table->foreign('classification_vote_id')
                ->references('id')
                ->on('hazards_classification_votes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazards_classification_supports');
    }
};
