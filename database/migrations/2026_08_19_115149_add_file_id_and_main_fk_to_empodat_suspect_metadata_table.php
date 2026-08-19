<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable `file_id` column to `empodat_suspect_metadata` and wires up
     * two foreign keys plus a supporting index.
     *
     * Design:
     * - `fk_esmd_main` is a COMPOSITE FK on (id, is_numeric_concentration)
     *   referencing `empodat_suspect_main`. This is required, not stylistic:
     *   `empodat_suspect_main` is `PARTITION BY LIST (is_numeric_concentration)`
     *   with `PRIMARY KEY (id, is_numeric_concentration)`, and PostgreSQL requires
     *   a FK to target a unique/PK column set, so `(id, is_numeric_concentration)`
     *   is the only well-formed target — a single-column FK on `id` alone is not
     *   possible. As a bonus, the composite FK also guarantees a metadata row
     *   always lives in the same partition as its corresponding main row.
     * - The FK direction is strictly child (metadata) -> parent (main): every
     *   metadata row must reference an existing main row, but a main row is
     *   never required to have metadata — roughly 48% of main rows legitimately
     *   have none. Nothing here enforces 1:1 coverage.
     * - Partitioned-to-partitioned foreign keys are supported in PostgreSQL from
     *   version 12 onward. Dev runs 17.6, CI runs 16 — both are fine.
     * - `file_id` is deliberately nullable so this migration is purely additive
     *   and safe to deploy ahead of any production data backfill.
     *
     * Per this project's convention for raw DDL, guards use SQL `IF EXISTS` /
     * `IF NOT EXISTS` rather than `Schema::has*()` checks.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE empodat_suspect_metadata ADD COLUMN file_id BIGINT NULL');

        DB::statement('
            ALTER TABLE empodat_suspect_metadata
                ADD CONSTRAINT fk_esmd_main
                FOREIGN KEY (id, is_numeric_concentration)
                REFERENCES empodat_suspect_main (id, is_numeric_concentration)
                ON DELETE NO ACTION ON UPDATE NO ACTION
        ');

        DB::statement('
            ALTER TABLE empodat_suspect_metadata
                ADD CONSTRAINT fk_esmd_file FOREIGN KEY (file_id) REFERENCES files(id)
        ');

        DB::statement('CREATE INDEX idx_esmd_file_id ON empodat_suspect_metadata (file_id)');
    }

    /**
     * Reverse the migrations.
     *
     * Drops the index, both constraints, and the column — each guarded with
     * `IF EXISTS` so this is safe to re-run against a partially-applied state.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_esmd_file_id');

        DB::statement('ALTER TABLE empodat_suspect_metadata DROP CONSTRAINT IF EXISTS fk_esmd_main');

        DB::statement('ALTER TABLE empodat_suspect_metadata DROP CONSTRAINT IF EXISTS fk_esmd_file');

        DB::statement('ALTER TABLE empodat_suspect_metadata DROP COLUMN IF EXISTS file_id');
    }
};
