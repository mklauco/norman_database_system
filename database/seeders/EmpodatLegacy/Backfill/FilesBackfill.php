<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;
use Throwable;

/**
 * `files` (from legacy `dct_list`). Inserts the list_ids PG lacks, with the
 * same column mapping as ImportFilesStep, but WITHOUT the `> sinceId` and
 * non-NULL range filters: every legacy list_id missing in PG is copied. With an
 * id window, only lists whose analysis range overlaps the window are in scope.
 * Rows that already exist are never updated; metadata that differs from legacy
 * is only reported. A PG files.id taken by another database entity (files is
 * shared with other modules) is reported as a conflict and never used as an
 * EMPODAT file_id.
 */
final class FilesBackfill
{
    private const STEP = 'Backfill files (dct_list -> files)';

    /** Columns written to files. */
    public const COLUMNS = [
        'id', 'database_entity_id', 'name', 'original_name', 'description', 'doi', 'note',
        'project_id', 'list_type', 'matrice_dct', 'main_id_from', 'main_id_to', 'analysis_number',
        'source_id_from', 'source_id_to', 'source_number', 'method_id_from', 'method_id_to',
        'method_number', 'is_deleted', 'is_protected', 'created_at', 'updated_at',
    ];

    private const COMPARED = [
        'main_id_from' => 'list_analysis_from',
        'main_id_to' => 'list_analysis_to',
        'analysis_number' => 'list_analysis_number',
        'source_id_from' => 'list_source_from',
        'source_id_to' => 'list_source_to',
        'method_id_from' => 'list_method_from',
        'method_id_to' => 'list_method_to',
    ];

    public function __construct(private readonly BackfillContext $ctx) {}

    /**
     * @return array{
     *     toInsert: list<int>,
     *     toInsertTitles: array<int, string>,
     *     present: int,
     *     conflicts: list<array{id: int, entity: ?int}>,
     *     mismatches: list<array{id: int, column: string, pg: mixed, legacy: mixed}>,
     *     legacyRows: int
     * }
     */
    public function plan(): array
    {
        $legacy = $this->legacyRows();
        $entityId = $this->empodatEntityId();
        $pgRows = $this->ctx->pg->table('files')
            ->whereIn('id', array_map(static fn (object $r): int => (int) $r->list_id, $legacy))
            ->get(['id', 'database_entity_id', ...array_keys(self::COMPARED)])
            ->keyBy('id');

        $toInsert = [];
        $toInsertTitles = [];
        $conflicts = [];
        $mismatches = [];
        $present = 0;

        foreach ($legacy as $row) {
            $id = (int) $row->list_id;
            $pg = $pgRows->get($id);
            if ($pg === null) {
                $toInsert[] = $id;
                $toInsertTitles[$id] = (string) $row->list_title;

                continue;
            }
            $present++;
            if ((int) $pg->database_entity_id !== $entityId) {
                $conflicts[] = ['id' => $id, 'entity' => $pg->database_entity_id !== null ? (int) $pg->database_entity_id : null];

                continue;
            }
            foreach (self::COMPARED as $pgColumn => $legacyColumn) {
                $pgValue = $pg->{$pgColumn};
                $legacyValue = $row->{$legacyColumn};
                if (($pgValue === null ? null : (int) $pgValue) !== ($legacyValue === null ? null : (int) $legacyValue)) {
                    $mismatches[] = ['id' => $id, 'column' => $pgColumn, 'pg' => $pgValue, 'legacy' => $legacyValue];
                }
            }
        }

        return [
            'toInsert' => $toInsert,
            'toInsertTitles' => $toInsertTitles,
            'present' => $present,
            'conflicts' => $conflicts,
            'mismatches' => $mismatches,
            'legacyRows' => count($legacy),
        ];
    }

    /**
     * @return list<int> inserted ids
     */
    public function execute(): array
    {
        $audit = $this->ctx->audit ?? throw new RuntimeException('FilesBackfill::execute() without an audit run.');
        $started = microtime(true);

        try {
            $plan = $this->plan();
            $missing = array_flip($plan['toInsert']);
            $entityId = $this->empodatEntityId();
            $now = now()->toDateTimeString();

            $rows = [];
            foreach ($this->legacyRows() as $row) {
                if (isset($missing[(int) $row->list_id])) {
                    $rows[] = $this->mapRow($row, $entityId, $now);
                }
            }

            return $this->ctx->pg->transaction(function () use ($rows, $audit, $started): array {
                $ids = BoundInsert::ignoreConflicts($this->ctx->pg, 'files', $rows);
                $audit->chunk(self::STEP, 'files', $ids, (int) ((microtime(true) - $started) * 1000));

                return $ids;
            });
        } catch (Throwable $e) {
            $audit->failedStep(self::STEP, 'files', $e);
            throw $e;
        }
    }

    /**
     * files.id values a new empodat_main row may point at: PG files of the
     * EMPODAT entity plus the ids this run inserts (or would insert).
     *
     * @param  list<int>  $extra
     * @return list<int>
     */
    public function availableFileIds(array $extra): array
    {
        $ids = array_map('intval', $this->ctx->pg->table('files')
            ->where('database_entity_id', $this->empodatEntityId())
            ->pluck('id')
            ->all());

        return array_values(array_unique([...$ids, ...$extra]));
    }

    /**
     * Legacy dct_list rows in scope: all of them, or with an id window only
     * the lists whose analysis range overlaps it.
     *
     * @return list<object>
     */
    private function legacyRows(): array
    {
        $query = $this->ctx->legacy->table('dct_list')->orderBy('list_id');
        $options = $this->ctx->options;
        if ($options->hasWindow()) {
            $query->whereNotNull('list_analysis_from')->whereNotNull('list_analysis_to');
            if ($options->idTo !== null) {
                $query->where('list_analysis_from', '<=', $options->idTo);
            }
            if ($options->idFrom !== null) {
                $query->where('list_analysis_to', '>=', $options->idFrom);
            }
        }

        return $query->get()->all();
    }

    private function empodatEntityId(): int
    {
        $id = $this->ctx->pg->table('database_entities')->where('code', 'empodat')->value('id');
        if ($id === null) {
            throw new RuntimeException("database_entities has no row with code 'empodat'.");
        }

        return (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(object $r, int $entityId, string $now): array
    {
        return [
            'id' => (int) $r->list_id,
            'database_entity_id' => $entityId,
            'name' => $r->list_title,
            'original_name' => $r->list_file,
            'description' => $r->list_description !== '' ? $r->list_description : null,
            'doi' => $r->list_doi !== '' ? $r->list_doi : null,
            'note' => $r->list_note !== '' ? $r->list_note : null,
            'project_id' => $r->list_project_id !== null ? (int) $r->list_project_id : null,
            'list_type' => $r->list_type !== '' ? $r->list_type : null,
            'matrice_dct' => $r->matrice_dct !== null ? (int) $r->matrice_dct : null,
            'main_id_from' => $r->list_analysis_from !== null ? (int) $r->list_analysis_from : null,
            'main_id_to' => $r->list_analysis_to !== null ? (int) $r->list_analysis_to : null,
            'analysis_number' => $r->list_analysis_number !== null ? (int) $r->list_analysis_number : null,
            'source_id_from' => $r->list_source_from !== null ? (int) $r->list_source_from : null,
            'source_id_to' => $r->list_source_to !== null ? (int) $r->list_source_to : null,
            'source_number' => $r->list_source_number !== null ? (int) $r->list_source_number : null,
            'method_id_from' => $r->list_method_from !== null ? (int) $r->list_method_from : null,
            'method_id_to' => $r->list_method_to !== null ? (int) $r->list_method_to : null,
            'method_number' => $r->list_method_number !== null ? (int) $r->list_method_number : null,
            'is_deleted' => (bool) ((int) $r->list_deleted),
            'is_protected' => $r->list_protected !== null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
