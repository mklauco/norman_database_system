<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * Repairs empodat_analytical_methods.sample_preparation_method_other. The first
 * import wrote the number of the preparation method (dpm_id) into the column
 * instead of the free text (dpm_other). A row is repaired only where the column
 * still holds exactly its own sample_preparation_method_id as text and legacy
 * dct_analytical_method (id_method = NDS2.0 id) has the same dpm_id. Then the
 * column gets the trimmed legacy dpm_other, or NULL where legacy dpm_other is
 * blank or 0. Rows where legacy text is the same number, or that fail any other
 * condition, are left alone. Only `sample_preparation_method_other` is written.
 *
 * The run stops before anything is written unless exactly 8710 rows get a legacy
 * text and 96 rows get NULL. Rerun after a successful run plans 0 rows.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileSamplePreparationTextsSeeder' --force
 */
final class ReconcileSamplePreparationTextsSeeder extends ReconcileSeeder
{
    private const STEP = 'Sample preparation texts';

    private const TABLE = 'empodat_analytical_methods';

    private const COLUMN = 'sample_preparation_method_other';

    private const CHUNK = 1000;

    private const MAX_LENGTH = 255;

    private const EXPECTED_TEXTS = 8710;

    private const EXPECTED_NULLS = 96;

    /** @var array<int, array{old: string, new: ?string}> method id => old and new value */
    private array $changes = [];

    private int $texts = 0;

    private int $nulls = 0;

    private int $sameNumber = 0;

    private int $skipped = 0;

    private int $tooLong = 0;

    protected function plan(): int
    {
        $this->changes = [];
        $this->texts = 0;
        $this->nulls = 0;
        $this->sameNumber = 0;
        $this->skipped = 0;
        $this->tooLong = 0;

        $legacy = [];
        foreach ($this->legacy->table('dct_analytical_method')->get(['id_method', 'dpm_id', 'dpm_other']) as $row) {
            $legacy[(int) $row->id_method] = [
                'dpm_id' => trim((string) $row->dpm_id),
                'dpm_other' => trim((string) $row->dpm_other),
            ];
        }

        $rows = $this->pg->table(self::TABLE)
            ->whereNotNull(self::COLUMN)
            ->whereNotNull('sample_preparation_method_id')
            ->orderBy('id')
            ->get(['id', 'sample_preparation_method_id', self::COLUMN]);

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $old = (string) $row->{self::COLUMN};
            $number = (string) $row->sample_preparation_method_id;

            if (trim($old) !== $number || ! isset($legacy[$id]) || $legacy[$id]['dpm_id'] !== $number) {
                $this->skipped++;

                continue;
            }

            $text = $legacy[$id]['dpm_other'];

            if ($text === '' || $text === '0') {
                $this->changes[$id] = ['old' => $old, 'new' => null];
                $this->nulls++;
            } elseif ($text === $number) {
                $this->sameNumber++;
            } else {
                $this->changes[$id] = ['old' => $old, 'new' => $text];
                $this->texts++;
                if (mb_strlen($text) > self::MAX_LENGTH) {
                    $this->tooLong++;
                }
            }
        }

        $this->say('To set to the legacy text: '.$this->texts);
        $this->say('To set to NULL (legacy blank or 0): '.$this->nulls);
        $this->say('Legacy text is the same number, left alone: '.$this->sameNumber);
        $this->say('Other rows with a value, left alone: '.$this->skipped);
        $this->say('Legacy texts longer than '.self::MAX_LENGTH.': '.$this->tooLong);
        $this->say('Example ids: '.implode(', ', array_slice(array_keys($this->changes), 0, 5)));

        return count($this->changes);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        if ($this->texts !== self::EXPECTED_TEXTS || $this->nulls !== self::EXPECTED_NULLS) {
            throw new RuntimeException('Planned counts differ from the verified ones, nothing written: texts '.$this->texts.' (expected '.self::EXPECTED_TEXTS.'), NULLs '.$this->nulls.' (expected '.self::EXPECTED_NULLS.').');
        }

        if ($this->tooLong > 0) {
            throw new RuntimeException($this->tooLong.' legacy text(s) are longer than '.self::MAX_LENGTH.' characters, nothing written.');
        }

        $written = 0;

        $this->pg->transaction(function () use ($audit, &$written): void {
            foreach (array_chunk($this->changes, self::CHUNK, true) as $chunk) {
                $startedAt = hrtime(true);

                $values = [];
                $bindings = [];
                foreach ($chunk as $id => $change) {
                    $values[] = '(?::bigint, ?::varchar, ?::varchar)';
                    array_push($bindings, $id, $change['new'], $change['old']);
                }

                $returned = $this->pg->select(
                    'UPDATE '.self::TABLE.' AS t SET '.self::COLUMN.' = v.new_value FROM (VALUES '.implode(', ', $values).') AS v(id, new_value, old_value) WHERE t.id = v.id AND t.'.self::COLUMN.' IS NOT DISTINCT FROM v.old_value RETURNING t.id',
                    $bindings,
                );
                $updated = array_map(static fn (object $row): int => (int) $row->id, $returned);

                if (count($updated) !== count($chunk)) {
                    throw new RuntimeException('Updated '.count($updated).' of '.count($chunk).' rows in a chunk, everything rolled back.');
                }

                $before = array_map(
                    static fn (int $id): array => ['id' => $id, self::COLUMN => $chunk[$id]['old']],
                    $updated,
                );
                $audit->logFill(self::STEP, self::TABLE, [self::COLUMN], $updated, $before, (int) ((hrtime(true) - $startedAt) / 1_000_000));
                $written += count($updated);
            }
        });

        $this->say('Rows changed: '.$written.' of '.count($this->changes).' planned.');
    }

    protected function notes(): string
    {
        return 'empodat_analytical_methods.sample_preparation_method_other from legacy dct_analytical_method.dpm_other (first import wrote dpm_id)';
    }
}
