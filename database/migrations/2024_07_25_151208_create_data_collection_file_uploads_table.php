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
        Schema::create('data_collection_file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('filename');
            $table->foreignId('database_entity_id')->references('id')->constrained()->on('database_entities');
            $table->foreignId('data_collection_template_id')->references('id')->nullable()->default(null)->constrained()->on('data_collection_templates');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_collection_file_uploads');
    }
};
