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
        Schema::create('ecotox_main_3_changes', function (Blueprint $table) {
            $table->id();
            $table->string('column_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('change_date')->nullable();
            $table->string('ecotox_id', 30)->nullable();
            $table->text('change_old')->nullable();
            $table->text('change_new')->nullable();
            $table->unsignedTinyInteger('change_type')->nullable();

            $table->index('ecotox_id');
            $table->index('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_main_3_changes');
    }
};
