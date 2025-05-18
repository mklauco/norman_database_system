<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the table for distinct substance_ids
        Schema::create('ecotox_main_3_substance_distinct', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_id')->constrained('susdat_substances');
            $table->unsignedInteger('sus_id')->nullable()->comment('Legacy substance ID');
            $table->integer('record_count')->nullable()->default(null)->comment('Number of records in ecotox_main_3');
            $table->timestamps();
            
            // Add uniqueness constraint
            $table->unique('substance_id', 'uq_substance_id');
        });
        
        // Note: Initial population will be handled by the controller method
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_main_3_substance_distinct');
    }
};