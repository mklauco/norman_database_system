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
        Schema::create('file_upload_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->references('id')->constrained()->on('projects');
            $table->foreignId('data_collection_file_id')->references('id')->constrained()->on('data_collection_file_uploads');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_empodats');
    }
};
