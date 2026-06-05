<?php

declare(strict_types=1);

namespace App\Services\EmpodatSuspect;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a raw `empodat_suspect_matrix_*` materialized-view row into an ordered
 * list of display rows for the EmpodatSuspect detail view.
 *
 * Responsibilities:
 *   - resolve legacy integer FK columns (`*_id`) to their human label via the
 *     matching PG `list_*` lookup table;
 *   - append measurement units (extracted from the legacy column comments);
 *   - hide internal columns, null/empty values and unresolved "0" ids.
 *
 * The configuration is per-matrix. Only `biota` is fully mapped today; the other
 * eight matrix types fall through to a humanised-label / raw-value rendering
 * until their legacy lookup counterparts are available — add a `self::FIELDS`
 * entry to enrich them, no other change required.
 *
 * Literature-module guard (these collide by name — do NOT cross them):
 *   - `dspc_id`  -> `list_biota_species`  (NOT `list_species`, which is Literature)
 *   - `dmeas_id` -> `list_measurements`   (NOT `list_basis_of_measurements`, empty)
 */
class MatrixMetadataLabeller
{
    /**
     * Per-matrix field configuration.
     *
     * Each column may define:
     *   - `label`  : display label (defaults to the humanised column name)
     *   - `lookup` : `list_*` table whose `name` resolves the column's integer id
     *   - `unit`   : unit appended to the value (from the legacy column comment)
     *
     * @var array<string, array<string, array{label?: string, lookup?: string, unit?: string}>>
     */
    private const FIELDS = [
        'biota' => [
            // --- legacy FK columns -> PG list_* lookups -------------------------
            'dpr_id' => ['label' => 'Proxy pressure', 'lookup' => 'list_proxy_pressures'],
            'dsgr_id' => ['label' => 'Species group', 'lookup' => 'list_species_groups'],
            'dmeas_id' => ['label' => 'Basis of measurement', 'lookup' => 'list_measurements'],
            'dtiel_id' => ['label' => 'Tissue element', 'lookup' => 'list_tissues'],
            'dki_id' => ['label' => 'Kingdom', 'lookup' => 'list_kingdoms'],
            'dph_id' => ['label' => 'Phylum', 'lookup' => 'list_phyla'],
            'dcla_id' => ['label' => 'Class', 'lookup' => 'list_classes'],
            'dspc_id' => ['label' => 'Species (common name)', 'lookup' => 'list_biota_species'],
            'diop_id' => ['label' => 'Individual or pooled', 'lookup' => 'list_individual_or_pooled'],
            'dcat_id' => ['label' => 'Category', 'lookup' => 'list_categories'],
            'dht_id' => ['label' => 'Habitat type', 'lookup' => 'list_habitat_types'],

            // --- legacy FK columns WITHOUT a PG lookup table (no dump available) -
            // Rendered as raw integers until `list_orders` / `list_families` exist.
            'dord_id' => ['label' => 'Order'],
            'dfam_id' => ['label' => 'Family'],

            // --- free-text fields: friendlier labels + units from legacy dump ---
            'species_name' => ['label' => 'Species name (Latin)'],
            'biota_size' => ['label' => 'Biota size', 'unit' => 'mm'],
            'biota_length' => ['label' => 'Biota length', 'unit' => 'mm'],
            'biota_weight' => ['label' => 'Biota weight', 'unit' => 'g'],
            'biota_weight_dry' => ['label' => 'Biota weight (dry)', 'unit' => 'g'],
            'water_content' => ['label' => 'Water content of tissue', 'unit' => '%'],
            'fat_content' => ['label' => 'Fat content of tissue', 'unit' => '%'],
            'dry_wet' => ['label' => 'Dry/Wet ratio', 'unit' => 'weight %'],
            'bill_length' => ['label' => 'Bill length', 'unit' => 'cm'],
            'wing_length' => ['label' => 'Wing length', 'unit' => 'cm'],
            'tarsus_length' => ['label' => 'Tarsus length', 'unit' => 'mm'],
            'tarsus_width' => ['label' => 'Tarsus width', 'unit' => 'mm'],
            'km' => ['label' => 'River-km', 'unit' => 'km'],
        ],
    ];

    /**
     * Columns that are plumbing, never shown to the user.
     *
     * @var list<string>
     */
    private const HIDDEN = ['id', 'station_id', 'empodat_main_id'];

    /**
     * Build the ordered display rows for one matrix MV row.
     *
     * @param  object|array<string, mixed>  $row
     * @return list<array{label: string, value: string}>
     */
    public function label(string $matrixType, object|array $row): array
    {
        $row = (array) $row;
        $fields = self::FIELDS[$matrixType] ?? [];
        $resolved = $this->resolveLookups($row, $fields);

        $output = [];

        foreach ($row as $column => $value) {
            if (in_array($column, self::HIDDEN, true)) {
                continue;
            }

            $config = $fields[$column] ?? [];

            if (isset($config['lookup'])) {
                $name = $resolved[$column] ?? null;
                if ($name === null || $name === '') {
                    continue;
                }

                $output[] = [
                    'label' => $config['label'] ?? $this->humanise($column),
                    'value' => $name,
                ];

                continue;
            }

            if ($this->isBlank($value)) {
                continue;
            }

            $text = trim((string) $value);
            if (isset($config['unit'])) {
                $text .= ' '.$config['unit'];
            }

            $output[] = [
                'label' => $config['label'] ?? $this->humanise($column),
                'value' => $text,
            ];
        }

        return $output;
    }

    /**
     * Resolve every lookup column present in the row to its `name`.
     *
     * One small primary-key query per referenced `list_*` table; ids of 0 / null
     * (legacy "not specified") are skipped and never queried.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, array{label?: string, lookup?: string, unit?: string}>  $fields
     * @return array<string, string> column => resolved name
     */
    private function resolveLookups(array $row, array $fields): array
    {
        $resolved = [];

        foreach ($fields as $column => $config) {
            if (! isset($config['lookup'])) {
                continue;
            }

            $id = $this->intOrNull($row[$column] ?? null);
            if ($id === null || $id === 0) {
                continue;
            }

            $name = DB::table($config['lookup'])->where('id', $id)->value('name');
            if ($name !== null) {
                $resolved[$column] = (string) $name;
            }
        }

        return $resolved;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function humanise(string $column): string
    {
        return Str::of($column)->replace('_', ' ')->title()->toString();
    }
}
