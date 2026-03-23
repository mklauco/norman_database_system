<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazards_classification_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('susdat_substance_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('classification_type', 50);
            $table->string('criterion', 1);
            $table->string('classification_code', 50)->nullable();
            $table->tinyInteger('vote_value')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(
                ['susdat_substance_id', 'classification_type', 'criterion'],
                'hazards_class_votes_substance_type_criterion_idx'
            );

            $table->foreign('susdat_substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("
            CREATE UNIQUE INDEX hazards_class_votes_current_unique
            ON hazards_classification_votes (susdat_substance_id, user_id, classification_type, criterion)
            WHERE is_current = true
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS hazards_class_votes_current_unique');
        Schema::dropIfExists('hazards_classification_votes');
    }
};
