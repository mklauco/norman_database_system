<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Reads the legacy dct_analysis rows that lack an empodat_main row, in id
 * windows, and aggregates what they reference: SUSDAT codes, analytical
 * methods, data sources, matrices and countries. Read-only; every window is
 * one short PK-range GROUP BY. In a dry run the runs are already limited to the
 * dry-run row cap, so this reads only that many rows.
 */
final class LegacyMainScan
{
    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $runs  missing-id runs of dct_analysis
     * @return array{
     *     rows: int,
     *     sus: array<string, int>,
     *     method: array<int, int>,
     *     dataSource: array<int, int>,
     *     matrix: array<int, int>,
     *     country: array<string, int>
     * }
     */
    public static function run(BackfillContext $ctx, array $runs): array
    {
        $result = [
            'rows' => 0,
            'sus' => [],
            'method' => [],
            'dataSource' => [],
            'matrix' => [],
            'country' => [],
        ];

        $window = $ctx->options->scanWindow;
        $windows = 0;

        foreach ($runs as [$from, $to]) {
            for ($lo = $from; $lo <= $to; $lo += $window) {
                $hi = min($to, $lo + $window - 1);

                $groups = $ctx->legacy->select(
                    'SELECT sus_id, id_method, id_data, `matrix` AS matrix_id, country, COUNT(*) AS cnt FROM dct_analysis '
                    .'WHERE id >= ? AND id <= ? GROUP BY sus_id, id_method, id_data, `matrix`, country',
                    [$lo, $hi],
                );
                foreach ($groups as $g) {
                    $count = (int) $g->cnt;
                    $result['rows'] += $count;
                    $sus = str_pad((string) $g->sus_id, 8, '0', STR_PAD_LEFT);
                    $result['sus'][$sus] = ($result['sus'][$sus] ?? 0) + $count;
                    if ($g->id_method !== null) {
                        $result['method'][(int) $g->id_method] = ($result['method'][(int) $g->id_method] ?? 0) + $count;
                    }
                    if ($g->id_data !== null) {
                        $result['dataSource'][(int) $g->id_data] = ($result['dataSource'][(int) $g->id_data] ?? 0) + $count;
                    }
                    if ($g->matrix_id !== null) {
                        $result['matrix'][(int) $g->matrix_id] = ($result['matrix'][(int) $g->matrix_id] ?? 0) + $count;
                    }
                    $country = trim((string) $g->country);
                    $result['country'][$country] = ($result['country'][$country] ?? 0) + $count;
                }

                $ctx->pauseLegacy();
                if (++$windows % 10 === 0) {
                    $ctx->progress(sprintf('scanned %d windows, %d rows so far (run %d-%d, at id %d)', $windows, $result['rows'], $from, $to, $hi));
                }
            }
        }

        return $result;
    }
}
