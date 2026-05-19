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
        // Create the table for distinct substance_ids from PNEC3
        Schema::create('ecotox_pnec3_substance_distinct', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_id')->constrained('susdat_substances');
            $table->unsignedInteger('sus_id')->nullable()->comment('Legacy substance ID');
            $table->integer('record_count')->nullable()->default(null)->comment('Number of records in ecotox_pnec3');
            $table->timestamps();

            // Add uniqueness constraint
            $table->unique('substance_id', 'uq_pnec3_substance_id');
        });

        // Note: Initial population will be handled by the controller method
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_pnec3_substance_distinct');
    }
};
