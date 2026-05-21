<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates `empodat_suspect_metadata` as a PostgreSQL list-partitioned side
     * table for HRMS identification metadata that the legacy `empodat_suspect_main`
     * schema does not capture (introduced for BlackSea / NKUA-format sources).
     *
     * Design:
     * - `id` mirrors `empodat_suspect_main.id` 1:1 (NOT auto-increment; populated
     *   by the per-source MainSeeder which captures the inserted main IDs).
     * - Partitioned LIST on `is_numeric_concentration` to match the parent table's
     *   partition layout, so a metadata row always lives in the same partition as
     *   its corresponding main row.
     * - No DB-level FK to `empodat_suspect_main` by design — referential integrity
     *   is enforced by the seeder (cross-partition FKs are awkward in PostgreSQL
     *   and we prefer schema simplicity here).
     * - Only sources whose XLSX includes the HRMS metadata block populate this
     *   table (BlackSea: yes; legacy 8: no; TerraChem: TBD).
     */
    public function up(): void
    {
        DB::statement('
            CREATE TABLE empodat_suspect_metadata (
                id BIGINT NOT NULL,
                is_numeric_concentration BOOLEAN NOT NULL,
                method VARCHAR(64) NULL,
                mz_score DOUBLE PRECISION NULL,
                isotopicfit_score DOUBLE PRECISION NULL,
                numoffragments_score DOUBLE PRECISION NULL,
                ddamsms_score DOUBLE PRECISION NULL,
                molecularfitfragments_score DOUBLE PRECISION NULL,
                rti_score DOUBLE PRECISION NULL,
                spectral_similarity DOUBLE PRECISION NULL,
                rt_avg DOUBLE PRECISION NULL,
                fragments TEXT NULL,
                based_on_similarity TEXT NULL,
                based_on_compound TEXT NULL,
                identification_proofs TEXT NULL,
                num_fragments DOUBLE PRECISION NULL,
                PRIMARY KEY (id, is_numeric_concentration)
            ) PARTITION BY LIST (is_numeric_concentration)
        ');

        DB::statement('
            CREATE TABLE empodat_suspect_metadata_numeric
                PARTITION OF empodat_suspect_metadata
                FOR VALUES IN (TRUE)
        ');

        DB::statement('
            CREATE TABLE empodat_suspect_metadata_nonnumeric
                PARTITION OF empodat_suspect_metadata
                FOR VALUES IN (FALSE)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Drops both partitions via CASCADE on the parent.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_metadata CASCADE');
    }
};
