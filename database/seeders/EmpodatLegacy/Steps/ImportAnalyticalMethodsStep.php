<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 1c — import `empodat_analytical_methods` rows for the delta.
 *
 * Source : legacy `dct_analytical_method` rows referenced by `dct_analysis` delta.
 * Target : PG `empodat_analytical_methods` (id = legacy id_method).
 *
 * Column mapping (verified against PG schema):
 *   id_method                    -> id
 *   am_lod                       -> lod                              (double, direct)
 *   am_loq                       -> loq                              (double, direct)
 *   am_uncertainty_loq           -> uncertainty_loq                  (varchar -> numeric; safe-cast, else NULL — 100% NULL in delta)
 *   dcf_id                       -> coverage_factor_id               (FK)
 *   dpm_id                       -> sample_preparation_method_id     (FK dropped in Phase 0d; raw legacy value, including 19 which PG list doesn't yet have)
 *   dpm_other                    -> sample_preparation_method_other
 *   dam_id                       -> analytical_method_id             (FK dropped in Phase 0d; raw, incl. 29)
 *   dam_other                    -> analytical_method_other
 *   dsm_id                       -> standardised_method_id           (FK dropped in Phase 0d; raw, incl. 8)
 *   dsm_other                    -> standardised_method_other
 *   am_number                    -> standardised_method_number
 *   dp_id                        -> validated_method_id              (FK; delta values in PG range)
 *   am_corrected_recovery        -> corrected_recovery_id            (FK list_yes_no_questions; delta 1-4)
 *   am_field_blank               -> field_blank_id                   (FK; delta 1-3)
 *   am_iso                       -> iso_id                           (FK; delta 1-3)
 *   am_given_analyte             -> given_analyte_id + legacy_given_analyte_id (split: 1-4 -> FK col; 0 -> legacy col, FK NULL)
 *   am_laboratory_participate    -> laboratory_participate_id + legacy_laboratory_participate_id (same split)
 *   dsp_id                       -> summary_performance_id           (FK; delta 1,3,4)
 *   am_control_charts            -> control_charts_id                (FK; delta 1-4)
 *   am_internal_standards        -> internal_standards_id            (FK; delta 1,3)
 *   am_authority                 -> authority_id                     (FK; delta 1-4)
 *   rating                       -> rating
 *   am_remark                    -> remark
 *   am_foa                       -> foa                              (varchar -> double; safe-cast, else NULL — 100% NULL in delta)
 *
 * PG-only cols left NULL: sampling_method_id, sampling_collection_device_id
 * (no source in dct_analytical_method; populated from dct_analysis if needed).
 *
 * Idempotent: ON CONFLICT (id) DO NOTHING. Re-running adds zero new rows.
 */
class ImportAnalyticalMethodsStep extends Step
{
    private const TABLE = 'empodat_analytical_methods';

    public function name(): string
    {
        return 'Import analytical methods (dct_analytical_method -> empodat_analytical_methods)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $referencedIds = $this->legacy()
                ->table('dct_analysis')
                ->where('id', '>=', $this->sinceId)
                ->whereNotNull('id_method')
                ->distinct()
                ->pluck('id_method')
                ->map(fn ($v) => (int) $v)
                ->all();

            if ($referencedIds === []) {
                $this->note('No analytical methods referenced in delta.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $legacyRows = $this->legacy()
                ->table('dct_analytical_method')
                ->whereIn('id_method', $referencedIds)
                ->orderBy('id_method')
                ->get();

            $resolvedIds = $legacyRows->pluck('id_method')->map(fn ($v) => (int) $v)->all();
            $orphans = array_values(array_diff($referencedIds, $resolvedIds));
            if ($orphans !== []) {
                $this->note(sprintf(
                    'Orphan id_method values (referenced but missing in dct_analytical_method): %d — main rows will get method_id = NULL in Phase 5',
                    count($orphans),
                ));
            }

            $alreadyInPg = $this->pg()
                ->table(self::TABLE)
                ->whereIn('id', $resolvedIds)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $toInsert = $legacyRows->reject(fn ($r) => in_array((int) $r->id_method, $alreadyInPg, true));

            if ($toInsert->isEmpty()) {
                $this->note('All '.count($resolvedIds).' referenced methods already in PG.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $insertable = $toInsert->map(function ($r) {
                [$givenAnalyteId, $legacyGivenAnalyteId] = $this->splitYesNoZero($r->am_given_analyte);
                [$labParticipateId, $legacyLabParticipateId] = $this->splitYesNoZero($r->am_laboratory_participate);

                return [
                    'id' => (int) $r->id_method,
                    'lod' => $r->am_lod !== null ? (float) $r->am_lod : null,
                    'loq' => $r->am_loq !== null ? (float) $r->am_loq : null,
                    'uncertainty_loq' => $this->castNumeric($r->am_uncertainty_loq),
                    'coverage_factor_id' => $this->intOrNull($r->dcf_id),
                    'sample_preparation_method_id' => $this->intOrNull($r->dpm_id),
                    'sample_preparation_method_other' => $this->blankToNull($r->dpm_other),
                    'analytical_method_id' => $this->intOrNull($r->dam_id),
                    'analytical_method_other' => $this->blankToNull($r->dam_other),
                    'standardised_method_id' => $this->intOrNull($r->dsm_id),
                    'standardised_method_other' => $this->blankToNull($r->dsm_other),
                    'standardised_method_number' => $this->blankToNull($r->am_number),
                    'validated_method_id' => $this->intOrNull($r->dp_id),
                    'corrected_recovery_id' => $this->intOrNull($r->am_corrected_recovery),
                    'field_blank_id' => $this->intOrNull($r->am_field_blank),
                    'iso_id' => $this->intOrNull($r->am_iso),
                    'given_analyte_id' => $givenAnalyteId,
                    'legacy_given_analyte_id' => $legacyGivenAnalyteId,
                    'laboratory_participate_id' => $labParticipateId,
                    'legacy_laboratory_participate_id' => $legacyLabParticipateId,
                    'summary_performance_id' => $this->intOrNull($r->dsp_id),
                    'control_charts_id' => $this->intOrNull($r->am_control_charts),
                    'internal_standards_id' => $this->intOrNull($r->am_internal_standards),
                    'authority_id' => $this->intOrNull($r->am_authority),
                    'rating' => $this->intOrNull($r->rating),
                    'remark' => $this->blankToNull($r->am_remark),
                    'foa' => $this->castDouble($r->am_foa),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            $insertedIds = $this->insertOnConflictDoNothing(self::TABLE, $insertable);

            $this->note(sprintf(
                'Candidates=%d  inserted=%d  skipped(already in PG)=%d',
                $toInsert->count(),
                count($insertedIds),
                $toInsert->count() - count($insertedIds),
            ));

            $this->logSmallStep(self::TABLE, $insertedIds, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }

    /**
     * Split a legacy yes/no tinyint into (fk_id, legacy_id).
     * Legacy 1-4 -> FK col (matches PG list_yes_no_questions ids), legacy_id NULL.
     * Legacy 0 -> FK col NULL, legacy_id = 0 (preserves the "unanswered" sentinel
     * per Phase 0d policy — PG has no list_yes_no_questions.id=0).
     * Legacy NULL -> both NULL.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function splitYesNoZero(mixed $value): array
    {
        if ($value === null) {
            return [null, null];
        }
        $int = (int) $value;
        if ($int === 0) {
            return [null, 0];
        }

        return [$int, null];
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function castNumeric(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function castDouble(?string $value): ?float
    {
        return $this->castNumeric($value);
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
