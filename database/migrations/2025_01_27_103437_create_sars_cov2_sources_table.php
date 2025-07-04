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
        Schema::create('sars_cov_file_uploads', function (Blueprint $table) {
            $table->id();
            //$table->string('sars_save')->nullable()->default(null); // ==> created_at  
            $table->string('filename')->nullable()->default(null); //  
            $table->string('directory')->nullable()->default(null); //  
            $table->boolean('is_available')->nullable()->default(null); // 0 - available, 1 - not available  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sars_cov_file_uploads');
    }
};

