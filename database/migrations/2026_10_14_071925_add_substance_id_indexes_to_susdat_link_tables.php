<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the substance_id foreign keys of the SusDat link tables.
     *
     * susdat_source_substance only had its (source_id, substance_id) primary key and
     * susdat_usepa only its id primary key, so looking up a substance's sources or
     * US EPA data scanned the whole table.
     */
    public function up(): void
    {
        Schema::table('susdat_source_substance', function (Blueprint $table): void {
            $table->index('substance_id', 'idx_susdat_source_substance_substance_id');
        });

        Schema::table('susdat_usepa', function (Blueprint $table): void {
            $table->index('substance_id', 'idx_susdat_usepa_substance_id');
        });
    }

    public function down(): void
    {
        Schema::table('susdat_source_substance', function (Blueprint $table): void {
            $table->dropIndex('idx_susdat_source_substance_substance_id');
        });

        Schema::table('susdat_usepa', function (Blueprint $table): void {
            $table->dropIndex('idx_susdat_usepa_substance_id');
        });
    }
};
