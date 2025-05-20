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
        Schema::create('export_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->default(null)->constrained()->onDelete('restrict');
            $table->string('filename');
            $table->string('format')->default('csv');
            $table->string('ip_address')->nullable()->default('');
            $table->string('user_agent')->nullable()->default('');
            $table->integer('record_count')->nullable()->default(null);
            $table->string('database_key');
            $table->string('status')->default('completed');
            $table->text('message')->nullable()->default('');
            $table->timestamps();
        });

        // Create pivot table between QueryLog and ExportDownload
        Schema::create('export_download_query_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_download_id')->constrained()->onDelete('cascade');
            $table->foreignId('query_log_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_download_query_log');
        Schema::dropIfExists('export_downloads');
    }
};