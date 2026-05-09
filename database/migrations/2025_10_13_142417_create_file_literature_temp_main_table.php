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
        Schema::create('file_literature_temp_main', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('literature_temp_main_id')->constrained('literature_temp_main')->onDelete('cascade');
            $table->timestamps();

            // Optional: Add unique constraint to prevent duplicate associations
            $table->unique(['file_id', 'literature_temp_main_id'], 'file_literature_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_literature_temp_main');
    }
};
