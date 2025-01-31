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
        Schema::create('file_upload_empodat_main', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_collection_file_id')->references('id')->constrained()->on('data_collection_file_uploads');
            $table->foreignId('empodat_main_id')->references('id')->constrained()->on('empodat_main');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_upload_empodat_main');
    }
};
