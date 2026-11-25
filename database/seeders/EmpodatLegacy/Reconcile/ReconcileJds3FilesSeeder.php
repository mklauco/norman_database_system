<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * Moves the records of the JDS3 survey (empodat_main.id 12061597 to 12104240,
 * 41 632 records) to their own files. The six JDS3 files share one id range, so
 * all of the records carry file_id 181 in empodat_main. Legacy dct_analysis.list_id
 * holds the right file. Only `file_id` is written, and only where it is still 181
 * and legacy says 176, 178, 179 or 180. The 160 records of file 181 stay.
 *
 * The run stops before anything is written when the legacy counts per file differ
 * from the verified ones, when any record in the range is on one side only or
 * sits in another file than expected, or when one of the files 176, 178, 179 and
 * 180 does not exist. Rerun after a successful run plans 0 rows.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileJds3FilesSeeder' --force
 */
final class ReconcileJds3FilesSeeder extends ReconcileSeeder
{
    private const STEP = 'JDS3 file numbers';

    private const TABLE = 'empodat_main';

    private const ID_FROM = 12061597;

    private const ID_TO = 12104240;

    private const STAY_FILE = 181;

    private const WRITE_CHUNK = 5000;

    /**
     * Verified legacy count per list_id in the id range.
     *
     * @var array<int, int>
     */
    private const LEGACY_COUNTS = [
        176 => 38824,
        178 => 1306,
        179 => 1294,
        180 => 48,
        181 => 160,
    ];

    /** @var array<int, list<int>> target file id => empodat_main ids to move */
    private array $work = [];

    /** @var array<int, int> list_id => records found in legacy */
    private array $legacyCounts = [];

    private int $problems = 0;

    protected function plan(): int
    {
        $this->work = [];
        $this->legacyCounts = [];
        $this->problems = 0;
        $done = 0;
        $problemExamples = [];

        $fileIds = array_keys(self::LEGACY_COUNTS);
        $legacy = [];
        $rows = $this->legacy->select(
            'SELECT id, list_id FROM dct_analysis WHERE id BETWEEN ? AND ? AND list_id IN ('.implode(',', $fileIds).')',
            [self::ID_FROM, self::ID_TO],
        );
        foreach ($rows as $row) {
            $legacy[(int) $row->id] = (int) $row->list_id;
            $this->legacyCounts[(int) $row->list_id] = ($this->legacyCounts[(int) $row->list_id] ?? 0) + 1;
        }
        ksort($this->legacyCounts);

        $nds = [];
        $rows = $this->pg->select(
            'SELECT id, file_id FROM '.self::TABLE.' WHERE id BETWEEN ? AND ? AND file_id IN ('.implode(',', $fileIds).')',
            [self::ID_FROM, self::ID_TO],
        );
        foreach ($rows as $row) {
            $nds[(int) $row->id] = (int) $row->file_id;
        }

        $ids = array_keys($legacy + $nds);
        sort($ids);

        foreach ($ids as $id) {
            $legacyFile = $legacy[$id] ?? null;
            $ndsFile = $nds[$id] ?? null;

            if ($legacyFile !== null && $ndsFile === $legacyFile) {
                $done++;
            } elseif ($ndsFile === self::STAY_FILE && $legacyFile !== null && $legacyFile !== self::STAY_FILE) {
                $this->work[$legacyFile][] = $id;
            } else {
                $this->problems++;
                if (count($problemExamples) < 5) {
                    $problemExamples[] = $id.' (legacy '.($legacyFile ?? 'none').', NDS2.0 '.($ndsFile ?? 'none').')';
                }
            }
        }
        ksort($this->work);

        $this->say('Legacy records in range per file: '.$this->describe($this->legacyCounts));
        $this->say('Records to move per target file: '.$this->describe(array_map('count', $this->work)));
        $this->say('Already done: '.$done);
        $this->say('Problems (one side only or other file): '.$this->problems.($problemExamples === [] ? '' : ' (e.g. '.implode(', ', $problemExamples).')'));

        return array_sum(array_map('count', $this->work));
    }

    protected function apply(ReconcileAudit $audit): void
    {
        if ($this->legacyCounts !== self::LEGACY_COUNTS) {
            throw new RuntimeException('Legacy counts per file differ from the verified ones, nothing written: found '.$this->describe($this->legacyCounts).', expected '.$this->describe(self::LEGACY_COUNTS).'.');
        }

        if ($this->problems > 0) {
            throw new RuntimeException('Found '.$this->problems.' record(s) on one side only or in another file, nothing written.');
        }

        $targets = array_keys($this->work);
        $existing = array_map(
            static fn (object $row): int => (int) $row->id,
            $this->pg->table('files')->whereIn('id', $targets)->get(['id'])->all(),
        );
        $missing = array_diff($targets, $existing);
        if ($missing !== []) {
            throw new RuntimeException('Files missing in the files table, nothing written: '.implode(', ', $missing));
        }

        $written = 0;
        $planned = array_sum(array_map('count', $this->work));

        $this->pg->transaction(function () use ($audit, &$written): void {
            foreach ($this->work as $fileId => $ids) {
                foreach (array_chunk($ids, self::WRITE_CHUNK) as $chunk) {
                    $startedAt = hrtime(true);

                    $returned = $this->pg->select(
                        'UPDATE '.self::TABLE.' SET file_id = ? WHERE id IN ('.implode(',', array_fill(0, count($chunk), '?')).') AND file_id = ? RETURNING id',
                        array_merge([$fileId], $chunk, [self::STAY_FILE]),
                    );
                    $updated = array_map(static fn (object $row): int => (int) $row->id, $returned);

                    if (count($updated) !== count($chunk)) {
                        throw new RuntimeException('File '.$fileId.': updated '.count($updated).' of '.count($chunk).' records in a chunk, everything rolled back.');
                    }

                    $before = array_map(
                        static fn (int $id): array => ['id' => $id, 'file_id' => self::STAY_FILE],
                        $updated,
                    );
                    $audit->logFill(self::STEP, self::TABLE, ['file_id'], $updated, $before, (int) ((hrtime(true) - $startedAt) / 1_000_000));
                    $written += count($updated);
                }
                $this->say('File '.$fileId.' done, written so far '.$written);
            }
        });

        $this->say('Rows changed: '.$written.' of '.$planned.' planned.');
    }

    protected function notes(): string
    {
        return 'empodat_main.file_id of the JDS3 records from legacy dct_analysis.list_id (181 to 176, 178, 179, 180)';
    }

    /**
     * @param  array<int, int>  $counts
     */
    private function describe(array $counts): string
    {
        $parts = [];
        foreach ($counts as $fileId => $count) {
            $parts[] = $fileId.' => '.$count;
        }

        return $parts === [] ? 'none' : implode(', ', $parts);
    }
}
