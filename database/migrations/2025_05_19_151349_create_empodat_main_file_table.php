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
        Schema::create('empodat_main_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empodat_main_id')->nullable()->default(null)->constrained('empodat_main')->onDelete('restrict');
            $table->foreignId('file_id')->nullable()->default(null)->constrained('files')->onDelete('restrict');
            $table->text('notes')->nullable()->default(null);
            $table->timestamps();
            
            $table->unique(['empodat_main_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_main_file');
    }
};