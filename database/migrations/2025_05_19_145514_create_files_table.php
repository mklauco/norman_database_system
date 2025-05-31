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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->string('original_name')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->string('file_path')->nullable()->default(null);
            $table->unsignedBigInteger('file_size')->nullable()->default(null)->comment('File size in bytes');
            $table->string('mime_type')->nullable()->default(null);
            $table->foreignId('template_id')->nullable()->default(null)->constrained('templates')->onDelete('restrict');
            $table->foreignId('database_entity_id')->nullable()->default(null)->constrained('database_entities')->onDelete('restrict');
            $table->text('processing_notes')->nullable()->default(null);
            $table->foreignId('uploaded_by')->nullable()->default(null)->constrained('users')->onDelete('set null');            
            $table->timestamp('uploaded_at')->nullable()->default(null);
            $table->boolean('is_deleted')->default(0);
            $table->foreignId('project_id')->nullable()->default(null)->constrained('projects')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};