<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resolve station_id for JDS5 GROUNDWATER mapping rows via direct lowercase
 * equality on empodat_stations.sample_code.
 *
 * Unlike the JDS5 BIOTA source (10017), this one is unambiguous: all 7 headers
 * resolve to exactly one station each (153915–153921) and there are no
 * duplicate station rows. `short_sample_code` is the short form `JDS5-GW1` and
 * matches nothing, so sample_code is the only usable column here. Verified
 * 2026-09-05.
 *
 * porovnanie_sample_code_gw.xlsx is a correction note, not a mapping table: it
 * records that an earlier revision wrote these codes with a dash
 * (`GW1-GRB:EISK`, "chyba: pomlcka") and that the underscore form is correct.
 * The delivered file already uses underscores, so nothing has to be corrected
 * here.
 *
 * Scoped to file_id = 10018. Handles non-uniqueness via MIN(s.id) + STRING_AGG.
 * Excludes deprecated stations.
 */
class EmpodatSuspectJds5GwXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10018;

    public function run(): void
    {
        $this->command->info('Resolving station_id for JDS5 GROUNDWATER mapping rows (file_id='.self::FILE_ID.')...');

        $sql = <<<'SQL'
            UPDATE empodat_suspect_xlsx_stations_mapping AS m
            SET
                station_id = sub.first_station_id,
                count      = sub.station_count,
                ids        = sub.all_station_ids,
                updated_at = NOW()
            FROM (
                SELECT
                    m.id AS mapping_id,
                    MIN(s.id)                                     AS first_station_id,
                    COUNT(s.id)                                   AS station_count,
                    STRING_AGG(s.id::text, ', ' ORDER BY s.id)    AS all_station_ids
                FROM empodat_suspect_xlsx_stations_mapping AS m
                LEFT JOIN empodat_stations AS s
                  ON LOWER(s.sample_code) = LOWER(m.xlsx_name)
                 AND (s.is_deprecated IS NULL OR s.is_deprecated = FALSE)
                WHERE m.file_id = :file_id
                GROUP BY m.id
            ) AS sub
            WHERE m.id = sub.mapping_id
        SQL;

        $affected = DB::update($sql, ['file_id' => self::FILE_ID]);

        $this->command->info("Updated {$affected} mapping rows.");
        $this->reportUnresolved();
    }

    protected function reportUnresolved(): void
    {
        $unresolved = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->where('file_id', self::FILE_ID)
            ->whereNull('station_id')
            ->pluck('xlsx_name')
            ->all();

        if (empty($unresolved)) {
            $this->command->info('All headers resolved for file_id='.self::FILE_ID.'.');

            return;
        }

        $this->command->warn(count($unresolved).' headers still unresolved for file_id='.self::FILE_ID.':');
        foreach ($unresolved as $name) {
            $this->command->warn('  - '.$name);
        }
        $this->command->warn('Append these to EMPODAT_SUSPECT-mapping-JS-<date>.csv and run EmpodatSuspectManualMappingFillSeeder.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5GwXlsxStationsMappingFillSeeder
