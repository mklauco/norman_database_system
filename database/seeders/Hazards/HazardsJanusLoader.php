<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

/**
 * Loads storage/app/hazards/hazards_janus.xlsx (sheet FINAL, else the first sheet) into
 * hazards_janus. susdat_substance_id is resolved from NORMAN_ID ('NS' + susdat_substances.code);
 * any id column in the file is ignored.
 *
 * Helper class of HazardsDataSeeder; not a seeder, nothing to run. Run only
 * Database\Seeders\Hazards\HazardsDataSeeder.
 */
final class HazardsJanusLoader extends HazardsSourceLoader
{
    protected function table(): string
    {
        return 'hazards_janus';
    }

    protected function label(): string
    {
        return 'Janus';
    }

    protected function path(): string
    {
        return storage_path('app/hazards/hazards_janus.xlsx');
    }

    protected function keyColumn(): string
    {
        return 'norman_id';
    }

    protected function resolve(string $key, HazardsSubstanceMap $map): array
    {
        return $map->resolveNormanId($key);
    }

    protected function openReader(string $path): \Spatie\SimpleExcel\SimpleExcelReader
    {
        $reader = parent::openReader($path);

        return $reader->hasSheet('FINAL') ? $reader->fromSheetName('FINAL') : $reader;
    }

    protected function columns(): array
    {
        return [
            'norman_id' => ['string', ['NORMAN_ID', 'normanId']],
            'dtxid' => ['string', ['DTXSID']],
            'smiles' => ['string', ['SMILES']],
            'p_assessment_class' => ['string', ['P assessment [class]', 'p_assessment_[class]']],
            'p_assessment_index' => ['float', ['P assessment [index]', 'p_assessment_[index]']],
            'p_reliability' => ['float', ['P reliability']],
            'p_score' => ['float', ['P score']],
            'b_assessment_log_units' => ['float', ['B assessment [log units]', 'b_assessment_[log_units]']],
            'b_reliability' => ['float', ['B reliability']],
            'b_score' => ['float', ['B score']],
            't_assessment_mg_l' => ['float', ['T assessment [mg/l]', 't_assessment_[mg/l]']],
            't_reliability' => ['float', ['T reliability']],
            't_score' => ['float', ['T score']],
            'c_assessment' => ['string', ['C assessment']],
            'c_reliability' => ['float', ['C reliability']],
            'c_score' => ['float', ['C score']],
            'm_assessment' => ['string', ['M assessment']],
            'm_reliability' => ['float', ['M reliability']],
            'm_score' => ['float', ['M score']],
            'r_assessment' => ['string', ['R assessment']],
            'r_reliability' => ['float', ['R reliability']],
            'r_score' => ['float', ['R score']],
            'ed_assessment_class' => ['string', ['ED assessment [class]', 'ed_assessment_[class]']],
            'ed_assessment_index' => ['float', ['ED assessment [index]', 'ed_assessment_[index]']],
            'ed_reliability' => ['float', ['ED reliability']],
            'ed_score' => ['float', ['ED score']],
            'score_vpvb' => ['float', ['SCORE(vPvB)', 'score(v_pv_b)']],
            'score_svhc' => ['float', ['SCORE(SVHC)', 'score(svhc)']],
            'score_pbt' => ['float', ['SCORE(PBT)', 'score(pbt)']],
            'remarks' => ['string', ['Remarks']],
        ];
    }
}
