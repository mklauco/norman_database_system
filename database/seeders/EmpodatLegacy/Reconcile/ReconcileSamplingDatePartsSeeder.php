<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Copies the sampling month and day from legacy dct_analysis
 * (sampling_date_m, sampling_date_d) into empodat_minor.sampling_date_m /
 * sampling_date_d, which are NULL on every row.
 *
 * Value rule: a legacy value greater than 0 becomes a two-digit string
 * (sprintf('%02d')); 0 or NULL leaves the column NULL. Month and day are
 * independent (month 5 and day 0 gives '05' and NULL). Nothing is range
 * validated; values above 12 (month) or 31 (day) are only counted and reported.
 * Only rows where BOTH target columns are NULL are written, so a stopped run
 * can simply be started again with the same or a later window.
 *
 * About 115M rows are in scope, so it runs in staged id windows of roughly
 * 10M ids (RECONCILE_ID_FROM / RECONCILE_ID_TO, inclusive). Each window needs
 * its own dry run: the execute needs RECONCILE_EXPECT_COUNT from that window's
 * dry run. Every updated row becomes a new tuple, so the table can grow
 * towards 2x until vacuumed: run `VACUUM (ANALYZE) empodat_minor` between
 * stages.
 *
 * Production, dry run:
 *   docker exec -e RECONCILE_ID_FROM=<a> -e RECONCILE_ID_TO=<b> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileSamplingDatePartsSeeder' --force
 *
 * Production, execute:
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> -e RECONCILE_ID_FROM=<a> -e RECONCILE_ID_TO=<b> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileSamplingDatePartsSeeder' --force
 */
final class ReconcileSamplingDatePartsSeeder extends ReconcileSeeder
{
    private const STEP = 'Fill sampling month/day';

    private const TABLE = 'empodat_minor';

    private const SUB_BATCH_ROWS = 15000;

    private const PROGRESS_SECONDS = 10.0;

    private int $legacyRows = 0;

    private int $skippedRows = 0;

    private int $changedRows = 0;

    private int $monthOutOfRange = 0;

    private int $dayOutOfRange = 0;

    protected function plan(): int
    {
        $window = $this->window();
        if ($window === null) {
            return 0;
        }

        if ($this->options->idFrom === null && $this->options->idTo === null) {
            $this->say('WARNING: no id window set, the whole table is in scope. Production should use staged windows (RECONCILE_ID_FROM / RECONCILE_ID_TO).');
        }
        $this->say('Scanning ids '.$window[0].' .. '.$window[1].' in chunks of '.$this->options->chunk.'.');

        return $this->walk($window[0], $window[1], null);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $window = $this->window();
        if ($window === null) {
            return;
        }

        $this->say('Writing ids '.$window[0].' .. '.$window[1].' in chunks of '.$this->options->chunk.'.');

        $written = $this->walk($window[0], $window[1], $audit);

        if ($this->options->expectCount !== null && $written !== $this->options->expectCount) {
            $this->say('WARNING: wrote '.$written.' rows but the dry run planned '.$this->options->expectCount.' (data changed in between, or a concurrent writer).');
        }
    }

    protected function notes(): string
    {
        return 'sampling month/day from legacy dct_analysis, window '.$this->options->windowLabel();
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function window(): ?array
    {
        $bounds = $this->pg->selectOne('SELECT MIN(id) AS lo, MAX(id) AS hi FROM '.self::TABLE);
        if ($bounds === null || $bounds->lo === null) {
            $this->say('empodat_minor is empty.');

            return null;
        }

        $from = $this->options->idFrom ?? (int) $bounds->lo;
        $to = $this->options->idTo ?? (int) $bounds->hi;

        if ($to < $from) {
            $this->say('Empty window: id '.$from.' .. '.$to.'.');

            return null;
        }

        return [$from, $to];
    }

    /**
     * Walks the window one chunk at a time. Without an audit it only counts;
     * with one it writes each chunk in its own transaction.
     */
    private function walk(int $from, int $to, ?ReconcileAudit $audit): int
    {
        $this->legacyRows = 0;
        $this->skippedRows = 0;
        $this->changedRows = 0;
        $this->monthOutOfRange = 0;
        $this->dayOutOfRange = 0;

        $total = $to - $from + 1;
        $startedAt = microtime(true);
        $lastReport = $startedAt;

        for ($lo = $from; $lo <= $to; $lo += $this->options->chunk) {
            $hi = min($lo + $this->options->chunk - 1, $to);

            $rows = $this->candidates($lo, $hi);
            if ($audit !== null && $rows !== []) {
                $this->writeChunk($audit, $rows);
            }

            $now = microtime(true);
            if ($now - $lastReport >= self::PROGRESS_SECONDS) {
                $this->progress($hi - $from + 1, $total, $now - $startedAt);
                $lastReport = $now;
            }
        }

        $this->progress($total, $total, microtime(true) - $startedAt);
        $this->say('Legacy rows with month/day: '.$this->legacyRows);
        $this->say('Skipped (already filled in empodat_minor, or id missing there): '.$this->skippedRows);
        $this->say(($audit === null ? 'Rows that would change: ' : 'Rows changed: ').$this->changedRows);
        $this->say('Out of range among changed rows: month > 12: '.$this->monthOutOfRange.', day > 31: '.$this->dayOutOfRange);

        return $this->changedRows;
    }

    /**
     * @return list<array{0: int, 1: string|null, 2: string|null}> id, month, day of legacy rows that can be written
     */
    private function candidates(int $lo, int $hi): array
    {
        $legacyRows = $this->legacy->select(
            'SELECT id, sampling_date_m, sampling_date_d FROM dct_analysis WHERE id BETWEEN ? AND ? AND (sampling_date_m > 0 OR sampling_date_d > 0)',
            [$lo, $hi],
        );
        $this->pauseLegacy();

        if ($legacyRows === []) {
            return [];
        }
        $this->legacyRows += count($legacyRows);

        $eligible = [];
        $pgRows = $this->pg->select(
            'SELECT id FROM '.self::TABLE.' WHERE id BETWEEN ? AND ? AND sampling_date_m IS NULL AND sampling_date_d IS NULL',
            [$lo, $hi],
        );
        foreach ($pgRows as $row) {
            $eligible[(int) $row->id] = true;
        }

        $rows = [];
        foreach ($legacyRows as $row) {
            $id = (int) $row->id;
            if (! isset($eligible[$id])) {
                $this->skippedRows++;

                continue;
            }

            $month = (int) $row->sampling_date_m;
            $day = (int) $row->sampling_date_d;
            if ($month > 12) {
                $this->monthOutOfRange++;
            }
            if ($day > 31) {
                $this->dayOutOfRange++;
            }

            $rows[] = [
                $id,
                $month > 0 ? sprintf('%02d', $month) : null,
                $day > 0 ? sprintf('%02d', $day) : null,
            ];
        }

        $this->changedRows += count($rows);

        return $rows;
    }

    /**
     * @param  list<array{0: int, 1: string|null, 2: string|null}>  $rows
     */
    private function writeChunk(ReconcileAudit $audit, array $rows): void
    {
        $this->pg->transaction(function () use ($audit, $rows): void {
            $startedAt = hrtime(true);
            $updatedIds = [];

            foreach (array_chunk($rows, self::SUB_BATCH_ROWS) as $batch) {
                $placeholders = [];
                $bindings = [];
                foreach ($batch as $index => [$id, $month, $day]) {
                    $placeholders[] = $index === 0 ? '(?::bigint, ?::varchar, ?::varchar)' : '(?, ?, ?)';
                    array_push($bindings, $id, $month, $day);
                }

                $returned = $this->pg->select(
                    'UPDATE '.self::TABLE.' AS t SET sampling_date_m = v.m, sampling_date_d = v.d '
                    .'FROM (VALUES '.implode(', ', $placeholders).') AS v(id, m, d) '
                    .'WHERE t.id = v.id AND t.sampling_date_m IS NULL AND t.sampling_date_d IS NULL '
                    .'RETURNING t.id',
                    $bindings,
                );
                foreach ($returned as $row) {
                    $updatedIds[] = (int) $row->id;
                }
            }

            $audit->logFill(
                self::STEP,
                self::TABLE,
                ['sampling_date_m', 'sampling_date_d'],
                $updatedIds,
                null,
                (int) ((hrtime(true) - $startedAt) / 1_000_000),
            );
        });
    }

    private function progress(int $done, int $total, float $elapsed): void
    {
        $rate = $elapsed > 0 ? (int) ($done / $elapsed) : 0;
        $this->say(sprintf(
            '%s / %s ids (%.1f%%), %s ids/s, legacy %s, skipped %s, changed %s',
            number_format($done, 0, '.', ' '),
            number_format($total, 0, '.', ' '),
            $total > 0 ? 100 * $done / $total : 100.0,
            number_format($rate, 0, '.', ' '),
            number_format($this->legacyRows, 0, '.', ' '),
            number_format($this->skippedRows, 0, '.', ' '),
            number_format($this->changedRows, 0, '.', ' '),
        ));
    }
}
