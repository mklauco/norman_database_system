<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates list_tissues_subcategory table to store tissue subcategories
     * linked to main tissue categories in list_tissues table.
     */
    public function up(): void
    {
        Schema::create('list_tissues_subcategory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tissue_id');
            $table->string('name');
            $table->timestamps();

            // Foreign key constraint to maintain referential integrity
            $table->foreign('tissue_id')
                ->references('id')
                ->on('list_tissues')
                ->onDelete('cascade');

            // Index for faster lookups
            $table->index('tissue_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_tissues_subcategory');
    }
};
