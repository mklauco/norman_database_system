<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * READ-ONLY pre-flight inspection of an EMPODAT Suspect source spreadsheet,
 * run BEFORE anything is deleted or imported.
 *
 * WHY
 * ---
 * `empodat_suspect_main` stores neither the raw cell text nor the NORMAN_ID,
 * and a station column is bound to a station SOLELY by its heading. Once a
 * file is imported, a renamed heading that no longer resolves, a decimal
 * comma, or a lowercase "n/a" are all indistinguishable from legitimate data:
 * they land as rows with `concentration = NULL` (and possibly
 * `station_id = NULL`), with the original text discarded. This command is the
 * only point at which those problems are still visible.
 *
 * WHAT IT REPORTS
 * ---------------
 *   1. Cell classification over every station column, in the exact buckets the
 *      importer's `cleanDouble()` collapses: empty (no row written), numeric,
 *      exact "NA", decimal-comma candidates, whitespace-only, and any other
 *      non-numeric token — the last two listed verbatim with counts.
 *   2. The PREDICTED `empodat_suspect_main` row count, compared against the
 *      live `files.number_of_records` baseline. This is the go/no-go number
 *      for an id-range-preserving re-import.
 *   3. Heading drift: which headings are new, which have disappeared, and —
 *      for every heading — the station it resolves to under the SAME rules the
 *      XlsxStationsMappingFill seeders apply, including headings that resolve
 *      to nothing and headings that match more than one station.
 *   4. Substance codes that will not resolve to a `susdat_substances` row.
 *
 * It writes nothing to the database and nothing to the source file. The only
 * artifact is a JSON report under storage/logs/.
 *
 * USAGE
 *   php artisan empodat-suspect:preflight-xlsx "OK_CONNECT 2 ... v2.xlsx" --file=10003
 *
 * A bare filename is resolved inside storage/app/public/empodat_suspect/.
 */
class PreflightEmpodatSuspectXlsx extends Command
{
    protected $signature = 'empodat-suspect:preflight-xlsx
                            {path : Spreadsheet to inspect; a bare filename resolves inside storage/app/public/empodat_suspect/}
                            {--file= : files.id this spreadsheet will be imported as (enables the mapping, station and baseline checks)}
                            {--tokens=40 : Maximum distinct non-numeric tokens to list}';

    protected $description = 'Read-only pre-flight report for an EMPODAT Suspect source spreadsheet (cell classification, predicted row count, heading/station drift)';

    /**
     * Station columns begin after this header column.
     */
    private const string STATION_COLUMN_MARKER = 'Units';

    /**
     * First column of the HRMS identification metadata block in the newer
     * (NKUA-style) layout. Station columns end here — the same boundary the
     * newer Main seeders use (see EmpodatSuspectBlackSeaSedimentMainSeeder's
     * METADATA_BOUNDARY). Files in the legacy layout simply never contain it.
     */
    private const string METADATA_BOUNDARY = 'mz score';

    /**
     * Decimal comma with no thousands separator, e.g. "0,5" or "-12,75".
     * These are silently imported as NULL by cleanDouble().
     */
    private const string DECIMAL_COMMA_PATTERN = '/^-?\d+,\d+$/';

    /**
     * code (leading zeros stripped) => susdat_substances.id
     *
     * @var array<string, int>
     */
    private array $substanceCache = [];

    /**
     * Metadata-block columns found in this file, in header order.
     *
     * @var list<string>
     */
    private array $metadataColumns = [];

    public function handle(): int
    {
        $path = $this->resolvePath($this->argument('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $fileId = $this->option('file') !== null ? (int) $this->option('file') : null;

        $this->info('Pre-flight (READ-ONLY): '.basename($path));
        $this->info('Size: '.number_format((int) filesize($path)).' bytes');
        $this->newLine();

        $this->loadSubstanceCache();

        $scan = $this->scan($path);

        $this->reportCells($scan);
        $this->reportSubstances($scan);
        $headings = $this->reportHeadings($scan, $fileId);
        $this->reportRowCount($scan, $headings, $fileId);

        $reportPath = $this->writeReport($path, $fileId, $scan, $headings);
        $this->newLine();
        $this->info("Full report written to: {$reportPath}");

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_contains($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return storage_path('app/public/empodat_suspect/'.$path);
    }

    /**
     * Single streaming pass over the spreadsheet.
     *
     * @return array{
     *     source_rows: int,
     *     station_columns: list<string>,
     *     totals: array<string, int>,
     *     per_column: array<string, array<string, int>>,
     *     tokens: array<string, int>,
     *     norman_ids: array<string, int>,
     *     unresolved_codes: array<string, int>,
     *     rows_missing_norman_id: int
     * }
     */
    private function scan(string $path): array
    {
        $stationColumns = [];
        $sourceRows = 0;
        $rowsMissingNormanId = 0;
        $normanIds = [];
        $unresolvedCodes = [];
        $tokens = [];
        $perColumn = [];
        $totals = [
            'empty' => 0,
            'numeric' => 0,
            'na_exact' => 0,
            'decimal_comma' => 0,
            'whitespace_only' => 0,
            'other_non_numeric' => 0,
        ];

        $reader = SimpleExcelReader::create($path);

        foreach ($reader->getRows() as $row) {
            if ($stationColumns === []) {
                $stationColumns = $this->extractStationColumns(array_keys($row));

                if ($stationColumns === []) {
                    $this->error('No station columns found — is there a "'.self::STATION_COLUMN_MARKER.'" column in the header?');

                    break;
                }

                foreach ($stationColumns as $column) {
                    $perColumn[$column] = $totals;
                }
            }

            $sourceRows++;

            $normanId = trim((string) ($row['NORMAN_ID'] ?? ''));

            if ($normanId === '') {
                $rowsMissingNormanId++;
            } else {
                $normanIds[$normanId] = ($normanIds[$normanId] ?? 0) + 1;

                if ($this->resolveSubstanceId($normanId) === null) {
                    $unresolvedCodes[$normanId] = ($unresolvedCodes[$normanId] ?? 0) + 1;
                }
            }

            foreach ($stationColumns as $column) {
                $bucket = $this->classify($row[$column] ?? null, $tokens);
                $totals[$bucket]++;
                $perColumn[$column][$bucket]++;
            }

            if ($sourceRows % 5000 === 0) {
                $this->line("  ...{$sourceRows} source rows scanned");
            }
        }

        arsort($tokens);
        arsort($unresolvedCodes);

        return [
            'source_rows' => $sourceRows,
            'station_columns' => $stationColumns,
            'totals' => $totals,
            'per_column' => $perColumn,
            'tokens' => $tokens,
            'norman_ids' => $normanIds,
            'unresolved_codes' => $unresolvedCodes,
            'rows_missing_norman_id' => $rowsMissingNormanId,
        ];
    }

    /**
     * Header columns after "Units", cleaned exactly as the importers clean them
     * (BOM stripped, trimmed) so what is reported is what would be looked up.
     *
     * @param  list<string>  $header
     * @return list<string>
     */
    private function extractStationColumns(array $header): array
    {
        $columns = [];
        $collecting = false;
        $inMetadata = false;

        foreach ($header as $name) {
            $name = trim(str_replace("\xEF\xBB\xBF", '', (string) $name));

            if ($name === self::STATION_COLUMN_MARKER) {
                $collecting = true;

                continue;
            }

            if ($name === self::METADATA_BOUNDARY) {
                $inMetadata = true;
            }

            if ($name === '') {
                continue;
            }

            if ($inMetadata) {
                $this->metadataColumns[] = $name;

                continue;
            }

            if ($collecting) {
                $columns[] = $name;
            }
        }

        return $columns;
    }

    /**
     * Classify one cell into the bucket the importer would collapse it into.
     *
     * The ordering mirrors the importer: `null`/`''` are skipped outright and
     * write no row; everything else writes a row, numeric or not.
     *
     * @param  array<string, int>  $tokens  distinct non-numeric tokens, by reference
     */
    private function classify(mixed $value, array &$tokens): string
    {
        if ($value === null || $value === '') {
            return 'empty';
        }

        $raw = (string) $value;
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return 'whitespace_only';
        }

        if (is_numeric($trimmed)) {
            return 'numeric';
        }

        if ($trimmed === 'NA') {
            return 'na_exact';
        }

        if (preg_match(self::DECIMAL_COMMA_PATTERN, $trimmed) === 1) {
            $tokens[$trimmed] = ($tokens[$trimmed] ?? 0) + 1;

            return 'decimal_comma';
        }

        $tokens[$trimmed] = ($tokens[$trimmed] ?? 0) + 1;

        return 'other_non_numeric';
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function reportCells(array $scan): void
    {
        $totals = $scan['totals'];
        $cells = array_sum($totals);

        $this->newLine();
        $this->info('CELL CLASSIFICATION (station columns only)');
        $this->table(
            ['Bucket', 'Cells', 'Share', 'Writes a main row?'],
            [
                ['empty (null / "")', number_format($totals['empty']), $this->share($totals['empty'], $cells), 'no'],
                ['numeric', number_format($totals['numeric']), $this->share($totals['numeric'], $cells), 'yes — value stored'],
                ['exact "NA"', number_format($totals['na_exact']), $this->share($totals['na_exact'], $cells), 'yes — NULL'],
                ['decimal comma', number_format($totals['decimal_comma']), $this->share($totals['decimal_comma'], $cells), 'yes — NULL (VALUE LOST)'],
                ['whitespace only', number_format($totals['whitespace_only']), $this->share($totals['whitespace_only'], $cells), 'yes — NULL (phantom row)'],
                ['other non-numeric', number_format($totals['other_non_numeric']), $this->share($totals['other_non_numeric'], $cells), 'yes — NULL (text discarded)'],
            ]
        );
        $this->line('  Source rows: '.number_format($scan['source_rows'])
            .'   Station columns: '.count($scan['station_columns'])
            .'   Cells inspected: '.number_format($cells));

        if ($totals['decimal_comma'] > 0) {
            $this->warn('  WARNING: decimal-comma cells will import as NULL. Fix the source file before importing.');
        }

        if ($totals['whitespace_only'] > 0) {
            $this->warn('  WARNING: whitespace-only cells create rows with no value. They should be truly empty.');
        }

        if ($scan['tokens'] !== []) {
            $this->newLine();
            $this->info('DISTINCT NON-NUMERIC TOKENS (excluding exact "NA")');
            $limit = (int) $this->option('tokens');
            $rows = [];

            foreach (array_slice($scan['tokens'], 0, $limit, true) as $token => $count) {
                $rows[] = ['"'.$token.'"', number_format($count)];
            }

            $this->table(['Token', 'Cells'], $rows);

            if (count($scan['tokens']) > $limit) {
                $this->line('  ... '.(count($scan['tokens']) - $limit).' more distinct token(s); see the JSON report.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function reportSubstances(array $scan): void
    {
        $this->newLine();
        $this->info('SUBSTANCE CODES');
        $this->line('  Distinct NORMAN_ID values: '.number_format(count($scan['norman_ids'])));
        $this->line('  Rows with no NORMAN_ID (skipped by the importer): '.number_format($scan['rows_missing_norman_id']));

        $unresolved = $scan['unresolved_codes'];

        if ($unresolved === []) {
            $this->line('  All codes resolve to a susdat_substances row.');

            return;
        }

        $affectedRows = array_sum($unresolved);
        $this->warn('  '.count($unresolved).' code(s) do NOT resolve, affecting '
            .number_format($affectedRows).' source row(s) — these import with substance_id = NULL.');

        $rows = [];
        foreach (array_slice($unresolved, 0, 20, true) as $code => $count) {
            $rows[] = [$code, number_format($count)];
        }
        $this->table(['NORMAN_ID', 'Source rows'], $rows);
    }

    /**
     * Heading drift plus station resolution, using the same rules as the
     * XlsxStationsMappingFill seeders.
     *
     * @param  array<string, mixed>  $scan
     * @return array<string, array<string, mixed>>
     */
    private function reportHeadings(array $scan, ?int $fileId): array
    {
        $this->newLine();

        if ($this->metadataColumns !== []) {
            $this->info('HRMS METADATA BLOCK DETECTED ('.count($this->metadataColumns).' columns from "'
                .self::METADATA_BOUNDARY.'")');
            $this->line('  '.implode(', ', $this->metadataColumns));
            $this->warn('  This is the newer (NKUA-style) layout: these columns belong in empodat_suspect_metadata.');
            $this->newLine();
        }

        $this->info('STATION HEADINGS');

        $known = [];

        if ($fileId !== null) {
            $known = DB::table('empodat_suspect_xlsx_stations_mapping')
                ->where('file_id', $fileId)
                ->pluck('station_id', 'xlsx_name')
                ->all();
        }

        $headings = [];
        $rows = [];
        $unresolvable = 0;

        foreach ($scan['station_columns'] as $column) {
            $resolution = $this->resolveStation($column);
            $inMapping = array_key_exists($column, $known);

            $headings[$column] = [
                'in_mapping' => $inMapping,
                'mapped_station_id' => $inMapping ? $known[$column] : null,
                'resolves_to' => $resolution['station_id'],
                'matches' => $resolution['matches'],
                'pattern' => $resolution['pattern'],
                'cells_writing_rows' => array_sum($scan['per_column'][$column]) - $scan['per_column'][$column]['empty'],
            ];

            if ($resolution['station_id'] === null) {
                $unresolvable++;
            }

            $rows[] = [
                mb_strimwidth($column, 0, 68, '...'),
                $inMapping ? 'known' : 'NEW',
                $resolution['pattern'] ?? '-',
                $resolution['station_id'] ?? 'NONE',
                $resolution['matches'] > 1 ? 'AMBIGUOUS ('.$resolution['matches'].')' : '',
            ];
        }

        $this->table(['Heading', 'In mapping', 'Pattern', 'Resolves to station', 'Note'], $rows);

        if ($fileId !== null) {
            $disappeared = array_diff(array_keys($known), $scan['station_columns']);

            if ($disappeared !== []) {
                $this->warn('  '.count($disappeared).' heading(s) present in the mapping table but NOT in this file:');
                foreach ($disappeared as $name) {
                    $this->line('    - '.$name.'  (station_id='.($known[$name] ?? 'NULL').')');
                }
            }
        }

        if ($unresolvable > 0) {
            $this->error('  '.$unresolvable.' heading(s) resolve to NO station. Importing now would produce rows with station_id = NULL.');
        }

        return $headings;
    }

    /**
     * Resolve a heading to a station exactly as the XlsxStationsMappingFill
     * seeders do: extract the CONnECTII / DnieperII ordinal from the heading,
     * strip leading zeros, and match it case-insensitively against
     * empodat_stations.short_sample_code, ignoring deprecated stations.
     *
     * @return array{station_id: int|null, matches: int, pattern: string|null}
     */
    private function resolveStation(string $heading): array
    {
        $lower = mb_strtolower($heading);

        if (preg_match('/conn?ectii (\d+)/', $lower, $m) === 1) {
            return $this->matchStation('CONNECTII '.ltrim($m[1], '0'), 'CONnECTII');
        }

        if (preg_match('/dnieperii-(\d+)/', $lower, $m) === 1) {
            return $this->matchStation('DnieperII-'.ltrim($m[1], '0'), 'DnieperII');
        }

        return ['station_id' => null, 'matches' => 0, 'pattern' => null];
    }

    /**
     * @return array{station_id: int|null, matches: int, pattern: string}
     */
    private function matchStation(string $shortSampleCode, string $pattern): array
    {
        $ids = DB::table('empodat_stations')
            ->whereRaw('LOWER(short_sample_code) = LOWER(?)', [$shortSampleCode])
            ->where(function ($query): void {
                $query->whereNull('is_deprecated')->orWhere('is_deprecated', false);
            })
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return [
            'station_id' => $ids === [] ? null : (int) $ids[0],
            'matches' => count($ids),
            'pattern' => $pattern,
        ];
    }

    /**
     * The go/no-go number: how many empodat_suspect_main rows this file would
     * produce, against the live baseline.
     *
     * Headings absent from the mapping table are skipped by the importer, so
     * both the optimistic (every heading mapped) and the actual (only currently
     * mapped headings) counts are reported.
     *
     * @param  array<string, mixed>  $scan
     * @param  array<string, array<string, mixed>>  $headings
     */
    private function reportRowCount(array $scan, array $headings, ?int $fileId): void
    {
        $all = 0;
        $mappedOnly = 0;

        foreach ($headings as $data) {
            $all += $data['cells_writing_rows'];

            if ($data['in_mapping']) {
                $mappedOnly += $data['cells_writing_rows'];
            }
        }

        $this->newLine();
        $this->info('PREDICTED empodat_suspect_main ROWS');
        $this->line('  All headings mapped:            '.number_format($all));
        $this->line('  Only currently-mapped headings: '.number_format($mappedOnly));

        if ($fileId === null) {
            $this->line('  (pass --file= to compare against the live baseline)');

            return;
        }

        $baseline = DB::table('files')
            ->where('id', $fileId)
            ->first(['number_of_records', 'main_id_from', 'main_id_to']);

        if ($baseline === null) {
            $this->warn("  files row {$fileId} not found — no baseline to compare against.");

            return;
        }

        $expected = (int) $baseline->number_of_records;
        $range = (int) $baseline->main_id_to - (int) $baseline->main_id_from + 1;

        $this->line('  Baseline files.number_of_records: '.number_format($expected));
        $this->line('  Baseline id range: '.number_format((int) $baseline->main_id_from)
            .' - '.number_format((int) $baseline->main_id_to).' ('.number_format($range).' ids)');

        $delta = $all - $expected;

        if ($delta === 0) {
            $this->info('  MATCH — an id-range-preserving re-import fits exactly.');

            return;
        }

        if ($delta > 0) {
            $this->error('  OVER by '.number_format($delta).' row(s) — the id range CANNOT hold this import.');

            return;
        }

        $this->warn('  UNDER by '.number_format(abs($delta)).' row(s) — the import fits but leaves a gap at the end of the range.');
    }

    /**
     * @param  array<string, mixed>  $scan
     * @param  array<string, array<string, mixed>>  $headings
     */
    private function writeReport(string $path, ?int $fileId, array $scan, array $headings): string
    {
        $report = [
            'generated_at' => now()->toDateTimeString(),
            'source_file' => $path,
            'source_bytes' => (int) filesize($path),
            'file_id' => $fileId,
            'source_rows' => $scan['source_rows'],
            'rows_missing_norman_id' => $scan['rows_missing_norman_id'],
            'distinct_norman_ids' => count($scan['norman_ids']),
            'unresolved_codes' => $scan['unresolved_codes'],
            'cell_totals' => $scan['totals'],
            'cell_totals_per_column' => $scan['per_column'],
            'non_numeric_tokens' => $scan['tokens'],
            'metadata_columns' => $this->metadataColumns,
            'headings' => $headings,
        ];

        $name = 'empodat-suspect-preflight-'.($fileId ?? 'nofile').'-'.now()->format('Ymd-His').'.json';
        $target = storage_path('logs/'.$name);

        file_put_contents($target, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $target;
    }

    /**
     * Substance codes resolve through susdat_substances plus the legacy
     * crosswalk, with leading zeros stripped — identical to
     * LoadsSubstanceCaches, so a code reported here as unresolvable is exactly
     * one the importer would leave NULL.
     */
    private function loadSubstanceCache(): void
    {
        foreach (DB::table('susdat_substances')->whereNotNull('code')->select('id', 'code')->cursor() as $substance) {
            $this->substanceCache[$this->normalizeCode((string) $substance->code)] = (int) $substance->id;
        }

        foreach (DB::table('empodat_suspect_susdat_code_mappings')->select('old_code', 'new_code')->cursor() as $mapping) {
            $new = $this->normalizeCode((string) $mapping->new_code);

            if (isset($this->substanceCache[$new])) {
                $this->substanceCache[$this->normalizeCode((string) $mapping->old_code)] = $this->substanceCache[$new];
            }
        }

        $this->line('Loaded '.number_format(count($this->substanceCache)).' resolvable substance codes.');
    }

    private function resolveSubstanceId(string $normanId): ?int
    {
        $code = preg_replace('/^NS/', '', $normanId) ?? $normanId;

        return $this->substanceCache[$this->normalizeCode($code)] ?? null;
    }

    private function normalizeCode(string $code): string
    {
        return ltrim($code, '0') ?: '0';
    }

    private function share(int $part, int $total): string
    {
        return $total === 0 ? '-' : number_format($part / $total * 100, 2).'%';
    }
}
