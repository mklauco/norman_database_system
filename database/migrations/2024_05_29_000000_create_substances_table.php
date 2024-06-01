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

        Schema::create('susdat_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->timestamps();
        });


        
        Schema::create('susdat_substances', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->json('metadata')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_substances');
    }
};
