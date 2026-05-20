<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brings empodat_matrix_biota to parity with legacy MariaDB dct_analysis_biota.
     *
     * The original v1->v2 pgloader migration imported the first 36 columns. The
     * remaining 17 columns (terrestrial-taxonomy hierarchy, individual/pooled
     * flag, EUNIS habitat, and bird biometrics) were left out and are required
     * to losslessly land the 1 054 477-row biota delta starting at id 161 062 333.
     *
     * Column-type policy:
     *   - Semantic FK ids (d**_id) use bigint via foreignId() so that the FK
     *     constraint can be added without an ALTER once the lookup tables
     *     (data_kingdom, data_phylum, data_class, data_order, data_family,
     *     data_species, data_individual_or_pooled, data_category,
     *     data_habitat_type) are migrated from legacy to PG.
     *   - Text-stored values (varchar/255 in legacy) use string().
     * All columns nullable, no defaults, no FK constraints yet (target tables
     * do not exist in PG); the constraints are a follow-up migration.
     */
    public function up(): void
    {
        Schema::table('empodat_matrix_biota', function (Blueprint $table) {
            $table->string('biota_weight_dry')->nullable()->after('biota_weight');

            $table->foreignId('dki_id')->nullable()->comment('Kingdom');
            $table->foreignId('dph_id')->nullable()->comment('Phylum');
            $table->foreignId('dcla_id')->nullable()->comment('Class');
            $table->foreignId('dord_id')->nullable()->comment('Order');
            $table->foreignId('dfam_id')->nullable()->comment('Family');
            $table->foreignId('dspc_id')->nullable()->comment('Species OR Species common name');
            $table->foreignId('diop_id')->nullable()->comment('"Individual" or "Pooled"');
            $table->foreignId('dcat_id')->nullable();
            $table->string('dcat_other')->nullable();
            $table->foreignId('dht_id')->nullable()->comment('Habitat type');
            $table->string('eunis_habitat_type')->nullable();

            $table->string('head_length')->nullable();
            $table->string('bill_length')->nullable()->comment('Bill length [cm]');
            $table->string('wing_length')->nullable()->comment('Wing length [cm]');
            $table->string('tarsus_length')->nullable()->comment('Tarsus length [cm]');
            $table->string('tarsus_width')->nullable()->comment('Tarsus width [mm]');
        });
    }

    public function down(): void
    {
        Schema::table('empodat_matrix_biota', function (Blueprint $table) {
            $table->dropColumn([
                'biota_weight_dry',
                'dki_id',
                'dph_id',
                'dcla_id',
                'dord_id',
                'dfam_id',
                'dspc_id',
                'diop_id',
                'dcat_id',
                'dcat_other',
                'dht_id',
                'eunis_habitat_type',
                'head_length',
                'bill_length',
                'wing_length',
                'tarsus_length',
                'tarsus_width',
            ]);
        });
    }
};
