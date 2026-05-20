<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 1a — import `files` rows for the delta.
 *
 * Source : legacy `dct_list` WHERE list_analysis_to > $sinceId
 *          (only files whose range extends past the cutoff are relevant
 *          to the delta).
 * Target : PG `files` (id = legacy list_id, same id space — no remapping).
 *
 * Column mapping (verified against v1-imported rows 596-609 in PG `files`):
 *   list_id              -> id
 *   list_title           -> name
 *   list_file            -> original_name
 *   list_analysis_from   -> main_id_from
 *   list_analysis_to     -> main_id_to
 *   list_analysis_number -> analysis_number
 *   list_source_from/to/number  -> source_id_from / source_id_to / source_number
 *   list_method_from/to/number  -> method_id_from / method_id_to / method_number
 *   list_type            -> list_type
 *   list_note            -> note
 *   list_description     -> description
 *   list_doi             -> doi
 *   list_project_id      -> project_id
 *   list_deleted (tinyint)             -> is_deleted (bool)
 *   list_protected (varchar tag/NULL)  -> is_protected (bool: tag-set => true)
 *   matrice_dct          -> matrice_dct
 *   (resolved at runtime) -> database_entity_id = id of database_entities WHERE code='empodat'
 *
 * Dropped (no PG home): list_name, list_common, list_hash, list_organisation,
 * list_email, list_author, list_data_accessibility, list_private_note, list_date.
 * PG-only cols (file_path, file_size, mime_type, template_id, processing_notes,
 * uploaded_by, uploaded_at, number_of_records) left NULL — expert users
 * populate them via the frontend later.
 *
 * Idempotent — uses INSERT ... ON CONFLICT (id) DO NOTHING.
 */
class ImportFilesStep extends Step
{
    private const TABLE = 'files';

    public function name(): string
    {
        return 'Import files (dct_list -> files)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $databaseEntityId = $this->pg()
                ->table('database_entities')
                ->where('code', 'empodat')
                ->value('id');

            if ($databaseEntityId === null) {
                throw new \RuntimeException(
                    "Cannot resolve database_entities.id WHERE code='empodat' — empodat module not registered in PG."
                );
            }

            $rows = $this->legacy()
                ->table('dct_list')
                ->whereNotNull('list_analysis_from')
                ->whereNotNull('list_analysis_to')
                ->where('list_analysis_to', '>', $this->sinceId)
                ->orderBy('list_id')
                ->get();

            if ($rows->isEmpty()) {
                $this->note('No legacy file rows extend past sinceId — nothing to import.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $insertable = $rows->map(fn ($r) => [
                'id' => (int) $r->list_id,
                'database_entity_id' => (int) $databaseEntityId,
                'name' => $r->list_title,
                'original_name' => $r->list_file,
                'description' => $r->list_description !== '' ? $r->list_description : null,
                'doi' => $r->list_doi !== '' ? $r->list_doi : null,
                'note' => $r->list_note !== '' ? $r->list_note : null,
                'project_id' => $r->list_project_id !== null ? (int) $r->list_project_id : null,
                'list_type' => $r->list_type !== '' ? $r->list_type : null,
                'matrice_dct' => $r->matrice_dct !== null ? (int) $r->matrice_dct : null,
                'main_id_from' => (int) $r->list_analysis_from,
                'main_id_to' => (int) $r->list_analysis_to,
                'analysis_number' => $r->list_analysis_number !== null ? (int) $r->list_analysis_number : null,
                'source_id_from' => $r->list_source_from !== null ? (int) $r->list_source_from : null,
                'source_id_to' => $r->list_source_to !== null ? (int) $r->list_source_to : null,
                'source_number' => $r->list_source_number !== null ? (int) $r->list_source_number : null,
                'method_id_from' => $r->list_method_from !== null ? (int) $r->list_method_from : null,
                'method_id_to' => $r->list_method_to !== null ? (int) $r->list_method_to : null,
                'method_number' => $r->list_method_number !== null ? (int) $r->list_method_number : null,
                'is_deleted' => (bool) ((int) $r->list_deleted),
                'is_protected' => $r->list_protected !== null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            $insertedIds = $this->insertOnConflictDoNothing(self::TABLE, $insertable);

            $this->note(sprintf(
                'Candidates=%d  inserted=%d  skipped(already in PG)=%d',
                $rows->count(),
                count($insertedIds),
                $rows->count() - count($insertedIds),
            ));
            $this->logSmallStep(self::TABLE, $insertedIds, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }
}
