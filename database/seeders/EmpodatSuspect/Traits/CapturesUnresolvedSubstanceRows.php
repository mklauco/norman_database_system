<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Captures, DURING the import pass, the exact `empodat_suspect_main.id` values produced by
 * source rows whose substance code could not be resolved.
 *
 * WHY IT MUST HAPPEN HERE
 * -----------------------
 * `empodat_suspect_main` does not store NORMAN_ID. The only moment the link between a source
 * code and the rows it produced exists is the insert itself. Afterwards the association is
 * unrecoverable — it can at best be guessed, and only while a file happens to contain a single
 * unresolved code.
 *
 * HOW
 * ---
 * Rows whose code did not resolve are tagged by the Main seeder (see UNRESOLVED_TAG) and
 * inserted in their own per-code sub-batch with `RETURNING id`. Every id coming back from that
 * statement therefore belongs to that one code — no reliance on RETURNING preserving the order
 * of a mixed batch. Resolved rows keep the original plain bulk insert.
 *
 * Unresolved rows are a tiny fraction of a file (~0.001% once the crosswalk is populated), so
 * the extra statements are negligible.
 *
 * The captured ids are written to `empodat_suspect_susdat_code_mappings.notes` as JSON, keyed
 * by file_id, so each file's import updates only its own entry and never clobbers another's.
 */
trait CapturesUnresolvedSubstanceRows
{
    /**
     * Key added to a main row by the Main seeder when its code failed to resolve.
     * Carries the raw NORMAN_ID. Stripped before the row reaches the database.
     */
    public const UNRESOLVED_TAG = '__unresolved_norman_id';

    /**
     * Beyond this many ids for one code in one file, the note stores count + min/max
     * instead of the full list, to keep the notes column sane.
     */
    private const MAX_IDS_IN_NOTE = 20000;

    /**
     * NORMAN_ID => list of inserted empodat_suspect_main.id values.
     *
     * @var array<string, array<int, int>>
     */
    protected array $unresolvedRowIds = [];

    /**
     * Insert a batch of main rows, splitting off tagged (unresolved) rows so their ids
     * can be captured per code.
     *
     * @param  array<int, array<string, mixed>>  $batch
     */
    protected function insertMainBatch(string $table, array $batch): void
    {
        if ($batch === []) {
            return;
        }

        $resolved = [];
        $unresolvedByNormanId = [];

        foreach ($batch as $row) {
            $normanId = $row[self::UNRESOLVED_TAG] ?? null;

            if ($normanId === null) {
                $resolved[] = $row;

                continue;
            }

            unset($row[self::UNRESOLVED_TAG]);
            $unresolvedByNormanId[(string) $normanId][] = $row;
        }

        if ($resolved !== []) {
            DB::table($table)->insert($resolved);
        }

        foreach ($unresolvedByNormanId as $normanId => $rows) {
            foreach (array_chunk($rows, 1000) as $chunk) {
                foreach ($this->insertReturningIds($table, $chunk) as $id) {
                    $this->unresolvedRowIds[$normanId][] = $id;
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int>
     */
    private function insertReturningIds(string $table, array $rows): array
    {
        $columns = array_keys($rows[0]);
        $placeholders = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $valuesSql = implode(', ', array_fill(0, count($rows), $placeholders));
        $columnsSql = implode(', ', $columns);

        $bindings = [];

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $bindings[] = $row[$column];
            }
        }

        $returned = DB::select(
            "INSERT INTO {$table} ({$columnsSql}) VALUES {$valuesSql} RETURNING id",
            $bindings
        );

        if (count($returned) !== count($rows)) {
            throw new \RuntimeException(
                'RETURNING count mismatch capturing unresolved rows: '
                .count($returned).' vs expected '.count($rows)
            );
        }

        return array_map(static fn (object $r): int => (int) $r->id, $returned);
    }

    /**
     * Write the captured ids into the crosswalk, one placeholder row per unresolved code.
     *
     * An existing crosswalk row keeps its `new_code` and `old_legacy_norman_id` untouched —
     * only `notes` is rewritten — so a real mapping can never be clobbered by an import.
     */
    protected function persistUnresolvedRowIds(int $fileId): void
    {
        if ($this->unresolvedRowIds === []) {
            $this->command->info('No unresolved substance codes in this file — nothing to record.');

            return;
        }

        $stamp = now()->toDateTimeString();

        foreach ($this->unresolvedRowIds as $normanId => $ids) {
            $oldCode = preg_replace('/^NS/', '', $normanId);
            $entry = [
                'rows' => count($ids),
                'captured_at' => $stamp,
            ];

            if (count($ids) <= self::MAX_IDS_IN_NOTE) {
                $entry['ids'] = $ids;
            } else {
                $entry['id_min'] = min($ids);
                $entry['id_max'] = max($ids);
                $entry['ids_omitted'] = 'more than '.self::MAX_IDS_IN_NOTE.' ids; re-run this file to recapture';
            }

            $existing = DB::table('empodat_suspect_susdat_code_mappings')
                ->where('old_code', $oldCode)
                ->first();

            $payload = $this->decodeNote($existing->notes ?? null);
            $payload['status'] = 'unmapped';
            $payload['norman_id'] = $normanId;
            $payload['how_to_fix'] = 'Set new_code on this row, resolve it to susdat_substances.id, then '
                .'UPDATE empodat_suspect_main SET substance_id = <that id> WHERE id IN (<the ids listed per file>). '
                .'No re-import needed. Ids are only valid until these files are re-imported.';
            $payload['files'][(string) $fileId] = $entry;

            $notes = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($existing !== null) {
                DB::table('empodat_suspect_susdat_code_mappings')
                    ->where('old_code', $oldCode)
                    ->update(['notes' => $notes, 'updated_at' => now()]);
            } else {
                DB::table('empodat_suspect_susdat_code_mappings')->insert([
                    'old_code' => $oldCode,
                    'old_legacy_norman_id' => $normanId,
                    'new_code' => '',
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->warn(
                "Unresolved code {$normanId}: captured ".count($ids)
                ." empodat_suspect_main id(s) for file_id={$fileId} into the crosswalk."
            );
        }
    }

    /**
     * Existing notes may be legacy free text rather than JSON; in that case it is preserved
     * under `previous_note` so nothing curated by hand is silently lost.
     *
     * @return array<string, mixed>
     */
    private function decodeNote(?string $notes): array
    {
        if ($notes === null || trim($notes) === '') {
            return ['files' => []];
        }

        $decoded = json_decode($notes, true);

        if (is_array($decoded) && isset($decoded['files']) && is_array($decoded['files'])) {
            return $decoded;
        }

        return ['files' => [], 'previous_note' => $notes];
    }
}
