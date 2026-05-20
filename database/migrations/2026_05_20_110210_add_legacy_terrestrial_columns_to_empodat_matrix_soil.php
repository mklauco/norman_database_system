<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brings empodat_matrix_soil to parity with legacy MariaDB dct_analysis_soil.
     *
     * The original v1->v2 pgloader migration imported 29 columns. The
     * remaining 7 columns below (EUNIS habitat, sample weight/water content
     * variants, storage temperature) were left out and are required to
     * losslessly land the soil delta starting at id > 161 062 333.
     *
     * Type policy: all 7 are free-form varchar in legacy and stay varchar in
     * PG — no FK references to add. Nullable to match legacy `DEFAULT NULL`
     * and absorb the ~88K existing rows that won't have values backfilled.
     */
    public function up(): void
    {
        Schema::table('empodat_matrix_soil', function (Blueprint $table) {
            $table->string('eunis_habitat_type')->nullable()->after('dpr_other');
            $table->string('no_pooled_sub_samples')->nullable()->after('eunis_habitat_type');
            $table->string('sample_wet_weight')->nullable()->after('no_pooled_sub_samples')->comment('Sample wet weight [g ww]');
            $table->string('sample_dry_weight')->nullable()->after('sample_wet_weight')->comment('Sample dry weight [g dw]');
            $table->string('water_content')->nullable()->after('sample_dry_weight');
            $table->string('fat_content')->nullable()->after('water_content');
            $table->string('storage_temperature')->nullable()->after('fat_content');
        });
    }

    public function down(): void
    {
        Schema::table('empodat_matrix_soil', function (Blueprint $table) {
            $table->dropColumn([
                'eunis_habitat_type',
                'no_pooled_sub_samples',
                'sample_wet_weight',
                'sample_dry_weight',
                'water_content',
                'fat_content',
                'storage_temperature',
            ]);
        });
    }
};
