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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->string('version')->nullable()->default('1');
            $table->foreignId('database_entity_id')->nullable()->default(null)->constrained('database_entities')->onDelete('restrict');
            $table->string('file_path')->nullable()->default(null);
            $table->boolean('is_active')->nullable()->default(true);
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->default(null)->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};