<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use InvalidArgumentException;

/**
 * Options of one reconcile invocation, read with getenv() at run time because
 * production runs with a cached config.
 */
final readonly class ReconcileOptions
{
    public const MODE_DRY_RUN = 'dry-run';

    public const MODE_EXECUTE = 'execute';

    public function __construct(
        public string $mode = self::MODE_DRY_RUN,
        public bool $executeFlag = false,
        public ?string $expectDatabase = null,
        public ?int $expectCount = null,
        public ?int $idFrom = null,
        public ?int $idTo = null,
        public int $chunk = 20000,
        public int $legacyPauseMs = 50,
        public ?int $closeStaleRunId = null,
    ) {
        if (! in_array($mode, [self::MODE_DRY_RUN, self::MODE_EXECUTE], true)) {
            throw new InvalidArgumentException('RECONCILE_MODE must be dry-run or execute (got "'.$mode.'").');
        }
        if ($chunk < 1) {
            throw new InvalidArgumentException('RECONCILE_CHUNK must be at least 1.');
        }
    }

    /**
     * @param  (callable(string): (string|false))|null  $getenv
     */
    public static function fromEnvironment(?callable $getenv = null): self
    {
        $getenv ??= static fn (string $name): string|false => getenv($name);

        $string = static function (string $name) use ($getenv): ?string {
            $value = $getenv($name);

            return $value === false || trim($value) === '' ? null : trim($value);
        };
        $int = static function (string $name, ?int $default) use ($string): ?int {
            $value = $string($name);
            if ($value === null) {
                return $default;
            }
            if (preg_match('/^\d+$/', $value) !== 1) {
                throw new InvalidArgumentException($name.' must be a non-negative integer (got "'.$value.'").');
            }

            return (int) $value;
        };

        return new self(
            mode: $string('RECONCILE_MODE') ?? self::MODE_DRY_RUN,
            executeFlag: in_array(strtolower($string('RECONCILE_EXECUTE') ?? ''), ['true', '1', 'yes'], true),
            expectDatabase: $string('RECONCILE_EXPECT_PG_DATABASE'),
            expectCount: $int('RECONCILE_EXPECT_COUNT', null),
            idFrom: $int('RECONCILE_ID_FROM', null),
            idTo: $int('RECONCILE_ID_TO', null),
            chunk: (int) $int('RECONCILE_CHUNK', 20000),
            legacyPauseMs: (int) $int('RECONCILE_LEGACY_PAUSE_MS', 50),
            closeStaleRunId: $int('RECONCILE_CLOSE_STALE_RUN', null),
        );
    }

    public function isExecute(): bool
    {
        return $this->mode === self::MODE_EXECUTE;
    }

    public function windowLabel(): string
    {
        if ($this->idFrom === null && $this->idTo === null) {
            return 'unbounded';
        }

        return 'id '.($this->idFrom ?? '-inf').' .. '.($this->idTo ?? '+inf').' (inclusive)';
    }

    /**
     * @return list<string> reasons writing is not allowed (empty = allowed)
     */
    public function writeViolations(string $connectedDatabase, int $plannedCount): array
    {
        $reasons = [];

        if ($this->mode !== self::MODE_EXECUTE) {
            $reasons[] = 'RECONCILE_MODE is not "execute"';
        }
        if (! $this->executeFlag) {
            $reasons[] = 'RECONCILE_EXECUTE is not true';
        }
        if ($this->expectDatabase === null) {
            $reasons[] = 'RECONCILE_EXPECT_PG_DATABASE is not set';
        } elseif ($this->expectDatabase !== $connectedDatabase) {
            $reasons[] = 'RECONCILE_EXPECT_PG_DATABASE="'.$this->expectDatabase.'" but connected to "'.$connectedDatabase.'"';
        }
        if ($this->expectCount === null) {
            $reasons[] = 'RECONCILE_EXPECT_COUNT is not set';
        } elseif ($this->expectCount !== $plannedCount) {
            $reasons[] = 'RECONCILE_EXPECT_COUNT='.$this->expectCount.' but the plan has '.$plannedCount;
        }

        return $reasons;
    }
}
