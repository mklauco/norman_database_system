<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resolve station_id for JDS5 BIOTA mapping rows via direct lowercase equality
 * on empodat_stations.short_sample_code.
 *
 * WHY short_sample_code AND NOT sample_code. Both columns contain the full
 * header form, e.g. `JDS5-2-P-FI_CHEM:WRISK`, so both match all 43 headers —
 * but empodat_stations holds four near-identical rows per station (see
 * Empodat-Suspect-3-new-source.md §6, "Ambiguous station headers"). Only one of
 * those four carries the full code in short_sample_code; the other three keep
 * the short form `JDS5-2`. So matching on short_sample_code resolves 42 of 43
 * headers to a single station, where sample_code would return all four copies
 * for every header. Measured 2026-09-05.
 *
 * The remaining header, `JDS5-6-P-FI_CHEM:WRISK`, matches two stations —
 * 154113 and 154144, identical apart from provider_code 3999 vs 5100. That is
 * recorded as count=2 with both ids in `ids`, exactly as for the 60 such rows
 * already carried by files 10002, 10008, 10010 and 10011. Which provider code
 * is correct is an open question for the data provider; it does not block the
 * import.
 *
 * Note that porovnanie_sample_code_biota.xlsx is a cross-reference, not a
 * source. Its `Suspect` column is stale in one place — it lists
 * `JDS5-6-P-FI_CHEM:WRISK2` where the delivered file already uses the corrected
 * `JDS-48-P-FI_CHEM:WRISK` — and it carries no station ids at all, unlike the
 * invertebrate one.
 *
 * Scoped to file_id = 10017. Handles non-uniqueness via MIN(s.id) + STRING_AGG.
 * Excludes deprecated stations.
 */
class EmpodatSuspectJds5BiotaXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10017;

    public function run(): void
    {
        $this->command->info('Resolving station_id for JDS5 BIOTA mapping rows (file_id='.self::FILE_ID.')...');

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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5BiotaXlsxStationsMappingFillSeeder
