<?php

declare(strict_types=1);

namespace App\Jobs\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Runs one allowlisted `empodat-suspect:*` artisan command and records the
 * outcome on its EmpodatSuspectCommandRun row.
 *
 * The command signature is never built from client input: `$commandKey` is
 * looked up against config('empodat_suspect_commands.commands'), and every
 * argument is re-validated against that same definition immediately before
 * the command runs — the run may have sat in the queue for a while, so the
 * world (e.g. which file ids still exist) may have changed since it was
 * queued.
 *
 * A non-blocking Cache::lock keyed by the command signature guarantees at
 * most one execution of a given command runs at a time. A second trigger
 * while one is in flight is refused immediately rather than queued behind
 * it — a concurrent run of the same command would corrupt the derived
 * materialized views.
 */
class RunEmpodatSuspectCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * These commands run for minutes to hours against ~40M rows. Matches the
     * `exports` queue worker's own --timeout=7200 in docker-compose(.production).yml —
     * if a command genuinely needs longer than 2 hours, that worker flag has
     * to move too.
     */
    public int $timeout = 7200;

    public int $tries = 1;

    private const LOCK_TTL = 7200;

    /**
     * Cap on stored command output. These commands print progress banners
     * that can run long; this keeps the `output` column bounded.
     */
    private const OUTPUT_CHAR_LIMIT = 20000;

    /**
     * @param  array<string, mixed>  $arguments  Pre-validated by the caller, and
     *                                           re-validated here before use.
     */
    public function __construct(
        public readonly int $runId,
        public readonly string $commandKey,
        public readonly array $arguments,
    ) {
        // Long-running, low-frequency, one-at-a-time by nature — the same
        // profile as the CSV export jobs, so it shares their queue.
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $run = EmpodatSuspectCommandRun::find($this->runId);

        if ($run === null) {
            Log::error("EmpodatSuspect command run #{$this->runId} not found; aborting.");

            return;
        }

        $definition = config("empodat_suspect_commands.commands.{$this->commandKey}");

        if (! is_array($definition)) {
            $this->refuse($run, "Unknown command key: {$this->commandKey}");

            return;
        }

        $lock = Cache::lock($this->lockKey(), self::LOCK_TTL);

        if (! $lock->get()) {
            $this->refuse($run, 'Another run of this command is already in progress. Refused rather than queued behind it.');

            return;
        }

        try {
            $parameters = $this->resolveParameters($definition);

            $run->update([
                'status' => EmpodatSuspectCommandRun::STATUS_RUNNING,
                'started_at' => now(),
            ]);

            $output = new BufferedOutput;
            $start = microtime(true);
            $exitCode = Artisan::call($definition['signature'], $parameters, $output);
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $run->update([
                'status' => $exitCode === 0 ? EmpodatSuspectCommandRun::STATUS_SUCCESS : EmpodatSuspectCommandRun::STATUS_FAILED,
                'finished_at' => now(),
                'duration_ms' => $durationMs,
                'exit_code' => $exitCode,
                'output' => $this->truncate($output->fetch()),
            ]);
        } catch (Throwable $e) {
            $run->refresh();
            $run->update([
                'status' => EmpodatSuspectCommandRun::STATUS_FAILED,
                'finished_at' => now(),
                'output' => $this->truncate(trim(($run->output ?? '')."\n\nException: ".$e->getMessage())),
            ]);

            Log::error("EmpodatSuspect command run #{$run->id} ({$this->commandKey}) failed: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Safety net in case the worker process died before the `finally` block
     * in handle() ran.
     */
    public function failed(Throwable $e): void
    {
        Cache::lock($this->lockKey())->forceRelease();
    }

    /**
     * Builds the `--option => value` array for Artisan::call from this job's
     * pre-validated arguments, re-checked against the allowlist definition.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resolveParameters(array $definition): array
    {
        $optionDefinitions = $definition['options'] ?? [];
        $parameters = [];

        foreach ($optionDefinitions as $optionName => $optionDefinition) {
            if (($optionDefinition['always'] ?? false) === true) {
                $parameters["--{$optionName}"] = true;

                continue;
            }

            if (! array_key_exists($optionName, $this->arguments)) {
                continue;
            }

            $value = $this->arguments[$optionName];

            if ($value === null || $value === '') {
                continue;
            }

            $parameters["--{$optionName}"] = match ($optionDefinition['type']) {
                'enum' => $this->validatedEnum($optionName, $optionDefinition, $value),
                'file_id' => $this->validatedFileId($optionDefinition, $value),
                default => throw new InvalidArgumentException("Unsupported option type for --{$optionName}."),
            };
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $optionDefinition
     */
    private function validatedEnum(string $optionName, array $optionDefinition, mixed $value): string
    {
        $allowed = $optionDefinition['allowed'] ?? [];

        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Value \"{$value}\" is not allowed for --{$optionName}.");
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $optionDefinition
     */
    private function validatedFileId(array $optionDefinition, mixed $value): int
    {
        $fileId = (int) $value;

        $entityId = DatabaseEntity::where('code', $optionDefinition['database_entity_code'])->value('id');

        $exists = $entityId !== null && File::where('id', $fileId)
            ->where('database_entity_id', $entityId)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException("File id {$fileId} is not a valid EMPODAT Suspect source file.");
        }

        return $fileId;
    }

    private function refuse(EmpodatSuspectCommandRun $run, string $message): void
    {
        $run->update([
            'status' => EmpodatSuspectCommandRun::STATUS_REFUSED,
            'finished_at' => now(),
            'output' => $message,
        ]);
    }

    private function truncate(string $output): string
    {
        return mb_strlen($output) > self::OUTPUT_CHAR_LIMIT
            ? mb_substr($output, 0, self::OUTPUT_CHAR_LIMIT)."\n\n[... output truncated at ".number_format(self::OUTPUT_CHAR_LIMIT, 0, '.', ' ').' characters ...]'
            : $output;
    }

    private function lockKey(): string
    {
        return "empodat_suspect.command.{$this->commandKey}";
    }
}
