<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use App\Models\Factsheet\FactsheetEntity;
use App\Models\Factsheet\FactsheetStatistic;
use App\Models\Ecotox\LowestPNEC;
use App\Models\Ecotox\LowestPNECMain;
use App\Models\Hazards\SubstanceClassification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactsheetController extends Controller
{
    public function index()
    {
        return view('factsheet.index');
    }

    public function filter(Request $request)
    {
        // Get search parameters from request
        $search = $request->get('search', '');
        $searchType = $request->get('searchType', 'name');
        $substances = $request->get('substances', []);
        
        // Convert to array if it's a single value, but ensure only one substance
        if (!is_array($substances)) {
            $substances = $substances ? [$substances] : [];
        }
        
        // If multiple substances are selected, take only the first one
        if (count($substances) > 1) {
            $substances = [reset($substances)];
        }
        
        return view('factsheet.filter', compact('request', 'search', 'searchType', 'substances'));
    }

    public function search(Request $request)
    {
        // Get selected substance from the request - only allow single substance
        $substanceId = $request->get('substances');
        
        // If substances is an array, take only the first one
        if (is_array($substanceId)) {
            $substanceId = !empty($substanceId) ? $substanceId[0] : null;
        }
        
        // If no substance selected, redirect back to filter
        if (empty($substanceId)) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Please select exactly one substance to view its factsheet.');
        }
        
        // Fetch the single substance
        $substance = Substance::find($substanceId);
        
        if (!$substance) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Selected substance not found.');
        }
        
        // Get factsheet entities for display and process their data
        $factsheetEntities = FactsheetEntity::ordered()->get();
        
        // Process each entity to prepare presentation data
        foreach ($factsheetEntities as $entity) {
            if ($entity->name === 'PBT/vPvB & PMT/vPvM (NORMAN)') {
                $entity->processed_data = $this->getHazardsPbtPmtData($substance);
                continue;
            }

            if (isset($entity->data['method_of_presentation'])) {
                if ($entity->data['method_of_presentation'] === 'database_table') {
                    // CASE 1: Database table presentation
                    $entity->processed_data = $this->processDatabaseTableData($entity, $substance);
                } elseif ($entity->data['method_of_presentation'] === 'text') {
                    // CASE 2: Text presentation
                    $entity->processed_data = $this->processTextData($entity);
                } elseif ($entity->data['method_of_presentation'] === 'banner') {
                    // CASE 3: Banner presentation
                    $entity->processed_data = $this->processBannerData($entity);
                } elseif ($entity->data['method_of_presentation'] === 'controller_method') {
                    // CASE 4: Controller method presentation
                    $entity->processed_data = $this->processControllerMethodData($entity, $substance);
                }
            }
        }
        
        // Check if statistics record exists for this substance (even if meta_data is null)
        $statisticsRecord = FactsheetStatistic::where('substance_id', $substance->id)->first();
        $hasStatistics = $statisticsRecord !== null;
        $statisticsData = $statisticsRecord ? $statisticsRecord->meta_data : null;
        
        // dd($factsheetEntities);
        return view('factsheet.index', compact('substance', 'factsheetEntities', 'hasStatistics', 'statisticsData'));
    }

    /**
     * Process database table data for CASE 1: method_of_presentation = database_table
     * 
     * @param FactsheetEntity $entity
     * @param Substance $substance
     * @return array
     */
    private function processDatabaseTableData($entity, $substance): array
    {
        $processedData = [
            'type' => 'database_table',
            'model' => $entity->data['model'] ?? null,
            'fields' => $entity->data['fields'] ?? [],
            'key_value_data' => []
        ];

        // Get the model class from the entity data
        $modelClass = $entity->data['model'] ?? null;
        $fields = $entity->data['fields'] ?? [];

        if ($modelClass && class_exists($modelClass) && !empty($fields)) {
            try {
                // For substance-related models, we'll query by substance_id or direct access
                if ($modelClass === 'App\Models\Susdat\Substance') {
                    // Direct access to substance data
                    foreach ($fields as $field) {
                        $value = $substance->{$field} ?? 'N/A';
                        $processedData['key_value_data'][$field] = [$value];
                    }
                } else {
                    // For other models, try to find related records
                    $model = new $modelClass();
                    
                    // Check if the model has a substance_id or similar field
                    if (in_array('substance_id', $model->getFillable()) || 
                        method_exists($model, 'substance')) {
                        $records = $model::where('substance_id', $substance->id)->get();
                        
                        if ($records->isNotEmpty()) {
                            foreach ($fields as $field) {
                                $values = $records->pluck($field)->filter()->unique();
                                $processedData['key_value_data'][$field] = $values->isNotEmpty() ? 
                                    $values->toArray() : ['N/A'];
                            }
                        } else {
                            // No records found
                            foreach ($fields as $field) {
                                $processedData['key_value_data'][$field] = ['No data available'];
                            }
                        }
                    } else {
                        // Model doesn't have substance relation, return placeholder
                        foreach ($fields as $field) {
                            $processedData['key_value_data'][$field] = ['Model relation not configured'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing database table data: ' . $e->getMessage());
                foreach ($fields as $field) {
                    $processedData['key_value_data'][$field] = ['Error loading data'];
                }
            }
        }

        return $processedData;
    }

    /**
     * Process text data for CASE 2: method_of_presentation = text
     * 
     * @param FactsheetEntity $entity
     * @return array
     */
    private function processTextData($entity): array
    {
        return [
            'type' => 'text',
            'content' => $entity->data['text'] ?? 'No text content available'
        ];
    }

    /**
     * Process banner data for CASE 3: method_of_presentation = banner
     * 
     * @param FactsheetEntity $entity
     * @return array
     */
    private function processBannerData($entity): array
    {
        return [
            'type' => 'banner',
            'color' => $entity->data['color'] ?? 'green',
            'text' => $entity->data['text'] ?? 'No banner text available'
        ];
    }

    /**
     * Process controller method data for CASE 4: method_of_presentation = controller_method
     * 
     * @param FactsheetEntity $entity
     * @param Substance $substance
     * @return array
     */
    private function processControllerMethodData($entity, $substance): array
    {
        $processedData = [
            'type' => 'database_table',
            'key_value_data' => []
        ];

        $method = $entity->data['method'] ?? null;

        if ($method && method_exists($this, $method)) {
            try {
                $methodData = $this->$method($substance);
                
                // Check if the method returned a special type (like banner or table)
                if (is_array($methodData) && isset($methodData['type'])) {
                    if ($methodData['type'] === 'banner') {
                        // Return banner data directly
                        return [
                            'type' => 'banner',
                            'color' => $methodData['color'] ?? 'light-green',
                            'text' => $methodData['text'] ?? 'No data available'
                        ];
                    } elseif ($methodData['type'] === 'table') {
                        // Return table data directly
                        return [
                            'type' => 'table',
                            'table_data' => $methodData['table_data'] ?? [],
                            'years' => $methodData['years'] ?? [],
                            'summary' => $methodData['summary'] ?? []
                        ];
                    } elseif ($methodData['type'] === 'matrix_table') {
                        // Return matrix table data directly
                        return [
                            'type' => 'matrix_table',
                            'matrix_data' => $methodData['matrix_data'] ?? [],
                            'summary' => $methodData['summary'] ?? []
                        ];
                    } elseif ($methodData['type'] === 'hazards_pbt_table') {
                        return [
                            'type' => 'hazards_pbt_table',
                            'rows' => $methodData['rows'] ?? [],
                            'source_label' => $methodData['source_label'] ?? null,
                            'updated_at' => $methodData['updated_at'] ?? null,
                            'legend' => $methodData['legend'] ?? [],
                        ];
                    }
                }
                
                // Regular key-value data
                $processedData['key_value_data'] = $methodData;
            } catch (\Exception $e) {
                Log::error("Error calling controller method {$method}: " . $e->getMessage());
                $processedData['key_value_data'] = ['error' => 'Error loading data'];
            }
        } else {
            $processedData['key_value_data'] = ['error' => 'Controller method not found or not specified'];
        }

        return $processedData;
    }

    /**
     * Get ecotoxicity data for the given substance
     * Matches legacy system format from ecotox.lowestpnec table
     * 
     * @param Substance $substance
     * @return array
     */
    private function getEcotoxicityData($substance): array
    {
        $data = [];

        try {
            // Convert substance code to numeric sus_id (legacy system uses numeric part of code)
            $susId = intval($substance->code);
            
            // Get freshwater PNEC record (matrix = 1, active = 1) - matches legacy SQL
            $freshwaterPnec = LowestPNECMain::where('sus_id', $susId)
                ->where('lowest_matrix', 1)
                ->where('lowest_active', 1)
                ->first();

            if ($freshwaterPnec) {
                // Parse test endpoint for species and endpoint 
                // Format can be: species\nendpoint|other OR <i>species</i><br>|endpoint|other
                $species = 'n.r.';
                $endpoint = '';
                
                if ($freshwaterPnec->lowest_test_endpoint) {
                    $testEndpoint = $freshwaterPnec->lowest_test_endpoint;
                    
                    // Handle HTML format: <i>species</i><br>|endpoint|other
                    if (strpos($testEndpoint, '<br>') !== false) {
                        $array1 = explode('<br>', $testEndpoint);
                        if (count($array1) > 0) {
                            // Clean HTML tags from species
                            $species = strip_tags(trim($array1[0])) ?: 'n.r.';
                            if (count($array1) > 1) {
                                $array2 = explode('|', $array1[1]);
                                $endpoint = isset($array2[1]) ? trim($array2[1]) : '';
                                $endpoint = $endpoint === 'n.r.' ? '' : $endpoint;
                            }
                        }
                    } else {
                        // Handle plain text format: species\nendpoint|other
                        $array1 = explode("\n", $testEndpoint);
                        if (count($array1) > 0) {
                            $species = trim($array1[0]) ?: 'n.r.';
                            if (count($array1) > 1) {
                                $array2 = explode('|', $array1[1]);
                                $endpoint = trim($array2[0]) ?? '';
                            }
                        }
                    }
                }

                // Get PNEC values for all matrix types (using legacy getLowestPNEC equivalent)
                $lowestPnecValues = $this->getLowestPNECByMatrix($susId);

                // Main freshwater data
                $data['lowest_pnec_fresh_water'] = $lowestPnecValues[1] ? number_format($lowestPnecValues[1], 2) : 'n.r.';
                $data['experimental_predicted'] = $freshwaterPnec->lowest_derivation_method ?: 'n.r.';
                $data['species'] = $species;
                $data['af'] = $freshwaterPnec->lowest_AF ?: '0';
                $data['endpoint'] = $endpoint ?: '';
                $data['reference'] = $freshwaterPnec->lowest_base_id ?: '';
                
                // Other matrix types
                $data['lowest_pnec_marine_water'] = $lowestPnecValues[2] ? number_format($lowestPnecValues[2], 3) : 'n.r.';
                $data['lowest_pnec_sediment'] = $lowestPnecValues[3] ? number_format($lowestPnecValues[3], 1) : 'n.r.';
                $data['lowest_pnec_biota'] = $lowestPnecValues[4] ? number_format($lowestPnecValues[4], 0) : 'n.r.';
            } else {
                // No freshwater PNEC data available
                $data['message'] = 'No ecotoxicity data available for this substance';
            }
        } catch (\Exception $e) {
            Log::error('Error retrieving ecotoxicity data: ' . $e->getMessage());
            $data['error'] = 'Error loading ecotoxicity data';
        }

        return $data;
    }

    /**
     * Get lowest PNEC values by matrix type (equivalent to legacy getLowestPNEC function)
     * This uses the aggregated LowestPNEC table, not the individual LowestPNECMain records
     * 
     * @param int $susId
     * @return array Matrix type => PNEC value
     */
    private function getLowestPNECByMatrix($susId): array
    {
        $pnecValues = [
            1 => null, // freshwater
            2 => null, // marine water  
            3 => null, // sediments
            4 => null, // biota
        ];

        // Get aggregated PNEC values from LowestPNEC table (matches legacy getLowestPNEC function)
        $pnecRecord = LowestPNEC::where('sus_id', $susId)->first();
        
        if ($pnecRecord) {
            $pnecValues[1] = $pnecRecord->lowest_pnec_value_1; // freshwater
            $pnecValues[2] = $pnecRecord->lowest_pnec_value_2; // marine water
            $pnecValues[3] = $pnecRecord->lowest_pnec_value_3; // sediments  
            $pnecValues[4] = $pnecRecord->lowest_pnec_value_4; // biota
        }

        return $pnecValues;
    }

    private function getHazardsPbtPmtData($substance): array
    {
        try {
            $currentConclusion = SubstanceClassification::query()
                ->with('supports')
                ->where('susdat_substance_id', $substance->id)
                ->where('is_current', true)
                ->orderByRaw("CASE kind WHEN 'classification' THEN 1 WHEN 'derivation' THEN 2 WHEN 'auto_baseline' THEN 3 ELSE 4 END")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if (! $currentConclusion) {
                return [
                    'type' => 'banner',
                    'color' => 'light-green',
                    'text' => 'No Hazards derivation or classification data available for this substance yet.',
                ];
            }

            $rows = [];
            foreach (['P', 'B', 'M', 'T'] as $criterion) {
                $classification = $currentConclusion->{$criterion} ?? null;
                $winnerPoints = $currentConclusion->{strtolower($criterion) . '_total_points'};
                $allPoints = $currentConclusion->{strtolower($criterion) . '_all_points'};
                $supports = $currentConclusion->supports
                    ->where('criterion', $criterion)
                    ->where('is_winner', true)
                    ->pluck('source_type')
                    ->filter()
                    ->unique()
                    ->values();

                $rows[] = [
                    'criterion' => match ($criterion) {
                        'P' => 'P (Persistence)',
                        'B' => 'B (Bioaccumulation)',
                        'M' => 'M (Mobility)',
                        'T' => 'T (Toxicity)',
                    },
                    'classification' => $classification ?: 'no data',
                    'score' => $this->getHazardsCriterionScore($classification),
                    'reference' => $supports->isNotEmpty() ? $supports->implode('; ') : 'N/A',
                    'consensus' => $this->formatHazardsConsensus($winnerPoints, $allPoints),
                ];
            }

            return [
                'type' => 'hazards_pbt_table',
                'rows' => $rows,
                'classification_rows' => $this->getHazardsPbtPmtClassificationRows($currentConclusion),
                'pbmt_score' => $this->getHazardsPbmtPrioritizationScore($currentConclusion),
                'source_label' => $currentConclusion->kind,
                'updated_at' => $currentConclusion->updated_at,
                'legend' => [
                    'P' => ['vP = 1', 'P = 0.75', 'sP (suspectP) = 0.5', 'not P = 0', 'probably-nP = 0.1', 'no data = 0.1'],
                    'B' => ['vB = 1', 'B = 0.75', 'sB (suspectB) = 0.5', 'not B = 0', 'probably-nB = 0.1', 'no data = 0.1'],
                    'M' => ['vM = 1', 'M = 0.75', 'sM (suspectM) = 0.5', 'not M = 0', 'probably-nM = 0.1', 'no data = 0.1'],
                    'T' => ['T+ = 1', 'T = 0.75', 'sT (suspectT) = 0.5', 'not T = 0', 'probably-nT = 0.1', 'no data = 0.1'],
                ],
                'classification_legend' => [
                    'PBT' => [
                        'if ((P or vP) and (B or vB) and (T or T+)):',
                        'Classification: PBT',
                        'PBT score = 1',
                        'else:',
                        'Classification: not PBT',
                        'PBT score = 0',
                    ],
                    'vPvB' => [
                        'if (vP and vB):',
                        'Classification: vPvB',
                        'vPvB score = 1',
                        'else:',
                        'Classification: not vPvB',
                        'vPvB score = 0',
                    ],
                    'PMT' => [
                        'if ((P or vP) and (M or vM) and (T or T+)):',
                        'Classification: PMT',
                        'PMT score = 1',
                        'else:',
                        'Classification: not PMT',
                        'PMT score = 0',
                    ],
                    'vPvM' => [
                        'if (vP and vM):',
                        'Classification: vPvM',
                        'vPvM score = 1',
                        'else:',
                        'Classification: not vPvM',
                        'vPvM score = 0',
                    ],
                ],
                'pbmt_legend' => 'PBMT = P score + B score + M score + T score + PBT score + vPvB score + PMT score + vPvM score',
            ];
        } catch (\Throwable $e) {
            Log::error('Error loading Hazards PBT/PMT factsheet data: ' . $e->getMessage());

            return [
                'type' => 'banner',
                'color' => 'light-green',
                'text' => 'Hazards PBT/PMT data could not be loaded.',
            ];
        }
    }

    private function getHazardsCriterionScore(?string $classification): string
    {
        return number_format($this->getHazardsCriterionScoreValue($classification), 2, '.', '');
    }

    private function getHazardsCriterionScoreValue(?string $classification): float
    {
        $code = trim((string) ($classification ?? ''));

        $scores = [
            'vP' => 1.00,
            'P' => 0.75,
            'sP' => 0.50,
            'nP' => 0.00,
            'probably-nP' => 0.10,
            'vB' => 1.00,
            'B' => 0.75,
            'sB' => 0.50,
            'nB' => 0.00,
            'probably-nB' => 0.10,
            'vM' => 1.00,
            'M' => 0.75,
            'sM' => 0.50,
            'nM' => 0.00,
            'probably-nM' => 0.10,
            'T+' => 1.00,
            'T' => 0.75,
            'sT' => 0.50,
            'nT' => 0.00,
            'probably-nT' => 0.10,
        ];

        if ($code === '' || strtolower($code) === 'no data') {
            return 0.10;
        }

        return (float) ($scores[$code] ?? 0.10);
    }

    private function formatHazardsConsensus($winnerPoints, $allPoints): string
    {
        if (! is_numeric($winnerPoints) || ! is_numeric($allPoints) || (float) $allPoints <= 0) {
            return 'N/A';
        }

        $percent = ((float) $winnerPoints / (float) $allPoints) * 100;
        $formatted = number_format($percent, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted . '%';
    }

    private function getHazardsPbtPmtClassificationRows(SubstanceClassification $currentConclusion): array
    {
        $p = (string) ($currentConclusion->P ?? '');
        $b = (string) ($currentConclusion->B ?? '');
        $m = (string) ($currentConclusion->M ?? '');
        $t = (string) ($currentConclusion->T ?? '');

        $isPOrvP = in_array($p, ['P', 'vP'], true);
        $isVvP = $p === 'vP';
        $isBOrvB = in_array($b, ['B', 'vB'], true);
        $isVvB = $b === 'vB';
        $isMOrvM = in_array($m, ['M', 'vM'], true);
        $isVvM = $m === 'vM';
        $isTOrTPlus = in_array($t, ['T', 'T+'], true);

        return [
            [
                'classification' => 'PBT',
                'result' => ($isPOrvP && $isBOrvB && $isTOrTPlus) ? 'PBT' : 'not PBT',
                'score' => ($isPOrvP && $isBOrvB && $isTOrTPlus) ? '1' : '0',
            ],
            [
                'classification' => 'vPvB',
                'result' => ($isVvP && $isVvB) ? 'vPvB' : 'not vPvB',
                'score' => ($isVvP && $isVvB) ? '1' : '0',
            ],
            [
                'classification' => 'PMT',
                'result' => ($isPOrvP && $isMOrvM && $isTOrTPlus) ? 'PMT' : 'not PMT',
                'score' => ($isPOrvP && $isMOrvM && $isTOrTPlus) ? '1' : '0',
            ],
            [
                'classification' => 'vPvM',
                'result' => ($isVvP && $isVvM) ? 'vPvM' : 'not vPvM',
                'score' => ($isVvP && $isVvM) ? '1' : '0',
            ],
        ];
    }

    private function getHazardsPbmtPrioritizationScore(SubstanceClassification $currentConclusion): string
    {
        $classificationRows = $this->getHazardsPbtPmtClassificationRows($currentConclusion);

        $criterionScore = $this->getHazardsCriterionScoreValue($currentConclusion->P)
            + $this->getHazardsCriterionScoreValue($currentConclusion->B)
            + $this->getHazardsCriterionScoreValue($currentConclusion->M)
            + $this->getHazardsCriterionScoreValue($currentConclusion->T);

        $classificationScore = collect($classificationRows)
            ->sum(fn (array $row) => (float) ($row['score'] ?? 0));

        $formatted = number_format($criterionScore + $classificationScore, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Get environmental occurrence data detailed for the given substance
     * This method is called from factsheet entities with method_of_presentation = controller_method
     * 
     * @param Substance $substance
     * @return array
     */
    private function getEnvironmentalOccurrenceDataDetailed($substance): array
    {
        try {
            // Check if statistics exist for this substance
            $statisticsRecord = FactsheetStatistic::where('substance_id', $substance->id)->first();
            
            if (!$statisticsRecord || !$statisticsRecord->meta_data) {
                // No statistics available - return banner message
                return [
                    'type' => 'banner',
                    'color' => 'light-green',
                    'text' => "No records for {$substance->name} found in Chemical Occurrence Database"
                ];
            }

            $statisticsData = $statisticsRecord->meta_data;
            
            // Check if country_year data exists
            if (isset($statisticsData['country_year']) && isset($statisticsData['country_year']['data'])) {
                $countryYearData = $statisticsData['country_year']['data'];
                $yearRange = $statisticsData['country_year']['year_range'] ?? [];
                
                // Get all unique years from the data and sort them
                $allYears = [];
                foreach ($countryYearData as $country => $yearData) {
                    $allYears = array_merge($allYears, array_keys($yearData));
                }
                $allYears = array_unique($allYears);
                sort($allYears);
                
                // Calculate country totals and sort by total records (descending)
                $countryTotals = [];
                foreach ($countryYearData as $country => $yearData) {
                    $countryTotals[$country] = array_sum($yearData);
                }
                arsort($countryTotals);
                
                // Prepare table data
                $tableData = [];
                foreach ($countryTotals as $country => $totalRecords) {
                    $row = [
                        'country' => $country,
                        'total_records' => $totalRecords,
                        'years' => []
                    ];
                    
                    // Add data for each year
                    foreach ($allYears as $year) {
                        $row['years'][$year] = $countryYearData[$country][$year] ?? 0;
                    }
                    
                    $tableData[] = $row;
                }
                
                return [
                    'type' => 'table',
                    'table_data' => $tableData,
                    'years' => $allYears,
                    'summary' => [
                        'total_countries' => count($countryYearData),
                        'total_records' => $statisticsData['total_records'] ?? 0,
                        'year_range' => ($yearRange['min_year'] ?? 'N/A') . ' - ' . ($yearRange['max_year'] ?? 'N/A'),
                        'generated_at' => isset($statisticsData['generated_at']) ? 
                            \Carbon\Carbon::parse($statisticsData['generated_at'])->format('M d, Y H:i') : 'N/A'
                    ]
                ];
            } else {
                return [
                    'type' => 'banner',
                    'color' => 'light-green', 
                    'text' => "No country-year data available for {$substance->name} in Chemical Occurrence Database"
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error retrieving environmental occurrence data: ' . $e->getMessage());
            return [
                'error' => 'Error loading environmental occurrence data'
            ];
        }
    }

    /**
     * Get environmental occurrence matrix data for the given substance
     * This method is called from factsheet entities with method_of_presentation = controller_method
     * 
     * @param Substance $substance
     * @return array
     */
    private function getEnvironmentalOccurrenceMatrixData($substance): array
    {
        try {
            // Check if statistics exist for this substance
            $statisticsRecord = FactsheetStatistic::where('substance_id', $substance->id)->first();
            
            if (!$statisticsRecord || !$statisticsRecord->meta_data) {
                // No statistics available - return banner message
                return [
                    'type' => 'banner',
                    'color' => 'light-green',
                    'text' => "No matrix data for {$substance->name} found in Chemical Occurrence Database"
                ];
            }

            $statisticsData = $statisticsRecord->meta_data;
            
            // Check if matrix data exists
            if (isset($statisticsData['matrix']) && isset($statisticsData['matrix']['data'])) {
                $matrixData = $statisticsData['matrix']['data'];
                
                // Sort by record count (descending) to show most common matrices first
                usort($matrixData, function ($a, $b) {
                    return $b['record_count'] - $a['record_count'];
                });
                
                return [
                    'type' => 'matrix_table',
                    'matrix_data' => $matrixData,
                    'summary' => [
                        'total_matrices' => count($matrixData),
                        'total_records' => $statisticsData['total_records'] ?? 0,
                        'generated_at' => isset($statisticsData['generated_at']) ? 
                            \Carbon\Carbon::parse($statisticsData['generated_at'])->format('M d, Y H:i') : 'N/A'
                    ]
                ];
            } else {
                return [
                    'type' => 'banner',
                    'color' => 'light-green', 
                    'text' => "No matrix data available for {$substance->name} in Chemical Occurrence Database"
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error retrieving environmental occurrence matrix data: ' . $e->getMessage());
            return [
                'error' => 'Error loading environmental occurrence matrix data'
            ];
        }
    }

}



