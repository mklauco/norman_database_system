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
        Schema::create('susdat_category_source', function (Blueprint $table) {
            $table->foreignId('source_id')->constrained()->nullable()->default(null)->references('id')->on('suspect_list_exchange_sources');
            $table->foreignId('category_id')->constrained()->nullable()->default(null)->references('id')->on('susdat_categories');
            $table->primary(['source_id', 'category_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_category_source');
    }
};
