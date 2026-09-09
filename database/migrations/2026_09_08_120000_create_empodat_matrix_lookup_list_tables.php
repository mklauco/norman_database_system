<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the four PG `list_*` tables still missing for the EMPODAT
     * matrix-metadata codelists reported in issue #22. Without them the
     * record modal prints a bare integer — "Dtw Id: 2" — instead of the
     * codelist text, because `EmpodatRecordDisplay::matrixLookups()` has no
     * table to resolve the id against.
     *
     * | new table                 | legacy source      | matrix column           |
     * |---------------------------|--------------------|-------------------------|
     * | list_air_locations        | data_location (13) | matrix_air.dloca_id     |
     * | list_air_sampling_modes   | data_smo (4)       | matrix_air.dsmo_id      |
     * | list_type_wastes          | data_type_waste (6)| water_waste.dtw_id      |
     * | list_sewage_sludges       | data_sewage_sludge (9) | sewage_sludge.dss_id |
     *
     * `list_air_locations` is deliberately NOT folded into the existing
     * `list_locations`: that table holds legacy `data_loc` (4 rows) and the
     * ids CLASH — `data_loc` id 4 is "Ambient air - Industrial area" while
     * `data_location` id 4 is "Industrial area - in activity". They are two
     * separate legacy codelists that happen to overlap in wording.
     *
     * `list_air_sampling_modes` is likewise separate from
     * `list_sampling_methods`: legacy `data_smo` agrees with
     * `data_sampling_method` on ids 1-3 by coincidence only and diverges at
     * id 4 ("NR" vs "LVSPE MAXX Sampler").
     *
     * Two further codelists need no table here — `list_treatment_plants`
     * (dtp_id) and `list_advanced_treatment_steps` (dtt_id) were already
     * created by `2024_05_29_190145_create_empodat_lists_tables.php` and
     * have simply stood empty ever since; they are filled by the matching
     * seeders.
     *
     * All four share the canonical `(id bigint pk, name varchar, timestamps)`
     * shape used by every other `list_*` table, and legacy ids are preserved
     * 1:1 so the existing FK columns resolve without remapping. FK
     * constraints are intentionally not added: the matrix columns are raw
     * smallints today and several carry legacy values with no codelist row.
     */
    private const TABLES = [
        'list_air_locations',
        'list_air_sampling_modes',
        'list_type_wastes',
        'list_sewage_sludges',
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
