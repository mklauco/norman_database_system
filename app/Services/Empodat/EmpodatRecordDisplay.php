<?php

declare(strict_types=1);

namespace App\Services\Empodat;

use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Builds the human-readable field lists rendered by the EMPODAT
 * search-result record modal (issue #22).
 *
 * Three rules, applied to every section:
 *
 *  1. Codelist columns (`x_id`) are replaced by the name from their
 *     `list_*` table. Ids that resolve to nothing are hidden, as in legacy.
 *  2. "Other" collapsing — when the resolved name is "Other" and the
 *     sibling `x_other` free-text column holds a usable value, the free
 *     text is shown *instead of* the word "Other", as a single row. The
 *     `x_other` column is never rendered as a row of its own.
 *  3. Labels come from `config/empodat_field_labels.php`; columns with no
 *     entry there keep their raw column name, which the frontend
 *     Title-Cases.
 *
 * Only the modal payload is affected. `EmpodatResource` and
 * `EmpodatCsvExportJob` read the underlying models directly and keep
 * seeing raw column values.
 */
class EmpodatRecordDisplay
{
    /**
     * Legacy v1 sentinels that mean "no value" rather than a real value.
     */
    /**
     * Codelist entries that are placeholders rather than answers. Legacy
     * prints the `*_other` free text in place of both: record 6762608 has
     * `dtiel_id` = 7 ("NR") with `dtiel_other` = "Liver", and legacy renders
     * "Tissue element of species monitored: Liver".
     */
    private const PLACEHOLDER_NAMES = ['other', 'nr'];

    private const ZERO_SENTINELS = [
        '0', '00', '000', '0000',
        '0000-00-00', '0000-00-00 00:00:00',
        '00:00', '00:00:00', '0:00', '0:00:00',
    ];

    /**
     * Build the "Analytical Method" section: (label => value) pairs.
     *
     * `$unit` is the matrix unit, appended to LOD/LOQ — legacy prints
     * those with the unit implied by the matrix, and without it the number
     * is meaningless (issue #22).
     *
     * @return array<string, mixed>
     */
    public function analyticalMethodFields(?Model $analyticalMethod, ?string $unit = null): array
    {
        if ($analyticalMethod === null) {
            return [];
        }

        $attributes = $this->stripEnvelope($analyticalMethod->getAttributes());

        unset($attributes['legacy_given_analyte_id'], $attributes['legacy_laboratory_participate_id']);

        if ($unit !== null && $unit !== '') {
            foreach (['lod', 'loq'] as $column) {
                if ($this->hasValue($attributes[$column] ?? null)) {
                    $attributes[$column] = $this->formatNumber($attributes[$column]).' '.$unit;
                }
            }
        }

        foreach (['uncertainty_loq', 'uncertainty_loq_range_min', 'uncertainty_loq_range_max'] as $column) {
            if ($this->hasValue($attributes[$column] ?? null)) {
                $attributes[$column] = $this->formatNumber($attributes[$column]).' %';
            }
        }

        $attributes['rating'] = self::describeRating($attributes['rating'] ?? null);

        return $this->build($attributes, $this->analyticalMethodLookups(), 'analytical_method');
    }

    /**
     * Build the "Station" section: (label => value) pairs.
     *
     * The country is rendered from the `country_relation` name; the raw
     * `country_id` / `country_other_id` FKs and the internal
     * `is_deprecated` flag are dropped. Latitude and longitude are rendered
     * separately by the modal (as degrees/minutes/seconds plus a map), so
     * they are excluded here.
     *
     * @return array<string, mixed>
     */
    public function stationFields(?Model $station): array
    {
        if ($station === null) {
            return [];
        }

        $attributes = $this->stripEnvelope($station->getAttributes());
        unset(
            $attributes['country_id'],
            $attributes['country_other_id'],
            $attributes['is_deprecated'],
            $attributes['latitude'],
            $attributes['longitude'],
        );

        $fields = $this->build($attributes, [], 'station');

        $country = $station->getRelationValue('countryRelation');

        if ($country !== null) {
            return ['Name of country' => $country->name] + $fields;
        }

        return $fields;
    }

    /**
     * Build the "Data Source" section: (label => value) pairs.
     *
     * @return array<string, mixed>
     */
    public function dataSourceFields(?Model $dataSource): array
    {
        if ($dataSource === null) {
            return [];
        }

        $attributes = $this->stripEnvelope($dataSource->getAttributes());

        $fields = $this->build($attributes, $this->dataSourceLookups(), 'data_source');

        return $this->withFullNames($fields, $dataSource);
    }

    /**
     * Build the "Additional Record Details" section from `empodat_minor`.
     *
     * @return array<string, mixed>
     */
    public function minorFields(?Model $minor): array
    {
        if ($minor === null) {
            return [];
        }

        $attributes = $this->stripEnvelope($minor->getAttributes());

        // Internal plumbing only. The date-part columns are kept: for the
        // majority of records the v1 import lost the day and month, so
        // `sampling_date_t` is the only time information that survives, and
        // the zero-sentinel filter already hides it when it is '0:00:00'.
        unset(
            $attributes['empodat_main_id'],
            $attributes['noexport'],
            $attributes['list_id'],
            $attributes['show_date'],
        );

        return $this->build($attributes, $this->minorLookups(), 'minor');
    }

    /**
     * Build the "Matrix Metadata" section for one `empodat_matrix_*` table.
     *
     * @param  array<string, mixed>  $metaData
     * @return array<string, mixed>
     */
    public function matrixFields(array $metaData, string $matrixType): array
    {
        return $this->build($metaData, $this->matrixLookups($matrixType), 'matrix');
    }

    /**
     * Normalise a `list_matrices.unit` value for display as plain text.
     *
     * The column stores HTML ("µg/m<sup>3</sup>"), which the modal and the
     * results table both render escaped — so the markup shows up verbatim.
     */
    public static function plainUnit(?string $unit): ?string
    {
        if ($unit === null || $unit === '') {
            return $unit;
        }

        $superscripts = ['0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴',
            '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹'];

        $unit = preg_replace_callback(
            '#<sup>(.*?)</sup>#i',
            static fn (array $m): string => strtr($m[1], $superscripts),
            $unit,
        ) ?? $unit;

        return trim(strip_tags($unit));
    }

    /**
     * Apply the three display rules to one flat column => value map.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, ?string>  $lookups  column => `list_*` table (null: no table available)
     * @return array<string, mixed>
     */
    private function build(array $attributes, array $lookups, string $labelGroup): array
    {
        $names = $this->resolveNames($attributes, $lookups);

        // Columns consumed by the "Other" rule must not also appear as rows.
        $otherColumns = [];
        foreach (array_keys($lookups) as $column) {
            $otherColumns[$this->otherColumnFor($column)] = true;
        }

        $fields = [];
        foreach ($attributes as $column => $value) {
            if (isset($otherColumns[$column])) {
                continue;
            }

            if (array_key_exists($column, $lookups)) {
                $value = $this->codelistValue($column, $value, $attributes, $lookups, $names);
            }

            if (! $this->hasValue($value)) {
                continue;
            }

            $fields[$this->label($labelGroup, $column)] = $this->trimMidnight($value);
        }

        return $fields;
    }

    /**
     * Resolve one codelist column to the value to display, applying the
     * "Other" rule. Returns null when the row should be hidden.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, ?string>  $lookups
     * @param  array<string, array<int, string>>  $names
     */
    private function codelistValue(
        string $column,
        mixed $value,
        array $attributes,
        array $lookups,
        array $names,
    ): ?string {
        $freeText = $this->usableFreeText($attributes[$this->otherColumnFor($column)] ?? null, $value);
        $table = $lookups[$column];

        // Either there is no `list_*` table for this column (the legacy
        // codelist was never imported), or the row carries no codelist id at
        // all. Legacy still prints the free text in both cases — record
        // 5314 has no analytical_method_id and renders "Analytical method:
        // GC-AED (atomic emission detection)" from the free text alone.
        if ($table === null || ! $this->isUsableId($value)) {
            return $freeText;
        }

        $name = $names[$table][(int) $value] ?? null;

        if ($name === null) {
            return $freeText;
        }

        if ($freeText !== null && in_array(strtolower(trim($name)), self::PLACEHOLDER_NAMES, true)) {
            return $freeText;
        }

        return $name;
    }

    /**
     * Batch-load (id => name) for every `list_*` table referenced by the
     * attributes: one query per table, not one per column.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, ?string>  $lookups
     * @return array<string, array<int, string>>
     */
    private function resolveNames(array $attributes, array $lookups): array
    {
        $idsByTable = [];
        foreach ($lookups as $column => $table) {
            if ($table === null || ! $this->isUsableId($attributes[$column] ?? null)) {
                continue;
            }
            $idsByTable[$table][] = (int) $attributes[$column];
        }

        $names = [];
        foreach ($idsByTable as $table => $ids) {
            try {
                $names[$table] = DB::table($table)
                    ->whereIn('id', array_values(array_unique($ids)))
                    ->pluck('name', 'id')
                    ->all();
            } catch (\Throwable $e) {
                // A `list_*` table missing on a partially migrated
                // environment must degrade to "field hidden", not a 500.
                Log::warning('EmpodatRecordDisplay: lookup table query failed', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $names;
    }

    /**
     * Laboratories and organisations are shown by their full name — name,
     * city and country — which lives in the models' `full_name` accessor,
     * not in the plain `name` column. Legacy does the same ("Fraunhofer
     * IME, Schmallenberg, Germany").
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function withFullNames(array $fields, Model $dataSource): array
    {
        $sources = [
            DataSourceLaboratory::class => ['laboratory1_id', 'laboratory2_id'],
            DataSourceOrganisation::class => ['organisation_id'],
        ];

        foreach ($sources as $modelClass => $columns) {
            $ids = [];
            foreach ($columns as $column) {
                $id = $dataSource->getAttribute($column);
                if ($this->isUsableId($id)) {
                    $ids[$column] = (int) $id;
                }
            }

            if ($ids === []) {
                continue;
            }

            $rows = $modelClass::with('country')
                ->whereIn('id', array_values(array_unique($ids)))
                ->get()
                ->keyBy('id');

            foreach ($ids as $column => $id) {
                $label = $this->label('data_source', $column);
                $row = $rows->get($id);

                if ($row === null) {
                    unset($fields[$label]);

                    continue;
                }

                $fields[$label] = $row->full_name;
            }
        }

        return $fields;
    }

    /**
     * The free-text sibling of a codelist column: `x_id` => `x_other`.
     */
    private function otherColumnFor(string $column): string
    {
        return preg_replace('/_id$/', '', $column).'_other';
    }

    /**
     * A `*_other` value is only usable when it is real free text.
     *
     * The v1 import left `sample_preparation_method_other` holding a copy of
     * the codelist id ("16") on ~8 800 rows — printing that instead of
     * "Other" is exactly the defect reported in issue #22. Rejecting the
     * value only when it equals the paired id keeps a genuinely numeric
     * answer (someone typing "3") intact.
     */
    private function usableFreeText(mixed $value, mixed $id = null): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || in_array($value, self::ZERO_SENTINELS, true)) {
            return null;
        }

        if ($id !== null && $id !== '' && (string) $id === $value) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function stripEnvelope(array $attributes): array
    {
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

        return $attributes;
    }

    /**
     * Whether a codelist FK column carries a usable id. Legacy writes 0 for
     * "nothing selected" throughout these tables.
     */
    private function isUsableId(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== 0 && $value !== '0';
    }

    /**
     * Whether a value is worth rendering.
     *
     * Note the asymmetry with isUsableId(): a *float* zero is kept, because
     * `lod` / `loq` and the matrix measurements are double-precision columns
     * where 0 is a measurement, not an absent value — 2 185 rows have
     * `lod = 0`. Integer zero and the legacy string sentinels ('0', '0000',
     * '0000-00-00 00:00:00', '0:00:00') stay hidden: those columns are
     * legacy flags and dates where 0 means "not reported".
     */
    private function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === 0) {
            return false;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || in_array($trimmed, self::ZERO_SENTINELS, true)) {
                return false;
            }

            // Carbon serialisation of the legacy zero-datetime.
            return preg_match('/^-0+1-11-30/', $trimmed) !== 1;
        }

        return true;
    }

    /**
     * Drop the ' 00:00:00' tail the legacy datetime columns carry when only
     * a date was recorded.
     */
    private function trimMidnight(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^(\d{4}-\d{2}-\d{2}) 00:00:00$/', trim($value), $m) === 1) {
            return $m[1];
        }

        return $value;
    }

    private function label(string $group, string $column): string
    {
        return config('empodat_field_labels.'.$group.'.'.$column, $column);
    }

    /**
     * Trim the float artefacts off LOD/LOQ before the unit is appended.
     */
    private function formatNumber(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }

    /**
     * The analytical-method rating is stored as a 0-100 score. Legacy
     * renders it as "20 (Not supported by quality-related information)".
     */
    public static function describeRating(mixed $rating): ?string
    {
        if (! is_numeric($rating)) {
            return is_string($rating) && $rating !== '' ? $rating : null;
        }

        $rating = (int) $rating;

        $band = match (true) {
            $rating >= 68 => 'Adequately supported by quality-related information',
            $rating >= 52 => 'Supported by limited quality-related information',
            $rating >= 22 => 'Minimal quality-related information',
            $rating >= 0 => 'Not supported by quality-related information',
            default => 'Rating value out of range',
        };

        return $rating.' ('.$band.')';
    }

    /**
     * @return array<string, ?string>
     */
    private function analyticalMethodLookups(): array
    {
        return [
            'coverage_factor_id' => 'list_coverage_factors',
            'sample_preparation_method_id' => 'list_sample_preparation_methods',
            'analytical_method_id' => 'list_analytical_methods',
            'standardised_method_id' => 'list_standardised_methods',
            'validated_method_id' => 'list_validated_methods',
            'corrected_recovery_id' => 'list_yes_no_questions',
            'field_blank_id' => 'list_yes_no_questions',
            'iso_id' => 'list_yes_no_questions',
            'given_analyte_id' => 'list_yes_no_questions',
            'laboratory_participate_id' => 'list_yes_no_questions',
            'summary_performance_id' => 'list_summary_performances',
            'control_charts_id' => 'list_yes_no_questions',
            'internal_standards_id' => 'list_yes_no_questions',
            'authority_id' => 'list_yes_no_questions',
            'sampling_method_id' => 'list_sampling_methods',
            'sampling_collection_device_id' => 'list_sampling_collection_devices',
        ];
    }

    /**
     * @return array<string, ?string>
     */
    private function dataSourceLookups(): array
    {
        return [
            'type_data_source_id' => 'list_type_data_sources',
            'type_monitoring_id' => 'list_type_monitorings',
            'data_accessibility_id' => 'list_data_accessibilities',
            // organisation_id / laboratory1_id / laboratory2_id are
            // deliberately absent: withFullNames() resolves them through
            // their models, whose full_name accessors add the city and
            // country.
        ];
    }

    /**
     * `empodat_minor` codelists. `null` marks a legacy codelist with no PG
     * counterpart: the id is hidden, but a `*_other` free text is still
     * shown when present.
     *
     * @return array<string, ?string>
     */
    private function minorLookups(): array
    {
        return [
            'dpc_id' => 'list_coordinate_precisions',
            'dcod_id' => 'list_concentration_data',
            'dst_id' => 'list_sampling_techniques',
            'dplu_id' => 'list_prevalent_land_uses',
            'dtl_id' => 'list_treatment_less',
            'dtod_id' => null,
            'dtos_id' => null,
            'dmm_id' => null,
        ];
    }

    /**
     * Per-matrix-type codelists.
     *
     * The `df_id` / `de_id` / `dcat_id` / `dpr_id` / `dtbu_id` columns are
     * the same shared codelists wherever they appear, so the water,
     * suspended-matter and sewage-sludge tables reuse the mappings already
     * verified for sediments and soil.
     *
     * `empodat_matrix_air` is deliberately absent: its `dloca_id` /
     * `dsmo_id` / `dscd_id` values fall outside the id ranges of any
     * `list_*` table in PostgreSQL, so the legacy codelists behind them
     * were never imported and any mapping would be a guess.
     *
     * @return array<string, ?string>
     */
    private function matrixLookups(string $matrixType): array
    {
        $shared = [
            'df_id' => 'list_fractions',
            'de_id' => 'list_depths',
            'dcat_id' => 'list_categories',
            'dpr_id' => 'list_proxy_pressures',
            'dtbu_id' => null,
        ];

        return match (strtolower($matrixType)) {
            'biota' => [
                'dki_id' => 'list_kingdoms',
                'dph_id' => 'list_phyla',
                'dcla_id' => 'list_classes',
                'dord_id' => null,
                'dfam_id' => null,
                'dspc_id' => 'list_biota_species',
                'diop_id' => 'list_individual_or_pooled',
                'dcat_id' => 'list_categories',
                'dht_id' => null,
                'dmeas_id' => 'list_measurements',
                'dtiel_id' => 'list_tissues',
                'dpr_id' => 'list_proxy_pressures',
                'dsgr_id' => 'list_species_groups',
            ],
            'soil' => $shared + [
                'dps_id' => 'list_particle_sizes',
                'dgra_id' => 'list_grain_size_distributions',
                'dsot_id' => 'list_soil_textures',
                'dcnps_id' => 'list_conc_normal_particle_sizes',
            ],
            'sediments', 'suspended_matter', 'sewage_sludge',
            'water_surface', 'water_ground' => $shared,
            'water_waste' => $shared + [
                'effluent_influent_id' => 'list_effluent_influents',
            ],
            default => [],
        };
    }
}
