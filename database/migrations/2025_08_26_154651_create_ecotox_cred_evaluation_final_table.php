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
        Schema::create('ecotox_cred_evaluation_final', function (Blueprint $table) {
            // Composite primary key (ecotox_id, user_id)
            $table->id();
            $table->string('ecotox_id', 50);
            $table->foreignId('user_id')->nullable()
                ->default(null)
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Evaluation scores
            $table->decimal('cred_final_score', 8, 4)->unsigned()->nullable()->default(null);
            $table->decimal('cred_final_score_total', 8, 4)->unsigned()->nullable()->default(null);

            // Boolean flags
            $table->integer('cred_final_close')->default(false)->nullable()->default(null);
            $table->integer('cred_final_evaluation')->default(false)->nullable()->default(null);

            // Comments and date
            $table->text('cred_final_comment')->nullable()->default(null);
            $table->dateTime('cred_final_date')->nullable()->default(null);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_cred_evaluation_final');
    }
};
