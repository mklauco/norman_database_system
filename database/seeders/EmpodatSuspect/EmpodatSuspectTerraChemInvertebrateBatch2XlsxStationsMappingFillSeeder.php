<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resolve station_id for TerraChem INVERTEBRATE 2nd batch mapping rows via
 * direct lowercase equality on empodat_stations.sample_code.
 *
 * DEVIATION FROM EVERY EARLIER SOURCE: this one matches on `sample_code`, not
 * `short_sample_code`. The 2nd batch spreadsheet names its columns in the full
 * form — `TChem-CS2.4-PT-L6-Inv(NH-P)-2025` — which is what
 * empodat_stations.sample_code holds. `short_sample_code` for the same station
 * is `CS2.4-PT-L6-Inv(NH-P)`, with neither the `TChem-` prefix nor the year, so
 * the batch-1 strategy resolves 0 of 58 headers. Matching on sample_code
 * resolves all 58 uniquely, and each resolved id agrees with the station ids
 * in porovnanie_sample_code_invertebrates.xlsx (verified 2026-09-05).
 *
 * Note that porovnanie_sample_code_invertebrates.xlsx is a cross-reference, not
 * a source: its `Suspect` column was taken from an earlier revision and
 * disagrees with the delivered file on 30 of 58 rows. Its `NDS2 - sample_code`
 * column is the one that matches.
 *
 * Scoped to file_id = 10016. Handles non-uniqueness via MIN(s.id) + STRING_AGG.
 * Excludes deprecated stations.
 *
 * See: Empodat-Suspect-3-new-source.md §11 (TerraChem)
 */
class EmpodatSuspectTerraChemInvertebrateBatch2XlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10016;

    public function run(): void
    {
        $this->command->info('Resolving station_id for TerraChem INVERTEBRATE 2nd batch mapping rows (file_id='.self::FILE_ID.')...');

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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemInvertebrateBatch2XlsxStationsMappingFillSeeder
