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
        Schema::create('ecotox_cred_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('question_number');
            $table->string('question_letter', 5)->nullable();
            $table->text('question_text');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('screening_score', 8, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign key to self for parent-child relationship
            $table->foreign('parent_id')->references('id')->on('ecotox_cred_questions')->onDelete('cascade');

            // Indexes for better performance
            $table->index('parent_id');
            $table->index('question_number');
            $table->index('sort_order');

            // Unique constraint for question number + letter combination
            $table->unique(['question_number', 'question_letter', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_cred_questions');
    }
};
