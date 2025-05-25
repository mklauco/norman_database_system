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
        Schema::create('suspect_list_exchange_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->tinyInteger('order')->nullable()->default(null);
            $table->tinyInteger('show')->nullable()->default(1);
            $table->foreignId('added_by')->nullable()->default(null)->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspect_list_exchange_sources');
    }
};
