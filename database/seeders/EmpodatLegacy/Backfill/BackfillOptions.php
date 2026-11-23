<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use InvalidArgumentException;

/**
 * Immutable, validated options of one backfill invocation. Built from
 * `config('empodat_legacy.backfill')`, which in turn reads the
 * `LEGACY_BACKFILL_*` environment variables.
 */
final readonly class BackfillOptions
{
    public const MODE_PREFLIGHT = 'preflight';

    public const MODE_DRY_RUN = 'dry-run';

    public const MODE_EXECUTE = 'execute';

    public const MODE_VERIFY = 'verify';

    public const MODES = [
        self::MODE_PREFLIGHT,
        self::MODE_DRY_RUN,
        self::MODE_EXECUTE,
        self::MODE_VERIFY,
    ];

    /**
     * Legacy columns that have no counterpart (or a differently named one) in
     * the legacy database. `null` means the PG column is filled with NULL.
     *
     * @var array<string, string|null>
     */
    public const DEFAULT_COLUMN_ALIASES = [
        'empodat_matrix_water_surface.ocean_sea_region_name' => null,
        'empodat_matrix_biota.packing_material' => null,
    ];

    /** Default number of missing ids per table a dry run reads and simulates. */
    public const DEFAULT_DRY_RUN_MAX_ROWS = 50_000;

    /** Default number of example rows per table printed by a dry run. */
    public const DEFAULT_SAMPLE_ROWS = 5;

    /**
     * @param  array<string, string|null>  $columnAliases  "pg_table.pg_column" => legacy column or null
     * @param  list<string>  $onlyTables  restrict matrix/minor tables (empty = all)
     * @param  ?int  $idFrom  first id of the window every mode is restricted to (inclusive), null = unbounded
     * @param  ?int  $idTo  last id of the window (inclusive), null = unbounded
     * @param  int  $dryRunMaxRows  missing ids per table a dry run reads and simulates, 0 = no limit
     * @param  int  $sampleRows  example rows per table printed by a dry run
     */
    public function __construct(
        public string $mode,
        public bool $executeFlag,
        public ?string $expectDatabase,
        public ?string $expectHost,
        public int $bucketSize,
        public int $chunkRows,
        public int $scanWindow,
        public int $legacyPauseMs,
        public bool $dryRunReadOnlyOk,
        public ?int $closeStaleRunId,
        public string $reportDirectory,
        public string $memoryLimit,
        public array $columnAliases,
        public array $onlyTables,
        public ?int $idFrom = null,
        public ?int $idTo = null,
        public int $dryRunMaxRows = self::DEFAULT_DRY_RUN_MAX_ROWS,
        public int $sampleRows = self::DEFAULT_SAMPLE_ROWS,
    ) {
        if (! in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException(
                'LEGACY_BACKFILL_MODE must be one of: '.implode(', ', self::MODES).' (got "'.$mode.'").'
            );
        }
        if (($idFrom !== null && $idFrom < 0) || ($idTo !== null && $idTo < 0)) {
            throw new InvalidArgumentException('LEGACY_BACKFILL_ID_FROM / LEGACY_BACKFILL_ID_TO must not be negative.');
        }
        if ($idFrom !== null && $idTo !== null && $idFrom > $idTo) {
            throw new InvalidArgumentException(sprintf(
                'LEGACY_BACKFILL_ID_FROM (%d) is greater than LEGACY_BACKFILL_ID_TO (%d).',
                $idFrom,
                $idTo,
            ));
        }
    }

    /**
     * Option key => environment variable.
     *
     * @var array<string, string>
     */
    public const ENV_KEYS = [
        'mode' => 'LEGACY_BACKFILL_MODE',
        'execute' => 'LEGACY_BACKFILL_EXECUTE',
        'expect_pg_database' => 'LEGACY_BACKFILL_EXPECT_PG_DATABASE',
        'expect_pg_host' => 'LEGACY_BACKFILL_EXPECT_PG_HOST',
        'bucket_size' => 'LEGACY_BACKFILL_BUCKET_SIZE',
        'chunk_rows' => 'LEGACY_BACKFILL_CHUNK_ROWS',
        'scan_window' => 'LEGACY_BACKFILL_SCAN_WINDOW',
        'legacy_pause_ms' => 'LEGACY_BACKFILL_LEGACY_PAUSE_MS',
        'dry_run_read_only_ok' => 'LEGACY_BACKFILL_DRY_RUN_READ_ONLY_OK',
        'close_stale_run_id' => 'LEGACY_BACKFILL_CLOSE_STALE_RUN_ID',
        'report_directory' => 'LEGACY_BACKFILL_REPORT_DIRECTORY',
        'memory_limit' => 'LEGACY_BACKFILL_MEMORY_LIMIT',
        'column_aliases' => 'LEGACY_BACKFILL_COLUMN_ALIASES',
        'only_tables' => 'LEGACY_BACKFILL_ONLY_TABLES',
        'id_from' => 'LEGACY_BACKFILL_ID_FROM',
        'id_to' => 'LEGACY_BACKFILL_ID_TO',
        'dry_run_max_rows' => 'LEGACY_BACKFILL_DRY_RUN_MAX_ROWS',
        'sample_rows' => 'LEGACY_BACKFILL_SAMPLE_ROWS',
    ];

    /**
     * Config values overridden by variables of the real process environment.
     * Production runs with a cached config, in which env() values are frozen at
     * cache time and a .env file is not read at all; a variable set on the
     * command line must still win, so it is read with getenv() at run time.
     *
     * @param  array<string, mixed>  $config
     * @param  (callable(string): (string|false))|null  $getenv
     */
    public static function fromEnvironment(array $config, ?callable $getenv = null): self
    {
        $getenv ??= static fn (string $name): string|false => getenv($name);

        foreach (self::ENV_KEYS as $key => $name) {
            $value = $getenv($name);
            if ($value !== false) {
                $config[$key] = $value;
            }
        }

        return self::fromArray($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $aliases = self::DEFAULT_COLUMN_ALIASES;
        $rawAliases = $config['column_aliases'] ?? '';
        if (is_string($rawAliases)) {
            $rawAliases = self::parseAliases($rawAliases);
        }
        foreach ((array) $rawAliases as $key => $legacyColumn) {
            $aliases[(string) $key] = ($legacyColumn === null || $legacyColumn === '') ? null : (string) $legacyColumn;
        }

        $only = $config['only_tables'] ?? '';
        $onlyList = is_array($only)
            ? array_values(array_map('strval', $only))
            : array_values(array_filter(array_map('trim', explode(',', (string) $only)), static fn (string $t): bool => $t !== ''));

        $closeStale = $config['close_stale_run_id'] ?? null;
        $maxRows = $config['dry_run_max_rows'] ?? null;

        return new self(
            mode: strtolower(trim((string) ($config['mode'] ?? self::MODE_DRY_RUN))) ?: self::MODE_DRY_RUN,
            executeFlag: filter_var($config['execute'] ?? false, FILTER_VALIDATE_BOOLEAN),
            expectDatabase: self::nullIfBlank($config['expect_pg_database'] ?? null),
            expectHost: self::nullIfBlank($config['expect_pg_host'] ?? null),
            bucketSize: max(1000, (int) ($config['bucket_size'] ?? 1_000_000)),
            chunkRows: min(50_000, max(100, (int) ($config['chunk_rows'] ?? 5000))),
            scanWindow: max(10_000, (int) ($config['scan_window'] ?? 250_000)),
            legacyPauseMs: max(0, (int) ($config['legacy_pause_ms'] ?? 0)),
            dryRunReadOnlyOk: filter_var($config['dry_run_read_only_ok'] ?? false, FILTER_VALIDATE_BOOLEAN),
            closeStaleRunId: is_numeric($closeStale) ? (int) $closeStale : null,
            reportDirectory: (string) ($config['report_directory'] ?? storage_path('logs')),
            memoryLimit: (string) ($config['memory_limit'] ?? '4G'),
            columnAliases: $aliases,
            onlyTables: $onlyList,
            idFrom: self::intOrNull($config['id_from'] ?? null),
            idTo: self::intOrNull($config['id_to'] ?? null),
            dryRunMaxRows: ($maxRows === null || trim((string) $maxRows) === '') ? self::DEFAULT_DRY_RUN_MAX_ROWS : max(0, (int) $maxRows),
            sampleRows: min(50, max(0, (int) ($config['sample_rows'] ?? self::DEFAULT_SAMPLE_ROWS))),
        );
    }

    /**
     * Missing ids per table this invocation may read and process: the dry-run
     * limit in dry-run mode, unlimited (0) in every other mode. Execute is
     * never row-capped; stage it with the id window instead.
     */
    public function rowCap(): int
    {
        return $this->mode === self::MODE_DRY_RUN ? $this->dryRunMaxRows : 0;
    }

    public function hasWindow(): bool
    {
        return $this->idFrom !== null || $this->idTo !== null;
    }

    /**
     * Clamps the half-open id range [lo, hi) to the window.
     *
     * @return array{0: int, 1: int}|null [lo, hi) inside the window, null when nothing is left
     */
    public function clampHalfOpen(int $lo, int $hi): ?array
    {
        if ($this->idFrom !== null) {
            $lo = max($lo, $this->idFrom);
        }
        if ($this->idTo !== null) {
            $hi = min($hi, $this->idTo + 1);
        }

        return $lo < $hi ? [$lo, $hi] : null;
    }

    public function windowLabel(): string
    {
        if (! $this->hasWindow()) {
            return 'whole id space';
        }

        return sprintf('ids %s to %s', $this->idFrom ?? 'start', $this->idTo ?? 'end');
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Expected an integer id, got "'.$value.'".');
        }

        return (int) $value;
    }

    /**
     * Parses "table.column=legacy_column;table.column2=" (empty right side = NULL fill).
     *
     * @return array<string, string|null>
     */
    public static function parseAliases(string $raw): array
    {
        $out = [];
        foreach (explode(';', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || ! str_contains($pair, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $pair, 2);
            $out[trim($key)] = trim($value) === '' ? null : trim($value);
        }

        return $out;
    }

    public function includesTable(string $pgTable): bool
    {
        return $this->onlyTables === [] || in_array($pgTable, $this->onlyTables, true);
    }

    private static function nullIfBlank(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
