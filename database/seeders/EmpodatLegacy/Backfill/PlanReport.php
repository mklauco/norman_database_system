<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;

/**
 * Accumulates the text report and a machine-readable JSON twin, and writes
 * both under storage/logs. The banner states unmistakably whether anything
 * was written.
 */
final class PlanReport
{
    /** @var list<string> */
    private array $lines = [];

    /** @var array<string, mixed> */
    private array $data = [];

    private readonly string $startedAt;

    public function __construct(private readonly string $mode, private bool $writes)
    {
        $this->startedAt = date('Y-m-d H:i:s');
    }

    public function setWrites(bool $writes): void
    {
        $this->writes = $writes;
    }

    public function banner(): string
    {
        if ($this->writes) {
            return '*** EXECUTE MODE: this run WRITES to the PostgreSQL target (insert-only). ***';
        }

        return '*** '.strtoupper($this->mode).': NOTHING WAS WRITTEN TO ANY DATABASE. Read-only. ***';
    }

    public function heading(string $title): void
    {
        $this->lines[] = '';
        $this->lines[] = '== '.$title.' '.str_repeat('=', max(3, 70 - strlen($title)));
    }

    public function add(string $line): void
    {
        $this->lines[] = $line;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function render(): string
    {
        return implode("\n", [
            $this->banner(),
            'EMPODAT legacy backfill report, mode='.$this->mode.', started '.$this->startedAt,
            ...$this->lines,
            '',
            $this->banner(),
            '',
        ]);
    }

    /**
     * @return array{0: string, 1: string} [text path, json path]
     */
    public function write(string $directory): array
    {
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot create report directory '.$directory);
        }

        $base = rtrim($directory, '/').'/empodat_legacy_backfill_'.$this->mode.'_'.date('Ymd_His');
        file_put_contents($base.'.txt', $this->render());
        file_put_contents($base.'.json', json_encode([
            'mode' => $this->mode,
            'wrote_to_database' => $this->writes,
            'started_at' => $this->startedAt,
            'data' => $this->data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));

        return [$base.'.txt', $base.'.json'];
    }
}
