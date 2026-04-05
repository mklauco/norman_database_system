<?php

namespace App\Services\Hazards;

use App\Models\Hazards\ComptoxDetailRecord;
use App\Models\Hazards\ComptoxFateRecord;
use App\Models\Hazards\ComptoxPropertyRecord;
use App\Models\Hazards\ComptoxSubstanceData;
use Illuminate\Support\Facades\DB;

class ComptoxSubstanceDataFillService
{
    private const CHUNK_SIZE = 500;
    private const DATA_SOURCE_COMPTOX_DASHBOARD = 'CompTox Dashboard';
    private const DATA_SOURCE_JANUS = 'JANUS v. 1.0.3';

    private const FATE_TRANSPORT_PARAMETERS = [
        'ReadyBiodeg',
        'Biodeg. Half-Life',
        'Soil Adsorp. Coeff. (Koc)',
        'Bioconcentration Factor',
    ];

    public function __construct(
        private readonly HazardsClassificationService $classificationService
    ) {
    }

    public function fillFromParsedData(?int $editorUserId = null): array
    {
        $summary = [
            'total_fate_records' => 0,
            'total_property_records' => 0,
            'total_janus_records' => 0,
            'total_pikme_records' => 0,
            'inserted_fate_records' => 0,
            'updated_fate_records' => 0,
            'unchanged_fate_records' => 0,
            'inserted_property_records' => 0,
            'updated_property_records' => 0,
            'unchanged_property_records' => 0,
            'inserted_janus_records' => 0,
            'updated_janus_records' => 0,
            'unchanged_janus_records' => 0,
            'inserted_pikme_records' => 0,
            'updated_pikme_records' => 0,
            'unchanged_pikme_records' => 0,
            'inserted_records' => 0,
            'updated_records' => 0,
            'unchanged_records' => 0,
            'bootstrapped_substances' => 0,
        ];

        $detailsByDtxid = ComptoxDetailRecord::query()
            ->select(['susdat_substance_id', 'dtxid', 'preferred_name', 'casrn', 'inchikey', 'smiles'])
            ->get()
            ->keyBy('dtxid');

        $summary['total_fate_records'] = ComptoxFateRecord::count();
        ComptoxFateRecord::query()
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($records) use (&$summary, $detailsByDtxid, $editorUserId): void {
                foreach ($records as $record) {
                    $detail = $detailsByDtxid->get($record->dtxid);
                    $payload = $record->source_json ?? [];
                    $testType = $this->resolveTestType($record->value_type);

                    $value = $this->toNullableFloat($record->result_value);
                    $adScore = $this->toNullableFloat($payload['ad_value'] ?? $payload['ad_value_global'] ?? null);
                    $reliability = $this->toNullableFloat($payload['conf_index'] ?? $payload['confidence'] ?? null);
                    $valueRange = $this->toNullableString($payload['prop_value_error'] ?? null);
                    $applicabilityDomain = $this->toNullableString(
                        $payload['ad_conclusion'] ?? $payload['ad_conclusion_global'] ?? $payload['ad_reasoning'] ?? $payload['ad_reasoning_global'] ?? null
                    );

                    $assessmentClass = $this->resolveFateAssessmentClass(
                        $record->endpoint_name,
                        $value,
                        $testType === '2',
                        $adScore,
                        $reliability
                    );

                    $action = $this->upsertSubstanceDataRow(
                        [
                            'source_record_type' => 'fate',
                            'source_record_id' => $record->id,
                            'data_domain' => $this->resolveDataDomain($record->endpoint_name, 'fate_transport'),
                        ],
                        [
                            'parse_run_id' => $record->parse_run_id,
                            'comptox_payload_id' => $record->comptox_payload_id,
                            'data_source' => self::DATA_SOURCE_COMPTOX_DASHBOARD,
                            'editor' => $editorUserId,
                            'date' => now()->toDateString(),
                            'reference_type' => $testType === '2' ? 'Experimental' : 'Predicted',
                            'title' => $testType === '2' ? 'Experimental data' : 'Predicted by model',
                            'authors' => null,
                            'year' => null,
                            'bibliographic_source' => null,
                            'physico_chemical_source_doi' => null,
                            'test_type' => $testType,
                            'performed_under_glp' => null,
                            'standard_test' => false,
                            'susdat_substance_id' => $record->susdat_substance_id ?? $detail?->susdat_substance_id,
                            'dtxid' => $record->dtxid,
                            'substance_name' => $this->toNullableString($detail?->preferred_name),
                            'cas_no' => $this->toNullableString($detail?->casrn),
                            'inchikey' => $this->toNullableString($detail?->inchikey),
                            'smiles' => $this->toNullableString($detail?->smiles),
                            'radio_labeled_substance' => null,
                            'standard_qualifier' => '=',
                            'standard_used' => null,
                            'test_matrix' => $this->getTestMatrix($record->endpoint_name),
                            'test_species' => $record->endpoint_name === 'Bioconcentration Factor' ? 'fish' : null,
                            'duration_days' => null,
                            'exposure_concentration' => null,
                            'ph' => null,
                            'temperature_c' => null,
                            'total_organic_carbon' => null,
                            'original_parameter_name' => $record->endpoint_name,
                            'original_qualifier' => '=',
                            'original_value' => $value,
                            'original_value_range' => $valueRange,
                            'original_unit' => $this->toNullableString($record->unit) ?? 'N/A',
                            'norman_parameter_name' => $record->endpoint_name,
                            'specific_parameter_name' => null,
                            'assessment_qualifier' => '=',
                            'assessment_class' => $assessmentClass,
                            'value_assessment_index' => $value,
                            'value_standardised_score' => $value,
                            'unit' => $this->toNullableString($record->unit) ?? 'N/A',
                            'general_comment' => 'CompTox Dashboard data',
                            'applicability_domain' => $applicabilityDomain,
                            'applicability_domain_score' => $adScore,
                            'reliability_score' => $reliability,
                            'reliability_score_system' => null,
                            'reliability_rational' => null,
                            'institution_of_reliability_score' => null,
                            'regulatory_purpose' => 'Screening and prioritization',
                            'use_of_study' => 'Yes',
                        ]
                    );

                    $this->tallyAction($summary, $action, 'fate');
                }
            });

        $summary['total_property_records'] = ComptoxPropertyRecord::count();
        ComptoxPropertyRecord::query()
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($records) use (&$summary, $detailsByDtxid, $editorUserId): void {
                foreach ($records as $record) {
                    $detail = $detailsByDtxid->get($record->dtxid);
                    $payload = $record->source_json ?? [];
                    $testType = $this->resolveTestType($record->prop_type);
                    $value = $this->toNullableFloat($record->value);
                    $adScore = $this->toNullableFloat($payload['ad_value'] ?? $payload['ad_value_global'] ?? null);
                    $reliability = $this->toNullableFloat($payload['conf_index'] ?? $payload['confidence'] ?? null);
                    $valueRange = $this->toNullableString($payload['prop_value_error'] ?? null);
                    $applicabilityDomain = $this->toNullableString(
                        $payload['ad_conclusion'] ?? $payload['ad_conclusion_global'] ?? $payload['ad_reasoning'] ?? $payload['ad_reasoning_global'] ?? null
                    );

                    $action = $this->upsertSubstanceDataRow(
                        [
                            'source_record_type' => 'property',
                            'source_record_id' => $record->id,
                            'data_domain' => $this->resolveDataDomain($record->name, 'physchem'),
                        ],
                        [
                            'parse_run_id' => $record->parse_run_id,
                            'comptox_payload_id' => $record->comptox_payload_id,
                            'data_source' => self::DATA_SOURCE_COMPTOX_DASHBOARD,
                            'editor' => $editorUserId,
                            'date' => now()->toDateString(),
                            'reference_type' => $testType === '2' ? 'Experimental' : 'Predicted',
                            'title' => $testType === '2' ? 'Experimental data' : 'Predicted by model',
                            'authors' => null,
                            'year' => null,
                            'bibliographic_source' => null,
                            'physico_chemical_source_doi' => null,
                            'test_type' => $testType,
                            'performed_under_glp' => null,
                            'standard_test' => false,
                            'susdat_substance_id' => $record->susdat_substance_id ?? $detail?->susdat_substance_id,
                            'dtxid' => $record->dtxid,
                            'substance_name' => $this->toNullableString($detail?->preferred_name),
                            'cas_no' => $this->toNullableString($detail?->casrn),
                            'inchikey' => $this->toNullableString($detail?->inchikey),
                            'smiles' => $this->toNullableString($detail?->smiles),
                            'radio_labeled_substance' => null,
                            'standard_qualifier' => '=',
                            'standard_used' => null,
                            'test_matrix' => $this->getTestMatrix($record->name),
                            'test_species' => $record->name === 'Bioconcentration Factor' ? 'fish' : null,
                            'duration_days' => null,
                            'exposure_concentration' => null,
                            'ph' => null,
                            'temperature_c' => null,
                            'total_organic_carbon' => null,
                            'original_parameter_name' => $record->name,
                            'original_qualifier' => '=',
                            'original_value' => $value,
                            'original_value_range' => $valueRange,
                            'original_unit' => $this->toNullableString($record->unit) ?? 'N/A',
                            'norman_parameter_name' => $record->name,
                            'specific_parameter_name' => $this->toNullableString($record->property_string_id),
                            'assessment_qualifier' => '=',
                            'assessment_class' => null,
                            'value_assessment_index' => $value,
                            'value_standardised_score' => $value,
                            'unit' => $this->toNullableString($record->unit) ?? 'N/A',
                            'general_comment' => 'CompTox Dashboard data',
                            'applicability_domain' => $applicabilityDomain,
                            'applicability_domain_score' => $adScore,
                            'reliability_score' => $reliability,
                            'reliability_score_system' => null,
                            'reliability_rational' => null,
                            'institution_of_reliability_score' => null,
                            'regulatory_purpose' => 'Screening and prioritization',
                            'use_of_study' => 'Yes',
                        ]
                    );

                    $this->tallyAction($summary, $action, 'property');
                }
            });

        $this->fillJanusData($summary, $editorUserId);
        $this->fillPikmeData($summary, $editorUserId, $detailsByDtxid);
        $this->bootstrapDerivationAndClassification($summary);

        return $summary;
    }

    private function bootstrapDerivationAndClassification(array &$summary): void
    {
        $substanceIds = ComptoxSubstanceData::query()
            ->whereNotNull('susdat_substance_id')
            ->distinct()
            ->orderBy('susdat_substance_id')
            ->pluck('susdat_substance_id');

        foreach ($substanceIds as $substanceId) {
            $this->classificationService->run((int) $substanceId);
            $summary['bootstrapped_substances']++;
        }
    }

    private function fillJanusData(array &$summary, ?int $editorUserId = null): void
    {
        $allowedSubstanceIds = ComptoxDetailRecord::query()
            ->whereNotNull('susdat_substance_id')
            ->distinct()
            ->pluck('susdat_substance_id');

        if ($allowedSubstanceIds->isEmpty()) {
            return;
        }

        $summary['total_janus_records'] = DB::table('hazards_janus as j')
            ->join('susdat_substances as s', DB::raw("'NS' || s.code"), '=', 'j.norman_id')
            ->whereIn('s.id', $allowedSubstanceIds)
            ->count();

        DB::table('hazards_janus as j')
            ->join('susdat_substances as s', DB::raw("'NS' || s.code"), '=', 'j.norman_id')
            ->whereIn('s.id', $allowedSubstanceIds)
            ->select([
                'j.*',
                's.id as matched_susdat_substance_id',
                's.dtxid as matched_dtxid',
                's.name as matched_name',
                's.name_dashboard as matched_name_dashboard',
                's.cas_number as matched_cas_number',
                's.stdinchikey as matched_inchikey',
                's.smiles as matched_smiles',
            ])
            ->orderBy('j.id')
            ->chunk(self::CHUNK_SIZE, function ($rows) use (&$summary, $editorUserId): void {
                foreach ($rows as $row) {
                    foreach ($this->getJanusParameters($row) as $index => $parameter) {
                        $syntheticSourceRecordId = ((int) $row->id * 100) + $index + 1;

                        $action = $this->upsertSubstanceDataRow(
                            [
                                'source_record_type' => 'janus',
                                'source_record_id' => $syntheticSourceRecordId,
                                'data_domain' => 'physchem',
                            ],
                            [
                                'parse_run_id' => null,
                                'comptox_payload_id' => null,
                                'data_source' => self::DATA_SOURCE_JANUS,
                                'editor' => $editorUserId,
                                'date' => '2024',
                                'reference_type' => 'Report, Excel table',
                                'title' => 'LIFE APEX project: prediction of PBT, CMR and ED values for NORMAN Substance Database compounds using JANUS tool',
                                'authors' => 'Kelsey Ng',
                                'year' => '2024',
                                'bibliographic_source' => 'https://www.vegahub.eu/portfolio-item/janus/',
                                'physico_chemical_source_doi' => null,
                                'test_type' => '3',
                                'performed_under_glp' => null,
                                'standard_test' => true,
                                'susdat_substance_id' => (int) $row->matched_susdat_substance_id,
                                'dtxid' => $this->toNullableString($row->matched_dtxid),
                                'substance_name' => $this->toNullableString($row->matched_name_dashboard ?: $row->matched_name),
                                'cas_no' => $this->toNullableString($row->matched_cas_number),
                                'inchikey' => $this->toNullableString($row->matched_inchikey),
                                'smiles' => $this->toNullableString($row->smiles ?: $row->matched_smiles),
                                'radio_labeled_substance' => null,
                                'standard_qualifier' => '=',
                                'standard_used' => null,
                                'test_matrix' => null,
                                'test_species' => null,
                                'duration_days' => null,
                                'exposure_concentration' => null,
                                'ph' => null,
                                'temperature_c' => null,
                                'total_organic_carbon' => null,
                                'original_parameter_name' => $parameter['name'],
                                'original_qualifier' => '=',
                                'original_value' => $parameter['original_value'],
                                'original_value_range' => null,
                                'original_unit' => $parameter['original_unit'],
                                'norman_parameter_name' => $parameter['name'],
                                'specific_parameter_name' => $parameter['specific_name'],
                                'assessment_qualifier' => '=',
                                'assessment_class' => $parameter['assessment_class'],
                                'value_assessment_index' => $parameter['assessment_value'],
                                'value_standardised_score' => $parameter['score'],
                                'unit' => $parameter['assessment_unit'],
                                'general_comment' => 'JANUS data source',
                                'applicability_domain' => null,
                                'applicability_domain_score' => null,
                                'reliability_score' => $parameter['reliability'],
                                'reliability_score_system' => 'JANUS',
                                'reliability_rational' => $this->toNullableString($row->remarks),
                                'institution_of_reliability_score' => 'German Environment Agency',
                                'regulatory_purpose' => 'Prioritize and screen substances, considering PBT (persistent, bioaccumulative and toxic), CMR (carcinogenic, mutagenic and reprotoxic) substances, as well as chemicals supposed to be endocrine disruptors.',
                                'use_of_study' => 'Yes',
                            ]
                        );

                        $this->tallyAction($summary, $action, 'janus');
                    }
                }
            });
    }

    private function fillPikmeData(array &$summary, ?int $editorUserId, $detailsByDtxid): void
    {
        $allowedDtxids = $detailsByDtxid->keys()->filter(static fn ($value) => ! empty($value))->values();

        if ($allowedDtxids->isEmpty()) {
            return;
        }

        $summary['total_pikme_records'] = DB::table('hazards_pikme')
            ->whereIn('dtxid', $allowedDtxids)
            ->count();

        DB::table('hazards_pikme')
            ->whereIn('dtxid', $allowedDtxids)
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($rows) use (&$summary, $editorUserId, $detailsByDtxid): void {
                foreach ($rows as $row) {
                    $detail = $detailsByDtxid->get((string) $row->dtxid);
                    if (! $detail) {
                        continue;
                    }

                    foreach ($this->getPikmeParameters($row) as $index => $parameter) {
                        $syntheticSourceRecordId = ((int) $row->id * 10) + $index + 1;

                        $action = $this->upsertSubstanceDataRow(
                            [
                                'source_record_type' => 'pikme',
                                'source_record_id' => $syntheticSourceRecordId,
                                'data_domain' => $this->resolveDataDomain($parameter['norman_parameter_name'], 'fate_transport'),
                            ],
                            [
                                'parse_run_id' => null,
                                'comptox_payload_id' => null,
                                'data_source' => 'PikMe',
                                'editor' => $editorUserId,
                                'date' => now()->toDateString(),
                                'reference_type' => 'database',
                                'title' => 'OPERA',
                                'authors' => null,
                                'year' => null,
                                'bibliographic_source' => null,
                                'physico_chemical_source_doi' => null,
                                'test_type' => '3',
                                'performed_under_glp' => false,
                                'standard_test' => false,
                                'susdat_substance_id' => $detail->susdat_substance_id,
                                'dtxid' => (string) $row->dtxid,
                                'substance_name' => $this->toNullableString($detail->preferred_name),
                                'cas_no' => $this->toNullableString($detail->casrn),
                                'inchikey' => $this->toNullableString($detail->inchikey),
                                'smiles' => $this->toNullableString($detail->smiles),
                                'radio_labeled_substance' => null,
                                'standard_qualifier' => 'not applicable',
                                'standard_used' => 'OPERA 2.6',
                                'test_matrix' => $parameter['test_matrix'],
                                'test_species' => 'not applicable',
                                'duration_days' => null,
                                'exposure_concentration' => null,
                                'ph' => null,
                                'temperature_c' => null,
                                'total_organic_carbon' => null,
                                'original_parameter_name' => $parameter['original_parameter_name'],
                                'original_qualifier' => 'not reported',
                                'original_value' => $parameter['original_value'],
                                'original_value_range' => $parameter['original_value_range'],
                                'original_unit' => 'log unit',
                                'norman_parameter_name' => $parameter['norman_parameter_name'],
                                'specific_parameter_name' => null,
                                'assessment_qualifier' => 'not reported',
                                'assessment_class' => $parameter['assessment_class'],
                                'value_assessment_index' => $parameter['value_assessment_index'],
                                'value_standardised_score' => $parameter['value_assessment_index'],
                                'unit' => $parameter['unit'],
                                'general_comment' => 'PIKME data',
                                'applicability_domain' => $parameter['applicability_domain'],
                                'applicability_domain_score' => $parameter['applicability_domain_score'],
                                'reliability_score' => $parameter['reliability_score'],
                                'reliability_score_system' => 'OPERA 2.6',
                                'reliability_rational' => null,
                                'institution_of_reliability_score' => null,
                                'regulatory_purpose' => 'not applicable',
                                'use_of_study' => 'Yes',
                            ]
                        );

                        $this->tallyAction($summary, $action, 'pikme');
                    }
                }
            });
    }

    private function tallyAction(array &$summary, string $action, string $type): void
    {
        if ($action === 'inserted') {
            $summary["inserted_{$type}_records"]++;
            $summary['inserted_records']++;
            return;
        }

        if ($action === 'updated') {
            $summary["updated_{$type}_records"]++;
            $summary['updated_records']++;
            return;
        }

        $summary["unchanged_{$type}_records"]++;
        $summary['unchanged_records']++;
    }

    private function upsertSubstanceDataRow(array $identity, array $data): string
    {
        $row = ComptoxSubstanceData::query()
            ->where('source_record_type', $identity['source_record_type'])
            ->where('source_record_id', $identity['source_record_id'])
            ->where('data_domain', $identity['data_domain'])
            ->first();

        if (! $row) {
            ComptoxSubstanceData::create($identity + $data);
            return 'inserted';
        }

        $row->fill($data);
        if (! $row->isDirty()) {
            return 'unchanged';
        }

        $row->save();
        return 'updated';
    }

    private function resolveTestType(?string $valueType): string
    {
        $normalized = strtolower(trim((string) $valueType));
        if (in_array($normalized, ['experimental', 'exp', '2'], true)) {
            return '2';
        }

        return '3';
    }

    private function resolveDataDomain(?string $parameterName, string $defaultDomain): string
    {
        if (in_array((string) $parameterName, self::FATE_TRANSPORT_PARAMETERS, true)) {
            return 'fate_transport';
        }

        return $defaultDomain;
    }

    private function resolveFateAssessmentClass(
        ?string $endpointName,
        ?float $value,
        bool $isExperimental,
        ?float $adScore,
        ?float $reliability
    ): ?string {
        if ($value === null) {
            return null;
        }

        if ($endpointName === 'Biodeg. Half-Life') {
            return $this->getDashboardPClass($value, $isExperimental, $adScore, $reliability);
        }

        if ($endpointName === 'Bioconcentration Factor') {
            return $this->getDashboardBClass($value, $isExperimental, $adScore, $reliability);
        }

        if ($endpointName === 'Soil Adsorp. Coeff. (Koc)') {
            return $this->getDashboardMClass($value, $isExperimental, $adScore, $reliability);
        }

        return null;
    }

    private function getDashboardPClass(float $halfLifeDays, bool $isExperimental, ?float $ad, ?float $rel): ?string
    {
        if ($isExperimental) {
            return null;
        }

        $inside = ((int) ($ad ?? 0)) === 1;
        $reliable = ($rel ?? 0.0) >= 0.6;

        if ($halfLifeDays > 180) {
            $base = 'vP';
        } elseif ($halfLifeDays > 120) {
            $base = 'P';
        } elseif ($halfLifeDays > 60) {
            $base = 'P';
        } elseif ($halfLifeDays > 20) {
            $base = 'sP';
        } else {
            $base = 'nP';
        }

        if ($inside && $reliable) {
            return $base;
        }

        if ($inside && ! $reliable) {
            if ($base === 'vP' || $base === 'P') {
                return 'sP';
            }
            if ($base === 'nP') {
                return 'probably-nP';
            }
            return 'sP';
        }

        if (! $inside && $reliable) {
            if ($base === 'nP') {
                return 'probably-nP';
            }
            return 'sP';
        }

        return null;
    }

    private function getDashboardBClass(float $bcfMax, bool $isExperimental, ?float $ad, ?float $rel): ?string
    {
        if ($isExperimental) {
            if ($bcfMax > 5000) {
                return 'vB';
            }
            if ($bcfMax > 2000 && $bcfMax <= 5000) {
                return 'B';
            }
            if ($bcfMax >= 500 && $bcfMax <= 2000) {
                return 'sB';
            }
            if ($bcfMax < 500) {
                return 'nB';
            }
            return null;
        }

        $inside = ((int) ($ad ?? 0)) === 1;
        $reliable = ($rel ?? 0.0) >= 0.6;

        if ($bcfMax > 5000 && $inside && $reliable) {
            return 'vB';
        }
        if ($bcfMax > 2000 && $bcfMax <= 5000 && $inside && $reliable) {
            return 'B';
        }
        if (
            ($bcfMax >= 500 && $bcfMax <= 2000 && $inside && $reliable)
            || ($bcfMax > 5000 && $inside && ! $reliable)
            || ($bcfMax > 2000 && $bcfMax <= 5000 && $inside && ! $reliable)
        ) {
            return 'sB';
        }
        if ($bcfMax < 500 && $inside && $reliable) {
            return 'nB';
        }
        if (
            ($bcfMax < 500 && $inside && ! $reliable)
            || ($bcfMax < 500 && ! $inside && $reliable)
        ) {
            return 'probably-nB';
        }
        if (! $inside && ! $reliable) {
            return 'probably-nB';
        }

        return null;
    }

    private function getDashboardMClass(float $kocMin, bool $isExperimental, ?float $ad, ?float $rel): ?string
    {
        if ($isExperimental) {
            if ($kocMin <= 100) {
                return 'vM';
            }
            if ($kocMin > 100 && $kocMin <= 1000) {
                return 'M';
            }
            if ($kocMin > 1000 && $kocMin <= 2000) {
                return 'sM';
            }
            if ($kocMin > 2000) {
                return 'nM';
            }
            return null;
        }

        $inside = ((int) ($ad ?? 0)) === 1;
        $reliable = ($rel ?? 0.0) >= 0.6;

        if ($kocMin <= 100 && $inside && $reliable) {
            return 'vM';
        }
        if ($kocMin > 100 && $kocMin <= 1000 && $inside && $reliable) {
            return 'M';
        }
        if ($kocMin > 1000 && $kocMin <= 2000 && $inside && $reliable) {
            return 'sM';
        }
        if ($kocMin > 2000 && $inside && $reliable) {
            return 'nM';
        }

        if (
            ($kocMin > 1000 && $kocMin <= 2000 && $inside && ! $reliable)
            || ($kocMin <= 100 && $inside && ! $reliable)
            || ($kocMin > 100 && $kocMin <= 1000 && $inside && ! $reliable)
        ) {
            return 'sM';
        }

        if (
            ($kocMin > 2000 && $inside && ! $reliable)
            || ($kocMin > 2000 && ! $inside && $reliable)
        ) {
            return 'probably-nM';
        }

        if (! $inside && ! $reliable) {
            return 'probably-nM';
        }

        return null;
    }

    private function getTestMatrix(?string $parameter): ?string
    {
        return match ((string) $parameter) {
            'Bioconcentration Factor' => 'fish',
            'Soil Adsorp. Coeff. (Koc)' => 'soil',
            'LogKoa: Octanol-Air' => 'air',
            'LogKow: Octanol-Water' => 'water',
            'Water Solubility' => 'water',
            default => null,
        };
    }

    private function getJanusParameters(object $row): array
    {
        return [
            [
                'name' => 'Persistency',
                'specific_name' => 'P',
                'original_value' => $this->toNullableFloat($row->p_assessment_index),
                'assessment_value' => $this->toNullableFloat($row->p_assessment_index),
                'score' => $this->toNullableFloat($row->p_score),
                'reliability' => $this->toNullableFloat($row->p_reliability),
                'assessment_class' => $this->getJanusPbmtRule(
                    'P',
                    $this->toNullableString($row->p_assessment_class),
                    $this->toNullableFloat($row->p_reliability)
                ),
                'original_unit' => null,
                'assessment_unit' => null,
            ],
            [
                'name' => 'Bioconcentration factor (BCF)',
                'specific_name' => 'B',
                'original_value' => $this->toNullableFloat($row->b_assessment_log_units),
                'assessment_value' => $this->toNullableFloat($row->b_assessment_log_units) !== null
                    ? pow(10, (float) $row->b_assessment_log_units)
                    : null,
                'score' => $this->toNullableFloat($row->b_score),
                'reliability' => $this->toNullableFloat($row->b_reliability),
                'assessment_class' => $this->getJanusPbmtRule(
                    'B',
                    $this->toNullableFloat($row->b_assessment_log_units),
                    $this->toNullableFloat($row->b_reliability)
                ),
                'original_unit' => 'log unit',
                'assessment_unit' => 'L/kg',
            ],
            [
                'name' => 'Toxicity',
                'specific_name' => 'T',
                'original_value' => $this->toNullableFloat($row->t_assessment_mg_l),
                'assessment_value' => $this->toNullableFloat($row->t_assessment_mg_l),
                'score' => $this->toNullableFloat($row->t_score),
                'reliability' => $this->toNullableFloat($row->t_reliability),
                'assessment_class' => $this->getJanusPbmtRule(
                    'T',
                    $this->toNullableFloat($row->t_assessment_mg_l),
                    $this->toNullableFloat($row->t_reliability)
                ),
                'original_unit' => 'mg/l',
                'assessment_unit' => 'mg/l',
            ],
            [
                'name' => 'Carcinogenicity',
                'specific_name' => 'C',
                'original_value' => null,
                'assessment_value' => null,
                'score' => $this->toNullableFloat($row->c_score),
                'reliability' => $this->toNullableFloat($row->c_reliability),
                'assessment_class' => $this->getJanusCmrEdRule('C', $this->toNullableString($row->c_assessment)),
                'original_unit' => null,
                'assessment_unit' => null,
            ],
            [
                'name' => 'Mutagenicity',
                'specific_name' => 'M',
                'original_value' => null,
                'assessment_value' => null,
                'score' => $this->toNullableFloat($row->m_score),
                'reliability' => $this->toNullableFloat($row->m_reliability),
                'assessment_class' => $this->getJanusCmrEdRule('M', $this->toNullableString($row->m_assessment)),
                'original_unit' => null,
                'assessment_unit' => null,
            ],
            [
                'name' => 'Reprotoxicity',
                'specific_name' => 'R',
                'original_value' => null,
                'assessment_value' => null,
                'score' => $this->toNullableFloat($row->r_score),
                'reliability' => $this->toNullableFloat($row->r_reliability),
                'assessment_class' => $this->getJanusCmrEdRule('R', $this->toNullableString($row->r_assessment)),
                'original_unit' => null,
                'assessment_unit' => null,
            ],
            [
                'name' => 'Endocrine Disruption',
                'specific_name' => 'ED',
                'original_value' => $this->toNullableFloat($row->ed_assessment_index),
                'assessment_value' => $this->toNullableFloat($row->ed_assessment_index),
                'score' => $this->toNullableFloat($row->ed_score),
                'reliability' => $this->toNullableFloat($row->ed_reliability),
                'assessment_class' => $this->getJanusCmrEdRule('ED', $this->toNullableString($row->ed_assessment_class)),
                'original_unit' => null,
                'assessment_unit' => null,
            ],
        ];
    }

    private function getPikmeParameters(object $row): array
    {
        $parameters = [];

        $kocLogValue = $this->toNullableFloat($row->logkoc_pred_opera);
        if ($kocLogValue !== null) {
            $kocValue = pow(10, $kocLogValue);
            $parameters[] = [
                'norman_parameter_name' => 'Soil Adsorp. Coeff. (Koc)',
                'original_parameter_name' => 'logkoc_pred_opera',
                'original_value' => $kocLogValue,
                'original_value_range' => $this->toNullableString($row->koc_predrange_opera),
                'value_assessment_index' => $kocValue,
                'unit' => 'L/kg',
                'test_matrix' => 'soil',
                'assessment_class' => $this->getDashboardMClass(
                    $kocValue,
                    false,
                    $this->toNullableFloat($row->ad_koc_opera),
                    $this->toNullableFloat($row->conf_index_koc_opera)
                ),
                'applicability_domain' => $this->toNullableString($row->ad_koc_opera),
                'applicability_domain_score' => $this->toNullableFloat($row->ad_index_koc_opera),
                'reliability_score' => $this->toNullableFloat($row->conf_index_koc_opera),
            ];
        }

        $bcfLogValue = $this->toNullableFloat($row->logbcf_pred_opera);
        if ($bcfLogValue !== null) {
            $bcfValue = pow(10, $bcfLogValue);
            $parameters[] = [
                'norman_parameter_name' => 'Bioconcentration Factor',
                'original_parameter_name' => 'logbcf_pred_opera',
                'original_value' => $bcfLogValue,
                'original_value_range' => $this->toNullableString($row->bcf_predrange_opera),
                'value_assessment_index' => $bcfValue,
                'unit' => 'L/kg',
                'test_matrix' => 'fish',
                'assessment_class' => $this->getDashboardBClass(
                    $bcfValue,
                    false,
                    $this->toNullableFloat($row->ad_bcf_opera),
                    $this->toNullableFloat($row->conf_index_bcf_opera)
                ),
                'applicability_domain' => $this->toNullableString($row->ad_bcf_opera),
                'applicability_domain_score' => $this->toNullableFloat($row->ad_index_bcf_opera),
                'reliability_score' => $this->toNullableFloat($row->conf_index_bcf_opera),
            ];
        }

        $halfLifeLogValue = $this->toNullableFloat($row->biodeg_loghalflife_pred_opera)
            ?? $this->toNullableFloat($row->loghl_pred_opera);

        if ($halfLifeLogValue !== null) {
            $halfLifeDays = pow(10, $halfLifeLogValue);
            $parameters[] = [
                'norman_parameter_name' => 'Biodeg. Half-Life',
                'original_parameter_name' => $this->toNullableFloat($row->biodeg_loghalflife_pred_opera) !== null
                    ? 'biodeg_loghalflife_pred_opera'
                    : 'loghl_pred_opera',
                'original_value' => $halfLifeLogValue,
                'original_value_range' => $this->toNullableString($row->biodeg_predrange_opera)
                    ?? $this->toNullableString($row->hl_predrange_opera),
                'value_assessment_index' => $halfLifeDays,
                'unit' => 'days',
                'test_matrix' => 'water',
                'assessment_class' => $this->getDashboardPClass(
                    $halfLifeDays,
                    false,
                    $this->toNullableFloat($row->ad_biodeg_opera) ?? $this->toNullableFloat($row->ad_hl_opera),
                    $this->toNullableFloat($row->conf_index_biodeg_opera) ?? $this->toNullableFloat($row->conf_index_hl_opera)
                ),
                'applicability_domain' => $this->toNullableString($row->ad_biodeg_opera)
                    ?? $this->toNullableString($row->ad_hl_opera),
                'applicability_domain_score' => $this->toNullableFloat($row->ad_index_biodeg_opera),
                'reliability_score' => $this->toNullableFloat($row->conf_index_biodeg_opera)
                    ?? $this->toNullableFloat($row->conf_index_hl_opera),
            ];
        }

        return $parameters;
    }

    private function getJanusPbmtRule(string $type, string|float|null $value, ?float $reliability): ?string
    {
        if ($value === null || $reliability === null) {
            return null;
        }

        if ($type === 'P') {
            if ($value === 'vP' && $reliability >= 0.7) {
                return 'vP';
            }
            if ($value === 'P' && $reliability >= 0.7) {
                return 'P';
            }
            if (in_array($value, ['P', 'vP'], true) && $reliability < 0.7) {
                return 'sP';
            }
            if ($value === 'nP' && $reliability >= 0.7) {
                return 'nP';
            }
            if ($value === 'nP' && $reliability < 0.7) {
                return 'probably-nP';
            }

            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $numericValue = (float) $value;

        if ($type === 'B') {
            if ($numericValue > 3.7 && $reliability >= 0.7) {
                return 'vB';
            }
            if ($numericValue > 3.3 && $numericValue <= 3.7 && $reliability >= 0.7) {
                return 'B';
            }
            if (
                ($numericValue > 3.3 && $reliability < 0.7)
                || ($numericValue > 2.7 && $numericValue <= 3.3 && $reliability >= 0.7)
            ) {
                return 'sB';
            }
            if ($numericValue <= 2.7 && $reliability >= 0.7) {
                return 'nB';
            }
            if ($numericValue <= 2.7 && $reliability < 0.7) {
                return 'probably-nB';
            }

            return null;
        }

        if ($type === 'T') {
            if ($numericValue < 0.01 && $reliability >= 0.7) {
                return 'T+';
            }
            if ($numericValue >= 0.01 && $numericValue < 0.1 && $reliability >= 0.7) {
                return 'T';
            }
            if (
                ($numericValue < 0.1 && $reliability < 0.7)
                || ($numericValue >= 0.1 && $numericValue < 10 && $reliability >= 0.7)
            ) {
                return 'sT';
            }
            if ($numericValue >= 10 && $reliability >= 0.7) {
                return 'nT';
            }
            if ($numericValue >= 10 && $reliability < 0.7) {
                return 'probably-nT';
            }
        }

        return null;
    }

    private function getJanusCmrEdRule(string $type, ?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $upper = strtoupper($text);

        if ($type === 'C') {
            if (str_contains($upper, 'NON CARCINOGENIC')) {
                return 'nC';
            }
            if (str_contains($upper, 'CARCINOGENIC')) {
                return 'C';
            }

            return null;
        }

        if ($type === 'M') {
            if (str_contains($upper, 'NON MUTAGENIC')) {
                return 'nM';
            }
            if (str_contains($upper, 'MUTAGENIC')) {
                return 'M';
            }

            return null;
        }

        if ($type === 'R') {
            if (str_contains($upper, 'NON TOXICANT')) {
                return 'nR';
            }
            if (str_contains($upper, 'TOXICANT')) {
                return 'R';
            }

            return null;
        }

        if ($type === 'ED') {
            if (str_contains($upper, 'INACTIVE')) {
                return 'nED';
            }
            if (str_contains($upper, 'ACTIVE')) {
                return 'ED';
            }
        }

        return null;
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace(',', '.', $value);
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return null;
    }
}
