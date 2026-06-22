<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brings empodat_matrix_water_surface to parity with legacy MariaDB
     * dct_analysis_water_surface.
     *
     * The original v1->v2 pgloader pass imported 30 of 52 columns. The
     * remaining 22 (ocean/sea region, flow & sampled volume, nitrogen /
     * phosphorus / chloride / sulfate / carbon chemistry, plus calcium /
     * iron / magnesium / manganese / dissolved O2 / alkalinity / hardness
     * indicators) are required to losslessly land the delta water-surface
     * rows starting at id 161 062 333.
     *
     * Column-type policy:
     *   - Legacy varchar(N) -> string(N).
     *   - Legacy tinytext   -> text.
     * All columns nullable, no defaults, no FK constraints (no lookup
     * tables exist for these chemistry codes in PG yet).
     */
    public function up(): void
    {
        Schema::table('empodat_matrix_water_surface', function (Blueprint $table) {
            $table->string('ocean_sea_region_name')->nullable()->after('basin_name');

            $table->string('flow', 50)->nullable();
            $table->string('sampled_volume', 50)->nullable();

            $table->string('p_po4', 10)->nullable();
            $table->string('n_no2', 10)->nullable();
            $table->string('n_no3', 10)->nullable();
            $table->string('n_total', 10)->nullable();

            $table->text('alkalinity')->nullable();
            $table->text('nh4')->nullable();
            $table->text('dissolved_o2')->nullable();
            $table->text('cod')->nullable();
            $table->text('so42')->nullable();
            $table->text('hco3')->nullable();
            $table->text('toc')->nullable();
            $table->text('cl')->nullable();
            $table->text('po43')->nullable();
            $table->text('calcium')->nullable();
            $table->text('iron')->nullable();
            $table->text('magnesium')->nullable();
            $table->text('manganese')->nullable();
            $table->text('chlorides')->nullable();
            $table->text('sulfates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empodat_matrix_water_surface', function (Blueprint $table) {
            $table->dropColumn([
                'ocean_sea_region_name',
                'flow',
                'sampled_volume',
                'p_po4',
                'n_no2',
                'n_no3',
                'n_total',
                'alkalinity',
                'nh4',
                'dissolved_o2',
                'cod',
                'so42',
                'hco3',
                'toc',
                'cl',
                'po43',
                'calcium',
                'iron',
                'magnesium',
                'manganese',
                'chlorides',
                'sulfates',
            ]);
        });
    }
};
