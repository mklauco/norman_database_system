<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;
use Throwable;

/**
 * empodat_minor and every empodat_matrix_* table: rows keyed by the same id as
 * empodat_main. Diff-driven per table; a row is copied only when its id exists
 * in PG empodat_main (checked per chunk, after the main step has run).
 *
 * Dry run and execute share one pipeline: the same legacy reads, the same
 * conversion, the same eligibility check. Only the final COPY is replaced by a
 * count in a dry run, which also stops after the dry-run row cap.
 */
final class SidecarBackfill
{
    public function __construct(
        private readonly BackfillContext $ctx,
        private readonly GapFinder $gaps,
    ) {}

    /**
     * @return array{
     *     pgTable: string,
     *     legacyTable: string,
     *     gap: array<string, mixed>,
     *     rowsRead: int,
     *     inserted: int,
     *     wouldInsert: int,
     *     eligibleMainExists: int,
     *     eligibleMainAddedByRun: int,
     *     skippedNoMain: int,
     *     nulledValues: array<string, int>,
     *     nullFilledColumns: list<string>,
     *     samples: list<array<string, string|null>>
     * }
     */
    public function run(TableSpec $spec, bool $write): array
    {
        $audit = $write ? ($this->ctx->audit ?? throw new RuntimeException('SidecarBackfill write without an audit run.')) : null;
        $step = 'Backfill '.$spec->pgTable.' ('.$spec->legacyTable.')';
        $gap = $this->gaps->missing($spec->legacyTable, $spec->pgTable, $this->ctx->options->rowCap());

        $stats = [
            'pgTable' => $spec->pgTable,
            'legacyTable' => $spec->legacyTable,
            'gap' => $gap,
            'rowsRead' => 0,
            'inserted' => 0,
            'wouldInsert' => 0,
            'eligibleMainExists' => 0,
            'eligibleMainAddedByRun' => 0,
            'skippedNoMain' => 0,
            'nulledValues' => [],
            'nullFilledColumns' => $spec->nullFilled(),
            'samples' => [],
        ];
        $select = implode(', ', array_map(fn (string $c): string => $this->ctx->quoteLegacy($c), $spec->legacySelect()));
        $table = $this->ctx->quoteLegacy($spec->legacyTable);
        $chunk = $this->ctx->options->chunkRows;
        $columns = $spec->pgColumns();

        try {
            foreach ($gap['runs'] as [$from, $to]) {
                $cursor = $from;
                while ($cursor <= $to) {
                    $rows = $this->ctx->legacy->select(
                        'SELECT '.$select.' FROM '.$table.' WHERE id >= ? AND id <= ? ORDER BY id LIMIT '.$chunk,
                        [$cursor, $to],
                    );
                    if ($rows === []) {
                        break;
                    }
                    $this->ctx->pauseLegacy();

                    $started = microtime(true);
                    $ids = array_map(static fn (object $r): int => (int) $r->id, $rows);
                    $inMain = array_flip(array_map('intval', $this->ctx->pg->table('empodat_main')->whereIn('id', $ids)->pluck('id')->all()));
                    $addedByRun = $write ? [] : $this->legacyMainIds(array_values(array_diff($ids, array_keys($inMain))));

                    $lines = [];
                    foreach ($rows as $r) {
                        $id = (int) $r->id;
                        if (isset($inMain[$id])) {
                            $stats['eligibleMainExists']++;
                        } elseif (isset($addedByRun[$id])) {
                            $stats['eligibleMainAddedByRun']++;
                        } else {
                            $stats['skippedNoMain']++;

                            continue;
                        }

                        $converted = $spec->convert($r);
                        foreach ($converted['nulled'] as $column) {
                            $stats['nulledValues'][$column] = ($stats['nulledValues'][$column] ?? 0) + 1;
                        }
                        if (count($stats['samples']) < $this->ctx->options->sampleRows) {
                            $stats['samples'][] = $converted['values'];
                        }
                        $lines[] = ValueCoercer::copyLine(array_values($converted['values']));
                    }
                    $stats['rowsRead'] += count($rows);

                    if ($audit !== null) {
                        $inserted = $this->ctx->pg->transaction(function () use ($spec, $columns, $lines, $audit, $step, $started): array {
                            $insertedIds = CopyWriter::insertMissing($this->ctx->pg, $spec->pgTable, $columns, $lines);
                            $audit->chunk($step, $spec->pgTable, $insertedIds, (int) ((microtime(true) - $started) * 1000));

                            return $insertedIds;
                        });
                        $stats['inserted'] += count($inserted);
                    } else {
                        $stats['wouldInsert'] += count($lines);
                    }

                    $cursor = $ids[array_key_last($ids)] + 1;
                    $this->ctx->progress(sprintf(
                        '%s: %d legacy rows read, %d %s, %d skipped (no main row), at id %d',
                        $spec->pgTable,
                        $stats['rowsRead'],
                        $write ? $stats['inserted'] : $stats['wouldInsert'],
                        $write ? 'inserted' : 'would be inserted',
                        $stats['skippedNoMain'],
                        $cursor - 1,
                    ));
                }
            }
        } catch (Throwable $e) {
            $audit?->failedStep($step, $spec->pgTable, $e);
            throw $e;
        }

        return $stats;
    }

    /**
     * Ids that legacy dct_analysis has (so the main step of a real run adds
     * them to empodat_main before this table is processed). Dry run only.
     *
     * @param  list<int>  $ids
     * @return array<int, true>
     */
    private function legacyMainIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $found = [];
        foreach (array_chunk($ids, 5000) as $chunk) {
            foreach ($this->ctx->legacy->table('dct_analysis')->whereIn('id', $chunk)->pluck('id') as $id) {
                $found[(int) $id] = true;
            }
        }
        $this->ctx->pauseLegacy();

        return $found;
    }
}
