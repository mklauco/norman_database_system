<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resolve station_id for TerraChem SOIL 2nd batch mapping rows via direct
 * lowercase equality on empodat_stations.sample_code.
 *
 * Unambiguous: all 17 headers resolve to exactly one station each, no duplicate
 * rows, no collisions with any other file's mapping rows, and
 * `short_sample_code` matches none of them.
 *
 * Cross-checked against porovnanie_sample_code_soil.xlsx, which supplies
 * station ids directly: all 17 resolved ids match the ids that sheet asserts,
 * with zero differences. Verified 2026-09-05. Note that sheet also carries a
 * `Sample code - Empodat - ALL` column listing every TerraChem soil code in
 * empodat, 71 rows — that is a reference list, not part of the mapping.
 *
 * Scoped to file_id = 10021. Handles non-uniqueness via MIN(s.id) + STRING_AGG.
 * Excludes deprecated stations.
 */
class EmpodatSuspectTerraChemSoilBatch2XlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10021;

    public function run(): void
    {
        $this->command->info('Resolving station_id for TerraChem SOIL 2nd batch mapping rows (file_id='.self::FILE_ID.')...');

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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemSoilBatch2XlsxStationsMappingFillSeeder
