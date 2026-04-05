<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Factsheet\FactsheetEntity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FactsheetEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        FactsheetEntity::truncate();
        $entities = [
            [
                'name' => 'Chemical identity',
                'sort_order' => 1,
                'data' => json_encode(['method_of_presentation' => 'database_table', 'model' => 'App\Models\Susdat\Substance', 'fields' => ['prefixed_code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'molecular_formula', 'mass_iso', 'dtxid', 'pubchem_cid']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Major uses',
                'sort_order' => 2,
                'data' => json_encode(['method_of_presentation' => 'database_table', 'model' => 'App\Models\Susdat\UsepaCategories', 'fields' => ['category_name']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Properties',
                'sort_order' => 3,
                'data' => json_encode(['method_of_presentation' => 'database_table', 'model' => 'App\Models\Susdat\Usepa', 'fields' => ['usepa_formula', 'usepa_wikipedia', 'usepa_wikipedia_url', 'usepa_Log_Kow_experimental', 'usepa_Log_Kow_predicted', 'usepa_solubility_experimental', 'usepa_solubility_predicted', 'usepa_Koc_min_experimental', 'usepa_Koc_max_experimental', 'usepa_Koc_min_predicted', 'usepa_Koc_max_predicted', 'usepa_Life_experimental', 'usepa_Life_predicted', 'usepa_BCF_experimental', 'usepa_BCF_predicted']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Environmental occurrence (all data)',
                'sort_order' => 4,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Environmental occurrence (detailed information)',
                'sort_order' => 5,
                'data' => json_encode(['method_of_presentation' => 'controller_method', 'method' => 'getEnvironmentalOccurrenceDataDetailed']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Environmental occurrence (per matrix)',
                'sort_order' => 6,
                'data' => json_encode(['method_of_presentation' => 'controller_method', 'method' => 'getEnvironmentalOccurrenceMatrixData']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '(Eco)toxicity',
                'sort_order' => 7,
                'data' => json_encode(['method_of_presentation' => 'controller_method', 'method' => 'getEcotoxicityData']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PBT/vPvB & PMT/vPvM (NORMAN)',
                'sort_order' => 8,
                'data' => json_encode(['method_of_presentation' => 'controller_method', 'method' => 'getHazardsPbtPmtData']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'CMR & ED (NORMAN)',
                'sort_order' => 9,
                'data' => json_encode(['method_of_presentation' => 'banner', 'color' => 'green', 'text' => 'This module is currently under development.']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Potential risk of exceedance of lowest PNEC',
                'sort_order' => 10,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Conclusions and recommendations',
                'sort_order' => 11,
                'data' => json_encode(['method_of_presentation' => 'banner', 'color' => 'green', 'text' => 'This module is currently under development.']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bibliography, sources and supportive information',
                'sort_order' => 12,
                'data' => json_encode(['method_of_presentation' => 'text', 'text' => 'Dulio V. and Von der Ohe P. (2013) NORMAN Prioritisation framework for emerging substances. NORMAN Association, Verneuil en Halatte, France, 70 pages.']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        FactsheetEntity::insert($entities);
    }
}
// php artisan db:seed --class=FactsheetEntitySeeder
