<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HazardsDataController extends Controller
{
    private const WANTED_FATE_ENDPOINTS = [
        'ReadyBiodeg',
        'Biodeg. Half-Life',
        'Soil Adsorp. Coeff. (Koc)',
        'Bioconcentration Factor',
    ];

    private const WANTED_PROPERTY_NAMES = [
        'Water Solubility',
        'LogKoa: Octanol-Air',
        'LogKow: Octanol-Water',
        'pKa Acidic - Apparent',
        'pKa Acidic – Apparent',
        'pKa Basic - Apparent',
        'pKa Basic – Apparent',
        'LogD5.5',
        'LogD7.4',
    ];

    private const PBMT_ASSESSMENT_CLASSES = [
        'vP',
        'P',
        'sP',
        'nP',
        'probably-nP',
        'vB',
        'B',
        'sB',
        'nB',
        'probably-nB',
        'vM',
        'M',
        'sM',
        'nM',
        'probably-nM',
        'T+',
        'T',
        'sT',
        'nT',
        'probably-nT',
    ];

    public function index()
    {
        return redirect()->route('hazardshome.index');
    }

    public function filter(Request $request)
    {
        $dataDomains = ComptoxSubstanceData::query()
            ->whereNotNull('data_domain')
            ->where('data_domain', '!=', '')
            ->select('data_domain')
            ->distinct()
            ->orderBy('data_domain')
            ->pluck('data_domain');

        $referenceTypes = ComptoxSubstanceData::query()
            ->whereNotNull('reference_type')
            ->where('reference_type', '!=', '')
            ->select('reference_type')
            ->distinct()
            ->orderBy('reference_type')
            ->pluck('reference_type');

        $availableAssessmentClasses = ComptoxSubstanceData::query()
            ->whereNotNull('assessment_class')
            ->where('assessment_class', '!=', '')
            ->select('assessment_class')
            ->distinct()
            ->orderBy('assessment_class')
            ->pluck('assessment_class');

        $assessmentClasses = collect(self::PBMT_ASSESSMENT_CLASSES)
            ->filter(static fn (string $assessmentClass) => $availableAssessmentClasses->contains($assessmentClass))
            ->values();

        $groupedAssessmentClasses = [
            'P' => $assessmentClasses->filter(static fn (string $value) => str_contains($value, 'P'))->values(),
            'B' => $assessmentClasses->filter(static fn (string $value) => str_contains($value, 'B'))->values(),
            'M' => $assessmentClasses->filter(static fn (string $value) => str_contains($value, 'M'))->values(),
            'T' => $assessmentClasses->filter(static fn (string $value) => str_contains($value, 'T'))->values(),
        ];

        return view('hazards.filter', [
            'request' => $request,
            'dataDomains' => $dataDomains,
            'referenceTypes' => $referenceTypes,
            'assessmentClasses' => $assessmentClasses,
            'groupedAssessmentClasses' => $groupedAssessmentClasses,
        ]);
    }

    public function search(Request $request)
    {
        $displayLayout = $this->resolveDisplayLayout($request);
        $searchParameters = [
            'display_layout' => $displayLayout === 'summary'
                ? 'Summary (avg/min/max)'
                : 'Detailed records',
        ];

        $resultsQuery = ComptoxSubstanceData::query()
            ->with(['substance', 'editorUser']);

        $substances = $this->normalizeArrayInput($request->input('substances'));
        if (! empty($substances)) {
            $resultsQuery->whereIn('susdat_substance_id', $substances);
            $selectedSubstances = Substance::whereIn('id', $substances)
                ->orderBy('name')
                ->get();
            $searchParameters['substances'] = $selectedSubstances->pluck('name');
        } else {
            session()->flash('info', 'Please select at least one substance to search.');
            return redirect()->route('hazards.data.search.filter');
        }

        $dataDomains = $this->normalizeArrayInput($request->input('dataDomainSearch'));
        if (! empty($dataDomains)) {
            $resultsQuery->whereIn('data_domain', $dataDomains);
            $searchParameters['data_domain'] = array_map(function ($domain) {
                if ($domain === 'fate_transport') {
                    return 'Fate and Transport';
                }
                if ($domain === 'physchem') {
                    return 'Phys-Chemical';
                }
                return ucwords(str_replace('_', ' ', (string) $domain));
            }, $dataDomains);
        }

        $referenceTypes = $this->normalizeArrayInput($request->input('referenceTypeSearch'));
        if (! empty($referenceTypes)) {
            $resultsQuery->whereIn('reference_type', $referenceTypes);
            $searchParameters['reference_type'] = $referenceTypes;
        }

        $assessmentClasses = $this->normalizeArrayInput($request->input('assessmentClassSearch'));
        if (! empty($assessmentClasses)) {
            $resultsQuery->whereIn('assessment_class', $assessmentClasses);
            $searchParameters['assessment_class'] = $assessmentClasses;
        }

        $normanParameters = $this->normalizeArrayInput($request->input('normanParameterSearch'));
        if (! empty($normanParameters)) {
            $resultsQuery->whereIn('norman_parameter_name', $normanParameters);
            $searchParameters['norman_parameter'] = $normanParameters;
        }

        $specificParameters = $this->normalizeArrayInput($request->input('specificParameterSearch'));
        if (! empty($specificParameters)) {
            $resultsQuery->whereIn('specific_parameter_name', $specificParameters);
            $searchParameters['specific_parameter'] = $specificParameters;
        }

        $testTypes = array_map('strval', $this->normalizeArrayInput($request->input('testTypeSearch')));
        if (! empty($testTypes)) {
            $resultsQuery->whereIn('test_type', $testTypes);
            $searchParameters['test_type'] = array_map(static function ($testType) {
                return match ((string) $testType) {
                    '2' => 'Experimental',
                    '3' => 'Predicted',
                    default => (string) $testType,
                };
            }, $testTypes);
        }

        $sourceRecordTypes = $this->normalizeArrayInput($request->input('sourceRecordTypeSearch'));
        if (! empty($sourceRecordTypes)) {
            $resultsQuery->whereIn('source_record_type', $sourceRecordTypes);
        }

        $mainRequest = $request->all();
        $databaseKey = 'hazards';
        $resultsObjectsCount = ComptoxSubstanceData::count();
        $filteredRecordsCount = (clone $resultsQuery)->count();
        $queryLogId = QueryLog::query()->latest('id')->value('id') ?? 0;

        if (! $request->has('page')) {
            $queryLogId = $this->logSearchQuery(
                filteredQuery: $resultsQuery,
                mainRequest: $mainRequest,
                totalCount: $resultsObjectsCount,
                actualCount: $filteredRecordsCount,
                databaseKey: $databaseKey
            );
        }

        if ($displayLayout === 'summary') {
            return $this->renderSummaryLayout(
                filteredQuery: $resultsQuery,
                request: $request,
                searchParameters: $searchParameters,
                resultsObjectsCount: $resultsObjectsCount,
                filteredRecordsCount: $filteredRecordsCount,
                queryLogId: $queryLogId,
                mainRequest: $mainRequest
            );
        }

        return $this->renderDetailedLayout(
            filteredQuery: $resultsQuery,
            request: $request,
            searchParameters: $searchParameters,
            resultsObjectsCount: $resultsObjectsCount,
            filteredRecordsCount: $filteredRecordsCount,
            queryLogId: $queryLogId,
            mainRequest: $mainRequest
        );
    }

    private function renderDetailedLayout(
        Builder $filteredQuery,
        Request $request,
        array $searchParameters,
        int $resultsObjectsCount,
        int $filteredRecordsCount,
        int $queryLogId,
        array $mainRequest
    ) {
        $domainCounts = $this->buildDomainCounts(clone $filteredQuery);
        $resultsObjects = (clone $filteredQuery)
            ->orderBy('id', 'asc')
            ->paginate(200)
            ->withQueryString();

        return view('hazards.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'filteredRecordsCount' => $filteredRecordsCount,
            'domainCounts' => $domainCounts,
            'query_log_id' => $queryLogId,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $mainRequest);
    }

    private function renderSummaryLayout(
        Builder $filteredQuery,
        Request $request,
        array $searchParameters,
        int $resultsObjectsCount,
        int $filteredRecordsCount,
        int $queryLogId,
        array $mainRequest
    ) {
        $summaryScopeQuery = $this->applySummaryScope(clone $filteredQuery);
        $summaryRows = (clone $summaryScopeQuery)
            ->whereNotNull('value_assessment_index')
            ->selectRaw(
                'susdat_substance_id, data_domain, norman_parameter_name, specific_parameter_name, unit, test_type,
                COUNT(*) as records_count,
                SUM(value_assessment_index) as sum_value,
                AVG(value_assessment_index) as avg_value,
                MIN(value_assessment_index) as min_value,
                MAX(value_assessment_index) as max_value'
            )
            ->groupBy('susdat_substance_id', 'data_domain', 'norman_parameter_name', 'specific_parameter_name', 'unit', 'test_type')
            ->orderBy('susdat_substance_id')
            ->orderBy('data_domain')
            ->orderBy('norman_parameter_name')
            ->get();

        $filteredRecordsCount = (clone $summaryScopeQuery)->count();
        $resultsObjectsCount = $this->summaryTotalCount();
        $searchParameters['summary_scope'] = 'CompTox API fate/property only';

        $summaryBySubstance = [];
        $otherTestTypeRowsCount = 0;

        foreach ($summaryRows as $row) {
            $substanceId = (int) ($row->susdat_substance_id ?? 0);
            if ($substanceId <= 0) {
                continue;
            }

            $domainKey = (string) ($row->data_domain ?: 'unknown');
            $specificParameterName = $this->normalizeSummarySpecificParameter($row->specific_parameter_name);
            $parameterKey = implode('||', [
                (string) ($row->norman_parameter_name ?: 'Unknown parameter'),
                (string) ($specificParameterName ?: ''),
                (string) ($row->unit ?: ''),
            ]);

            if (! isset($summaryBySubstance[$substanceId])) {
                $summaryBySubstance[$substanceId] = [
                    'substance_id' => $substanceId,
                    'domains' => [],
                ];
            }

            if (! isset($summaryBySubstance[$substanceId]['domains'][$domainKey])) {
                $summaryBySubstance[$substanceId]['domains'][$domainKey] = [
                    'domain_key' => $domainKey,
                    'domain_label' => $this->formatDomainLabel($domainKey),
                    'parameters' => [],
                ];
            }

            if (! isset($summaryBySubstance[$substanceId]['domains'][$domainKey]['parameters'][$parameterKey])) {
                $summaryBySubstance[$substanceId]['domains'][$domainKey]['parameters'][$parameterKey] = [
                    'norman_parameter_name' => $row->norman_parameter_name ?: 'Unknown parameter',
                    'specific_parameter_name' => $specificParameterName,
                    'unit' => $row->unit ?: null,
                    'experimental' => $this->emptySummaryStats(),
                    'predicted' => $this->emptySummaryStats(),
                ];
            }

            $testType = (string) $row->test_type;
            $stats = [
                'count' => (int) $row->records_count,
                'sum' => (float) ($row->sum_value ?? 0),
                'min' => is_null($row->min_value) ? null : (float) $row->min_value,
                'max' => is_null($row->max_value) ? null : (float) $row->max_value,
            ];

            if ($testType === '2') {
                $this->mergeSummaryStats(
                    $summaryBySubstance[$substanceId]['domains'][$domainKey]['parameters'][$parameterKey]['experimental'],
                    $stats
                );
            } elseif ($testType === '3') {
                $this->mergeSummaryStats(
                    $summaryBySubstance[$substanceId]['domains'][$domainKey]['parameters'][$parameterKey]['predicted'],
                    $stats
                );
            } else {
                $otherTestTypeRowsCount += (int) $row->records_count;
            }
        }

        $substanceIds = array_keys($summaryBySubstance);
        $substancesById = Substance::whereIn('id', $substanceIds)->get()->keyBy('id');

        $domainSortOrder = ['fate_transport' => 1, 'physchem' => 2];
        foreach ($summaryBySubstance as $substanceId => &$substanceData) {
            $substanceData['substance'] = $substancesById->get($substanceId);

            foreach ($substanceData['domains'] as &$domainData) {
                foreach ($domainData['parameters'] as &$parameterData) {
                    $parameterData['experimental'] = $this->finalizeSummaryStats($parameterData['experimental']);
                    $parameterData['predicted'] = $this->finalizeSummaryStats($parameterData['predicted']);
                }
                unset($parameterData);

                $domainData['parameters'] = collect($domainData['parameters'])
                    ->sortBy(function ($parameter) {
                        return [
                            strtolower((string) $parameter['norman_parameter_name']),
                            strtolower((string) ($parameter['specific_parameter_name'] ?? '')),
                        ];
                    })
                    ->values()
                    ->all();
            }
            unset($domainData);

            uasort($substanceData['domains'], function ($left, $right) use ($domainSortOrder) {
                $leftOrder = $domainSortOrder[$left['domain_key']] ?? 99;
                $rightOrder = $domainSortOrder[$right['domain_key']] ?? 99;

                if ($leftOrder === $rightOrder) {
                    return strcmp($left['domain_label'], $right['domain_label']);
                }

                return $leftOrder <=> $rightOrder;
            });
        }
        unset($substanceData);

        $aggregatedRowsCount = (int) $summaryRows->sum('records_count');
        $domainCounts = [
            'all' => $aggregatedRowsCount,
            'physchem' => (int) $summaryRows->where('data_domain', 'physchem')->sum('records_count'),
            'fate_transport' => (int) $summaryRows->where('data_domain', 'fate_transport')->sum('records_count'),
        ];

        return view('hazards.summary.index', [
            'summaryBySubstance' => $summaryBySubstance,
            'resultsObjectsCount' => $resultsObjectsCount,
            'filteredRecordsCount' => $filteredRecordsCount,
            'aggregatedRowsCount' => $aggregatedRowsCount,
            'domainCounts' => $domainCounts,
            'otherTestTypeRowsCount' => $otherTestTypeRowsCount,
            'query_log_id' => $queryLogId,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $mainRequest);
    }

    private function resolveDisplayLayout(Request $request): string
    {
        $layout = strtolower((string) $request->input('displayLayout', 'detailed'));

        return in_array($layout, ['detailed', 'summary'], true) ? $layout : 'detailed';
    }

    private function logSearchQuery(
        Builder $filteredQuery,
        array $mainRequest,
        int $totalCount,
        int $actualCount,
        string $databaseKey
    ): int {
        $now = now();
        $bindings = $filteredQuery->getBindings();
        $sql = $this->buildDebugSql($filteredQuery->toSql(), $bindings);

        $queryHash = hash('sha256', $sql);
        $knownActualCount = QueryLog::where('query_hash', $queryHash)
            ->where('total_count', (int) $totalCount)
            ->value('actual_count');

        try {
            return (int) QueryLog::insertGetId([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => auth()->check() ? auth()->id() : null,
                'total_count' => (int) $totalCount,
                'actual_count' => is_null($knownActualCount) ? $actualCount : (int) $knownActualCount,
                'database_key' => $databaseKey,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Exception $e) {
            if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                session()->flash('failure', 'Query logging error: '.$e->getMessage());
            } else {
                session()->flash('error', 'An error occurred while processing your request.');
            }
        }

        return QueryLog::query()->latest('id')->value('id') ?? 0;
    }

    private function buildDebugSql(string $sql, array $bindings): string
    {
        $safeBindings = array_map(static function ($binding) {
            if ($binding instanceof \DateTimeInterface) {
                return $binding->format('Y-m-d H:i:s');
            }

            if (is_bool($binding)) {
                return (int) $binding;
            }

            if (is_null($binding)) {
                return 'NULL';
            }

            return str_replace('%', '%%', (string) $binding);
        }, $bindings);

        try {
            return vsprintf(str_replace('?', "'%s'", $sql), $safeBindings);
        } catch (\Throwable) {
            return $sql;
        }
    }

    private function formatDomainLabel(?string $domain): string
    {
        return match ($domain) {
            'fate_transport' => 'Fate and Transport',
            'physchem' => 'Phys-Chemical',
            default => $domain ? ucwords(str_replace('_', ' ', $domain)) : 'Unknown',
        };
    }

    private function formatSummaryNumber(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $numericValue = (float) $value;
        $absoluteValue = abs($numericValue);

        if ($numericValue === 0.0) {
            return '0';
        }

        if ($absoluteValue > 0 && $absoluteValue < 0.001) {
            $formatted = sprintf('%.3e', $numericValue);
            $formatted = preg_replace('/\.?0+e/i', 'e', $formatted) ?? $formatted;
            $formatted = preg_replace('/e\+?(-?)0*(\d+)/i', 'e$1$2', $formatted) ?? $formatted;

            return strtolower($formatted);
        }

        $formatted = number_format($numericValue, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function emptySummaryStats(): array
    {
        return [
            'count' => 0,
            'sum' => 0.0,
            'avg' => '-',
            'min' => null,
            'max' => null,
        ];
    }

    private function mergeSummaryStats(array &$target, array $incoming): void
    {
        $incomingCount = (int) ($incoming['count'] ?? 0);
        if ($incomingCount <= 0) {
            return;
        }

        $target['count'] += $incomingCount;
        $target['sum'] += (float) ($incoming['sum'] ?? 0);

        $incomingMin = $incoming['min'] ?? null;
        if ($incomingMin !== null) {
            $target['min'] = $target['min'] === null ? $incomingMin : min($target['min'], $incomingMin);
        }

        $incomingMax = $incoming['max'] ?? null;
        if ($incomingMax !== null) {
            $target['max'] = $target['max'] === null ? $incomingMax : max($target['max'], $incomingMax);
        }
    }

    private function finalizeSummaryStats(array $stats): array
    {
        $count = (int) ($stats['count'] ?? 0);
        $sum = (float) ($stats['sum'] ?? 0);
        $min = $stats['min'] ?? null;
        $max = $stats['max'] ?? null;

        return [
            'count' => $count,
            'avg' => $count > 0 ? $this->formatSummaryNumber($sum / $count) : '-',
            'min' => $count > 0 ? $this->formatSummaryNumber($min) : '-',
            'max' => $count > 0 ? $this->formatSummaryNumber($max) : '-',
        ];
    }

    private function normalizeSummarySpecificParameter(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $normalized) === 1) {
            return null;
        }

        return $normalized;
    }

    private function buildDomainCounts(Builder $filteredQuery): array
    {
        $groupedCounts = (clone $filteredQuery)
            ->selectRaw('data_domain, COUNT(*) as records_count')
            ->groupBy('data_domain')
            ->pluck('records_count', 'data_domain');

        return [
            'all' => (int) $groupedCounts->sum(),
            'physchem' => (int) ($groupedCounts['physchem'] ?? 0),
            'fate_transport' => (int) ($groupedCounts['fate_transport'] ?? 0),
        ];
    }

    private function applySummaryScope(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->where(function (Builder $inner) {
                    $inner->where('source_record_type', 'fate')
                        ->whereIn('norman_parameter_name', self::WANTED_FATE_ENDPOINTS);
                })
                ->orWhere(function (Builder $inner) {
                    $inner->where('source_record_type', 'property')
                        ->whereIn('norman_parameter_name', self::WANTED_PROPERTY_NAMES);
                });
        });
    }

    private function summaryTotalCount(): int
    {
        return ComptoxSubstanceData::query()
            ->where(function (Builder $builder) {
                $builder
                    ->where(function (Builder $inner) {
                        $inner->where('source_record_type', 'fate')
                            ->whereIn('norman_parameter_name', self::WANTED_FATE_ENDPOINTS);
                    })
                    ->orWhere(function (Builder $inner) {
                        $inner->where('source_record_type', 'property')
                            ->whereIn('norman_parameter_name', self::WANTED_PROPERTY_NAMES);
                    });
            })
            ->count();
    }

    public function show(string $id)
    {
        $record = ComptoxSubstanceData::with(['substance', 'editorUser'])->find($id);
        if (! $record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json($record);
    }

    public function showForm(string $id)
    {
        $record = ComptoxSubstanceData::with(['substance', 'editorUser'])->find($id);
        if (! $record) {
            abort(404, 'Record not found');
        }

        return view('hazards.data.hazards-form', [
            'recordId' => $id,
            'record' => $record,
        ]);
    }

    /**
     * Normalize scalar/array/json-array request values into a clean array.
     */
    private function normalizeArrayInput(mixed $input): array
    {
        if (is_null($input)) {
            return [];
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                $input = [$input];
            }
        }

        if (! is_array($input)) {
            $input = [$input];
        }

        return array_values(array_filter($input, static function ($value) {
            return ! is_null($value) && $value !== '';
        }));
    }
}
