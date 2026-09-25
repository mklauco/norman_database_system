<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Shared body of the two CONNECT 2 station-mapping FILL seeders (10003, 10004).
 *
 * Resolves `empodat_suspect_xlsx_stations_mapping.station_id` by exact
 * (case-insensitive) equality between the column heading and
 * `empodat_stations.short_sample_code` — the same rule the newer sources use.
 *
 * WHY THIS REPLACES THE OLD PATTERN MATCHING
 * ------------------------------------------
 * The previous CONNECT 2 fill seeders parsed an ordinal out of a long
 * descriptive heading ("CONnECTII 14 Sediment from Gulf of Cadiz in Spain…")
 * and rebuilt a sample code from it. It never matched: these stations carry an
 * empty `short_sample_code` under that spelling, which is why every CONNECT 2
 * mapping row — and therefore every CONNECT 2 main row — sat at
 * `station_id = NULL`.
 *
 * The v2 spreadsheets rename the headings to the station codes themselves
 * (`CONnECT_II_14`, `Dnieper_II_20`), which DO exist in `empodat_stations`,
 * one row each. So the resolution is now a plain equality join with no parsing.
 *
 * SCOPE AND STRICTNESS
 * --------------------
 *   - The UPDATE is scoped to a single `file_id`. The old seeders updated every
 *     row matching a LIKE pattern, across files.
 *   - Deprecated stations are excluded.
 *   - Afterwards, EVERY mapping row of this file must have a `station_id`, and
 *     no heading may match more than one station. Either failure throws, because
 *     importing on top of an unresolved mapping is what produced the NULLs in
 *     the first place — and a NULL station is invisible in station-filtered
 *     searches rather than obviously broken.
 *
 * `count` and `ids` are populated alongside `station_id`, as before, so an
 * ambiguous heading remains visible in the table itself.
 */
abstract class EmpodatSuspectConnect2MappingFillSeederBase extends Seeder
{
    use WithoutModelEvents;

    abstract protected function fileId(): int;

    public function run(): void
    {
        $fileId = $this->fileId();

        $this->command->info("Resolving station_id for file_id={$fileId} via empodat_stations.short_sample_code...");

        $updated = DB::update(<<<'SQL'
            UPDATE empodat_suspect_xlsx_stations_mapping m
            SET station_id = matched.first_station_id,
                count      = matched.station_count,
                ids        = matched.all_station_ids,
                updated_at = NOW()
            FROM (
                SELECT
                    m.id                                        AS mapping_id,
                    COUNT(s.id)                                 AS station_count,
                    MIN(s.id)                                   AS first_station_id,
                    STRING_AGG(s.id::text, ', ' ORDER BY s.id)  AS all_station_ids
                FROM empodat_suspect_xlsx_stations_mapping m
                LEFT JOIN empodat_stations s
                       ON LOWER(s.short_sample_code) = LOWER(m.xlsx_name)
                      AND (s.is_deprecated IS NULL OR s.is_deprecated = false)
                WHERE m.file_id = ?
                GROUP BY m.id
            ) AS matched
            WHERE m.id = matched.mapping_id
        SQL, [$fileId]);

        $this->command->info("Updated {$updated} mapping row(s).");

        $this->assertFullyResolved($fileId);
    }

    /**
     * Every heading must resolve, to exactly one station.
     */
    private function assertFullyResolved(int $fileId): void
    {
        $rows = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->where('file_id', $fileId)
            ->orderBy('id')
            ->get(['xlsx_name', 'station_id', 'count']);

        if ($rows->isEmpty()) {
            throw new RuntimeException("ABORTED: no mapping rows exist for file_id={$fileId} — run the mapping seeder first.");
        }

        $unresolved = $rows->whereNull('station_id');
        $ambiguous = $rows->filter(static fn (object $row): bool => (int) $row->count > 1);

        if ($unresolved->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'ABORTED: %d of %d heading(s) for file_id=%d did not resolve to a station: %s. '
                .'Importing now would write rows with station_id = NULL.',
                $unresolved->count(),
                $rows->count(),
                $fileId,
                $unresolved->pluck('xlsx_name')->implode(', '),
            ));
        }

        if ($ambiguous->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'ABORTED: %d heading(s) for file_id=%d match more than one station: %s. '
                .'The mapping would silently take the lowest station id.',
                $ambiguous->count(),
                $fileId,
                $ambiguous->map(static fn (object $r): string => $r->xlsx_name.' ('.$r->count.' matches)')->implode(', '),
            ));
        }

        $this->command->info("All {$rows->count()} heading(s) resolved to exactly one station:");

        foreach ($rows as $row) {
            $this->command->line('  - '.$row->xlsx_name.' -> station_id='.$row->station_id);
        }
    }
}
