<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Base of the Janus and PIKME loaders: reads one source file in chunks, resolves
 * susdat_substance_id from the in-memory substance map and replaces the content of exactly one
 * hazards table (TRUNCATE ... RESTART IDENTITY without CASCADE, then chunked inserts, all in
 * one transaction; on any error the table keeps its previous content).
 *
 * Helper class of HazardsDataSeeder; not a seeder, nothing to run. Run only
 * Database\Seeders\Hazards\HazardsDataSeeder.
 */
abstract class HazardsSourceLoader
{
    private const CHUNK_SIZE = 1000;

    /**
     * @param  Closure(string, string): void  $say  receives a level (info|warn|error) and a message
     */
    public function __construct(protected readonly Closure $say) {}

    abstract protected function table(): string;

    abstract protected function label(): string;

    abstract protected function path(): string;

    /** Column holding the unique key of the source (norman_id or dtxid). */
    abstract protected function keyColumn(): string;

    /**
     * Column => [type, header spellings]. Types: string, float.
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    abstract protected function columns(): array;

    /**
     * @return array{0: int|null, 1: string}
     */
    abstract protected function resolve(string $key, HazardsSubstanceMap $map): array;

    /**
     * @return array<string, int>|null null when the file is missing (table untouched)
     */
    public function load(HazardsSubstanceMap $map): ?array
    {
        $path = $this->path();

        if (! is_file($path)) {
            ($this->say)('warn', "{$this->label()}: file not found, {$this->table()} left untouched: {$path}");

            return null;
        }

        ($this->say)('info', "{$this->label()}: loading {$path} into {$this->table()}...");
        $started = microtime(true);

        $stats = DB::transaction(fn (): array => $this->replaceTable($path, $map, $started));

        ($this->say)('info', "{$this->label()}: done in ".round(microtime(true) - $started, 1).'s.');

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    private function replaceTable(string $path, HazardsSubstanceMap $map, float $started): array
    {
        $table = $this->table();
        $stats = [
            'rows_read' => 0,
            'inserted' => 0,
            'skipped_without_key' => 0,
            'skipped_duplicate_key' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
        ];

        $reader = $this->openReader($path);
        $now = now();
        $fileName = basename($path);
        $seen = [];
        $sources = null;

        DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY");

        $reader->getRows()->chunk(self::CHUNK_SIZE)->each(function ($rows, $chunkNo) use (&$stats, &$seen, &$sources, $map, $now, $fileName, $table, $started): void {
            $records = [];

            foreach ($rows as $row) {
                $stats['rows_read']++;
                $sources ??= $this->resolveHeaders(array_keys($row));

                $key = $this->cell($row, $sources[$this->keyColumn()] ?? null);
                $key = $key === null ? '' : trim((string) $key);
                if ($key === '') {
                    $stats['skipped_without_key']++;

                    continue;
                }

                if (isset($seen[$key])) {
                    $stats['skipped_duplicate_key']++;

                    continue;
                }
                $seen[$key] = true;

                [$substanceId, $status] = $this->resolve($key, $map);
                $stats[$status]++;

                $record = ['susdat_substance_id' => $substanceId];
                foreach ($this->columns() as $column => [$type]) {
                    $value = $this->cell($row, $sources[$column] ?? null);
                    $record[$column] = match (true) {
                        $column === $this->keyColumn() => $key,
                        $type === 'float' => $this->toFloat($value),
                        default => $this->toString($value),
                    };
                }

                $records[] = $record + [
                    'source_file_name' => $fileName,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($records !== []) {
                DB::table($table)->insert($records);
                $stats['inserted'] += count($records);
            }

            ($this->say)('info', "{$this->label()}: chunk ".($chunkNo + 1).", {$stats['inserted']} rows inserted, ".round(microtime(true) - $started, 1).'s elapsed');
        });

        $reader->close();

        return $stats;
    }

    protected function openReader(string $path): SimpleExcelReader
    {
        return SimpleExcelReader::create($path)->trimHeaderRow();
    }

    /**
     * Maps every column to the real header of the file: case-insensitive, whitespace-trimmed.
     *
     * @param  list<int|string>  $headers
     * @return array<string, string|null>
     */
    private function resolveHeaders(array $headers): array
    {
        $byNormalized = [];
        foreach ($headers as $header) {
            $byNormalized[$this->normalizeHeader((string) $header)] ??= (string) $header;
        }

        $resolved = [];
        foreach ($this->columns() as $column => [, $spellings]) {
            $resolved[$column] = null;
            foreach ([$column, ...$spellings] as $spelling) {
                $normalized = $this->normalizeHeader($spelling);
                if (isset($byNormalized[$normalized])) {
                    $resolved[$column] = $byNormalized[$normalized];

                    break;
                }
            }
        }

        if ($resolved[$this->keyColumn()] === null) {
            throw new RuntimeException("{$this->label()}: no column for {$this->keyColumn()} found in the file header; nothing was changed.");
        }

        return $resolved;
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $header)));
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function cell(array $row, ?string $header): mixed
    {
        return $header === null ? null : ($row[$header] ?? null);
    }

    private function toString(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return is_finite($float) ? $float : null;
    }
}
