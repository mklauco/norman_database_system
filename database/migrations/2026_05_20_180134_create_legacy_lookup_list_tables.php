<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the 11 PG `list_*` tables needed for Phase 6c — the legacy
     * `data_*` lookups that back FK columns on `empodat_minor` /
     * `empodat_matrix_*` but for which the v1→v2 pgloader migration created
     * no PG counterpart.
     *
     * All tables share the canonical `(id bigint pk, name varchar, timestamps)`
     * shape used by every other `list_*` table in the database. The matching
     * data is loaded by `ImportSimpleLookupsStep` (Phase 6c additions);
     * FK constraints are reinstated in a follow-up migration (Phase 6d) once
     * the data has landed and parity has been verified.
     *
     * Three biota taxonomy tables (`list_orders`, `list_families`,
     * `list_habitat_types`) are INTENTIONALLY NOT created here — the
     * corresponding `data_*` dumps were not in the homesrv backup_alfac share
     * as of 2026-05-20. The `dord_id` / `dfam_id` / `dht_id` columns on
     * `empodat_matrix_biota` continue to store raw legacy integers without an
     * enforced FK. If dumps surface, follow up with a Phase 6e migration.
     */
    private const TABLES = [
        'list_kingdoms',
        'list_phyla',
        'list_classes',
        'list_individual_or_pooled',
        'list_categories',
        'list_biota_species',
        'list_measurements',
        'list_concentration_data',
        'list_prevalent_land_uses',
        'list_particle_sizes',
        'list_conc_normal_particle_sizes',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasTable($name)) {
                continue;
            }
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
