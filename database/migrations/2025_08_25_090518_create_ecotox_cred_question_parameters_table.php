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
        Schema::create('ecotox_cred_question_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('ecotox_config_id');
            $table->string('parameter_label', 255);
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('question_id')->references('id')->on('ecotox_cred_questions')->onDelete('cascade');
            $table->foreign('ecotox_config_id')->references('id')->on('ecotox_comparative_table_configs')->onDelete('cascade');

            // Indexes for better performance
            $table->index('question_id');
            $table->index('ecotox_config_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_cred_question_parameters');
    }
};
