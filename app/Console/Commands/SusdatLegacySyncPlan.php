<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Susdat\LegacySyncPlanBuilder;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * Builds the plan that SusdatLegacyValuesSyncSeeder applies: the cells of
 * susdat_substances whose value really differs from the final legacy (NDS1) value.
 *
 * No database access: it reads three JSONL exports (one JSON object per line) and writes
 * the plan to database/seeders/seeds/susdat/legacy_sync/. The exports are made with
 * read-only commands, run from the project root; each SQL statement is in the file named:
 *
 * legacy (SQL: export_legacy.sql, one JSON object per line, values are strings or null):
 *   ssh mk03-claude mariadb -P 3308 susdat -N -B --raw < database/seeders/seeds/susdat/legacy_sync/export_legacy.sql > legacy.jsonl
 *   SELECT JSON_OBJECT('sus_id', LPAD(sus_id,8,'0'), '<each legacy column>', `<column>`, ...) FROM susdat ORDER BY sus_id;
 *
 * production (SQL: export_production.sql; the four metadata columns as their exact json text):
 *   ssh mk03-claude psql -h 127.0.0.1 -d nds -At < database/seeders/seeds/susdat/legacy_sync/export_production.sql > production.jsonl
 *   SELECT row_to_json(t) FROM (SELECT id, code, status, <15 page columns>, metadata_synonyms::text AS metadata_synonyms, ... FROM susdat_substances ORDER BY id) t;
 *   For a local plan the same file goes to the local database:
 *   docker exec -i nds-postgres psql -U app -d nds -At < database/seeders/seeds/susdat/legacy_sync/export_production.sql > production.jsonl
 *
 * audits (SQL: export_audits.sql):
 *   ssh mk03-claude psql -h 127.0.0.1 -d nds -At < database/seeders/seeds/susdat/legacy_sync/export_audits.sql > audits.jsonl
 *   SELECT row_to_json(t) FROM (SELECT id, auditable_id, event, old_values::text AS old_values, new_values::text AS new_values, user_id, created_at FROM audits WHERE auditable_type = 'App\Models\Susdat\Substance' ORDER BY id) t;
 *
 * The statements are plain SELECTs, not \copy: the text format of COPY doubles every
 * backslash and would corrupt the JSON escapes (a code ending in "\r" is exported as \r).
 */
class SusdatLegacySyncPlan extends Command
{
    public const PLAN_DIRECTORY = 'seeders/seeds/susdat/legacy_sync';

    public const PLAN_PREFIX = 'susdat_legacy_sync_plan_';

    protected $signature = 'susdat:legacy-sync-plan
        {legacy : JSONL export of legacy susdat}
        {production : JSONL export of susdat_substances}
        {audits : JSONL export of Substance audits}
        {--environment=production : production or local}
        {--approve=* : CODE:column cells to overwrite although changed in NDS2.0}';

    protected $description = 'Plan the copy of final legacy SUSDAT values into susdat_substances (no database access)';

    public function handle(LegacySyncPlanBuilder $builder): int
    {
        ini_set('memory_limit', '-1');

        $environment = (string) $this->option('environment');

        try {
            $inputs = [];
            $rows = [];

            foreach (['legacy', 'production', 'audits'] as $name) {
                $path = (string) $this->argument($name);
                $rows[$name] = $this->readJsonLines($path);
                $inputs[$name] = ['file' => basename($path), 'sha256' => (string) hash_file('sha256', $path), 'rows' => count($rows[$name])];
            }

            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $result = $builder->build($rows['legacy'], $rows['production'], $rows['audits'], [
                'environment' => $environment,
                'approvals' => array_values(array_map('strval', (array) $this->option('approve'))),
                'inputs' => $inputs,
                'created_at_utc' => $now->format('Y-m-d H:i:s'),
            ]);

            $path = $this->writePlan($result['plan'], $environment, $now);
        } catch (InvalidArgumentException|RuntimeException|JsonException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Plan written: {$path}");
        $this->info('SHA-256: '.hash_file('sha256', $path));

        foreach ($result['summary'] as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonLines(string $path): array
    {
        $handle = is_file($path) ? @fopen($path, 'r') : false;

        if ($handle === false) {
            throw new RuntimeException("Cannot read {$path}.");
        }

        $rows = [];
        $number = 0;

        while (($line = fgets($handle)) !== false) {
            $number++;

            if (trim($line) === '') {
                continue;
            }

            try {
                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                fclose($handle);

                throw new RuntimeException("{$path} line {$number} is not valid JSON: {$exception->getMessage()}");
            }

            if (! is_array($row)) {
                fclose($handle);

                throw new RuntimeException("{$path} line {$number} is not a JSON object.");
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function writePlan(array $plan, string $environment, DateTimeImmutable $now): string
    {
        $directory = database_path(self::PLAN_DIRECTORY);
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.self::PLAN_PREFIX.$environment.'_'.$now->format('Ymd\THis\Z').'.json';
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException("Cannot create the plan file {$path}.");
        }

        $content = json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        if (fwrite($handle, $content) !== strlen($content)) {
            fclose($handle);

            throw new RuntimeException("Cannot write the plan file {$path}.");
        }

        if (! fclose($handle)) {
            throw new RuntimeException("Cannot close the plan file {$path}.");
        }

        return $path;
    }
}
