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
        Schema::create('file_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->default(null)->constrained('files')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->default(null)->constrained('projects')->onDelete('cascade');
            $table->text('notes')->nullable()->default(null);
            $table->timestamps();
            
            $table->unique(['file_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_project');
    }
};