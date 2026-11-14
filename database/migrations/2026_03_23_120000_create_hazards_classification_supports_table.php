<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazards_classification_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_classification_id')->constrained('hazards_substance_classifications')->cascadeOnDelete();
            $table->foreignId('susdat_substance_id')->constrained('susdat_substances')->cascadeOnDelete();
            $table->string('criterion', 1)->index();
            $table->string('classification_code', 50)->index();
            $table->unsignedInteger('points');
            $table->string('source_type', 50)->nullable()->index();
            $table->string('origin_type', 30)->index();
            $table->foreignId('origin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('derivation_selection_id')->nullable()->constrained('hazards_derivation_selections')->nullOnDelete();
            $table->foreignId('classification_vote_id')->nullable()->constrained('hazards_classification_votes')->nullOnDelete();
            $table->boolean('is_winner')->default(false)->index();
            $table->timestamps();

            $table->index(['substance_classification_id', 'criterion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hazards_classification_supports');
    }
};
