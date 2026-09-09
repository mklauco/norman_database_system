<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy server exposes SusDat use categories on three levels
 * (category[] / subcategory[] / subcategory2[]) whose id spaces overlap:
 * legacy subcategory 2 is "Insecticides" while legacy category 2 is
 * "Pharmaceuticals (PHARMA)". The existing flat susdat_categories table
 * assumes id === legacy id, which cannot represent that, so the hierarchy
 * gets its own pair of tables here.
 *
 * Purely additive: susdat_categories and susdat_category_substance are left
 * in place and untouched so both structures can be compared side by side on
 * production before anything reads from the new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('susdat_use_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->default(null)
                ->references('id')
                ->on('susdat_use_categories')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->unsignedTinyInteger('level')->comment('1 = category, 2 = subcategory, 3 = subcategory2');
            $table->unsignedInteger('legacy_id')->comment('Legacy category[]/subcategory[]/subcategory2[] value');
            $table->string('name');
            $table->string('abbreviation')->nullable()->default(null)->comment('Only level 1 carries one, e.g. PPP');
            $table->string('path')->comment('Slash separated own ids of the ancestor chain, e.g. 3/25/61');
            $table->unsignedInteger('sort_order')->default(0)->comment('Alphabetical, with Other/NR forced last');
            $table->timestamps();

            $table->unique(['level', 'legacy_id']);
            $table->index('parent_id');
            $table->index('path');
            $table->index(['level', 'sort_order']);
        });

        Schema::create('susdat_substance_use_category', function (Blueprint $table) {
            $table->foreignId('substance_id')
                ->references('id')
                ->on('susdat_substances')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('use_category_id')
                ->references('id')
                ->on('susdat_use_categories')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();

            $table->primary(['substance_id', 'use_category_id']);
            $table->index('use_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('susdat_substance_use_category');
        Schema::dropIfExists('susdat_use_categories');
    }
};
