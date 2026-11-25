<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structure for the outdoor air records (legacy dct_analysis_air_outdoor).
     * Creates `list_air_proxy_pressures` (legacy data_proxy) and
     * `list_air_concentration_types` (legacy data_cto), both in the canonical
     * `(id bigint pk, name varchar, timestamps)` shape, and adds the nullable
     * smallints `dproxy_id` and `dcto_id` to `empodat_matrix_air`.
     *
     * `list_air_proxy_pressures` is separate from `list_proxy_pressures`: the
     * air codelist has its own five values and ids. No foreign keys and no
     * indexes, like the other columns of the matrix tables. The seeder
     * ReconcileOutdoorAirSeeder fills the lists and the records.
     */
    private const LISTS = [
        'list_air_proxy_pressures',
        'list_air_concentration_types',
    ];

    public function up(): void
    {
        foreach (self::LISTS as $name) {
            if (Schema::hasTable($name)) {
                continue;
            }

            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        Schema::table('empodat_matrix_air', function (Blueprint $table) {
            if (! Schema::hasColumn('empodat_matrix_air', 'dproxy_id')) {
                $table->smallInteger('dproxy_id')->nullable();
            }
            if (! Schema::hasColumn('empodat_matrix_air', 'dcto_id')) {
                $table->smallInteger('dcto_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empodat_matrix_air', function (Blueprint $table) {
            $table->dropColumn(['dproxy_id', 'dcto_id']);
        });

        foreach (array_reverse(self::LISTS) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
