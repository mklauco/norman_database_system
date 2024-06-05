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
        Schema::create('susdat_source_substance', function (Blueprint $table) {
            $table->foreignId('source_id')->constrained()->nullable()->default(null)->references('id')->on('suspect_list_exchange_sources');
            $table->foreignId('substance_id')->constrained()->nullable()->default(null)->references('id')->on('susdat_substances');
            $table->primary(['source_id', 'substance_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_substance_source');
    }
};
