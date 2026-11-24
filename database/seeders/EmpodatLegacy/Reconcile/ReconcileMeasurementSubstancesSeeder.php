<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Fills empodat_main.substance_id where it is NULL and legacy dct_analysis.sus_id
 * holds a substance. Never overwrites a non-empty value.
 *
 * Mapping as in the original import: code = LPAD(sus_id, 8, '0') matched to
 * susdat_substances.code (direct id, canonical_id is not followed). The NDS2.0
 * code is trimmed of whitespace first (some carry a trailing carriage return).
 * A row is filled only when exactly one susdat_substances row has that code.
 *
 * Skipped and reported separately: legacy sus_id 0 or NULL, code not found,
 * code ambiguous (several susdat_substances rows; deciding is not ours, the
 * candidate ids are printed).
 *
 * Dry run: the same command without the -e flags.
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileMeasurementSubstancesSeeder' --force
 */
final class ReconcileMeasurementSubstancesSeeder extends ReconcileSeeder
{
    private const STEP = 'Fill measurement substance';

    private const TABLE = 'empodat_main';

    private const LEGACY_CHUNK = 1000;

    private const WRITE_CHUNK = 5000;

    private const EXAMPLES = 5;

    /** @var array<int, int> empodat_main.id => susdat_substances.id to write */
    private array $work = [];

    protected function plan(): int
    {
        $this->work = [];

        $emptyIds = array_map(
            static fn (object $row): int => (int) $row->id,
            $this->pg->select('SELECT id FROM '.self::TABLE.' WHERE substance_id IS NULL ORDER BY id'),
        );

        /** @var array<string, list<int>> $byCode */
        $byCode = [];
        foreach ($this->pg->select('SELECT id, code FROM susdat_substances') as $row) {
            $byCode[trim((string) $row->code, " \t\r\n")][] = (int) $row->id;
        }

        $noSubstance = [];
        $notFound = [];
        $missingInLegacy = [];
        $ambiguousRows = [];
        $ambiguousIds = [];

        foreach (array_chunk($emptyIds, self::LEGACY_CHUNK) as $chunk) {
            $found = [];
            $rows = $this->legacy->select(
                'SELECT id, sus_id FROM dct_analysis WHERE id IN ('.implode(',', array_fill(0, count($chunk), '?')).')',
                $chunk,
            );
            $this->pauseLegacy();

            foreach ($rows as $row) {
                $id = (int) $row->id;
                $susId = (int) $row->sus_id;
                $found[$id] = true;

                if ($susId <= 0) {
                    $noSubstance[] = $id;

                    continue;
                }

                $code = str_pad((string) $susId, 8, '0', STR_PAD_LEFT);
                $candidates = $byCode[$code] ?? [];

                if ($candidates === []) {
                    $notFound[] = $id;
                } elseif (count($candidates) > 1) {
                    $ambiguousIds[$code] = $candidates;
                    $ambiguousRows[$code] = ($ambiguousRows[$code] ?? 0) + 1;
                } else {
                    $this->work[$id] = $candidates[0];
                }
            }
            foreach ($chunk as $id) {
                if (! isset($found[$id])) {
                    $missingInLegacy[] = $id;
                }
            }
        }

        $this->say('NDS2.0 empty rows: '.count($emptyIds));
        $this->say('Rows to fill: '.count($this->work));
        $this->reportSkip('legacy sus_id 0/NULL', $noSubstance);
        $this->reportSkip('code not found in susdat_substances', $notFound);
        $this->reportSkip('id missing in legacy', $missingInLegacy);
        $this->say('Skipped, code ambiguous: '.array_sum($ambiguousRows).' rows, '.count($ambiguousRows).' codes');
        ksort($ambiguousRows);
        foreach ($ambiguousRows as $code => $rows) {
            $this->say('  '.$code.': '.$rows.' rows, candidate susdat_substances ids '.implode(', ', $ambiguousIds[$code]));
        }

        return count($this->work);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $written = 0;

        foreach (array_chunk($this->work, self::WRITE_CHUNK, true) as $chunk) {
            $this->pg->transaction(function () use ($audit, $chunk, &$written): void {
                $startedAt = hrtime(true);
                $placeholders = [];
                $bindings = [];
                foreach ($chunk as $id => $substanceId) {
                    $placeholders[] = $bindings === [] ? '(?::bigint, ?::bigint)' : '(?, ?)';
                    array_push($bindings, $id, $substanceId);
                }

                $returned = $this->pg->select(
                    'UPDATE '.self::TABLE.' AS t SET substance_id = v.val '
                    .'FROM (VALUES '.implode(', ', $placeholders).') AS v(id, val) '
                    .'WHERE t.id = v.id AND t.substance_id IS NULL RETURNING t.id',
                    $bindings,
                );
                $ids = array_map(static fn (object $row): int => (int) $row->id, $returned);

                $audit->logFill(self::STEP, self::TABLE, ['substance_id'], $ids, null, (int) ((hrtime(true) - $startedAt) / 1_000_000));
                $written += count($ids);
            });
            $this->say('Written '.$written.' / '.count($this->work));
        }

        if ($this->options->expectCount !== null && $written !== $this->options->expectCount) {
            $this->say('WARNING: wrote '.$written.' rows but the dry run planned '.$this->options->expectCount.'.');
        }
    }

    protected function notes(): string
    {
        return 'empodat_main.substance_id from legacy dct_analysis.sus_id where NDS2.0 is NULL';
    }

    /**
     * @param  list<int>  $ids
     */
    private function reportSkip(string $reason, array $ids): void
    {
        $this->say('Skipped, '.$reason.': '.count($ids).($ids === [] ? '' : ' (e.g. '.implode(', ', array_slice($ids, 0, self::EXAMPLES)).')'));
    }
}
