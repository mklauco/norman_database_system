<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resolve station_id for BlackSea Surface Water mapping rows via direct
 * lowercase equality on empodat_stations.short_sample_code.
 *
 * Scoped to file_id = 10011. Handles non-uniqueness via MIN(s.id) + STRING_AGG.
 * Excludes deprecated stations.
 *
 * See: Empodat-Suspect-new-source-onboarding.md §3a (Block D)
 */
class EmpodatSuspectBlackSeaSurfaceWaterXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10011;

    public function run(): void
    {
        $this->command->info('Resolving station_id for BlackSea Surface Water mapping rows (file_id='.self::FILE_ID.')...');

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
                  ON LOWER(s.short_sample_code) = LOWER(m.xlsx_name)
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaSurfaceWaterXlsxStationsMappingFillSeeder
