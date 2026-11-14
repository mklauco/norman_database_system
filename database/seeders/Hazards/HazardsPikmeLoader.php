<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

/**
 * Loads storage/app/hazards/hazards_pikme.csv into hazards_pikme. susdat_substance_id is
 * resolved from dtxid (susdat_substances.dtxid); any id column in the file is ignored.
 *
 * Helper class of HazardsDataSeeder; not a seeder, nothing to run. Run only
 * Database\Seeders\Hazards\HazardsDataSeeder.
 */
final class HazardsPikmeLoader extends HazardsSourceLoader
{
    protected function table(): string
    {
        return 'hazards_pikme';
    }

    protected function label(): string
    {
        return 'PIKME';
    }

    protected function path(): string
    {
        return storage_path('app/hazards/hazards_pikme.csv');
    }

    protected function keyColumn(): string
    {
        return 'dtxid';
    }

    protected function resolve(string $key, HazardsSubstanceMap $map): array
    {
        return $map->resolveDtxid($key);
    }

    protected function openReader(string $path): \Spatie\SimpleExcel\SimpleExcelReader
    {
        return parent::openReader($path)->useDelimiter(',');
    }

    protected function columns(): array
    {
        return [
            'dtxid' => ['string', ['dtxsid']],
            'logkoc_pred_opera' => ['float', []],
            'koc_predrange_opera' => ['string', []],
            'conf_index_koc_opera' => ['float', []],
            'ad_koc_opera' => ['string', []],
            'ad_index_koc_opera' => ['float', []],
            'logbcf_pred_opera' => ['float', []],
            'bcf_predrange_opera' => ['string', []],
            'conf_index_bcf_opera' => ['float', []],
            'ad_bcf_opera' => ['string', []],
            'ad_index_bcf_opera' => ['float', []],
            'biodeg_loghalflife_pred_opera' => ['float', []],
            'biodeg_predrange_opera' => ['string', []],
            'conf_index_biodeg_opera' => ['float', []],
            'ad_biodeg_opera' => ['string', []],
            'ad_index_biodeg_opera' => ['float', []],
            'loghl_pred_opera' => ['float', []],
            'hl_predrange_opera' => ['string', []],
            'conf_index_hl_opera' => ['float', []],
            'ad_hl_opera' => ['string', []],
        ];
    }
}
