<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Decides whether the seeder is allowed to write. Dry run is the default;
 * writing needs ALL of: mode "execute", LEGACY_BACKFILL_EXECUTE=true, an
 * expected target database name that equals what the live connection reports,
 * and (when configured) an expected host that equals the configured host or
 * the server address the connection reports.
 */
final class ExecutionGuard
{
    /**
     * @param  array{database: ?string, host: ?string, addr: ?string}  $identity  what the live PG connection reports
     * @return list<string> human-readable reasons writing is NOT allowed (empty = allowed)
     */
    public static function violations(BackfillOptions $options, array $identity): array
    {
        $problems = [];

        if ($options->mode !== BackfillOptions::MODE_EXECUTE) {
            $problems[] = 'mode is "'.$options->mode.'", writing needs LEGACY_BACKFILL_MODE=execute';
        }

        if (! $options->executeFlag) {
            $problems[] = 'LEGACY_BACKFILL_EXECUTE is not true';
        }

        if ($options->expectDatabase === null) {
            $problems[] = 'LEGACY_BACKFILL_EXPECT_PG_DATABASE is not set';
        } elseif (($identity['database'] ?? null) === null) {
            $problems[] = 'the live connection did not report current_database()';
        } elseif ($identity['database'] !== $options->expectDatabase) {
            $problems[] = sprintf(
                'target database mismatch: expected "%s", connected to "%s"',
                $options->expectDatabase,
                $identity['database'],
            );
        }

        if ($options->expectHost !== null) {
            $known = array_filter([$identity['host'] ?? null, $identity['addr'] ?? null], static fn (?string $v): bool => $v !== null && $v !== '');
            if (! in_array($options->expectHost, $known, true)) {
                $problems[] = sprintf(
                    'target host mismatch: expected "%s", connection reports "%s"',
                    $options->expectHost,
                    implode(' / ', $known) ?: 'nothing',
                );
            }
        }

        return $problems;
    }

    /**
     * @param  array{database: ?string, host: ?string, addr: ?string}  $identity
     */
    public static function writesAllowed(BackfillOptions $options, array $identity): bool
    {
        return self::violations($options, $identity) === [];
    }

    /**
     * True when the operator asked for a write run (mode execute) and the guard
     * still refuses: the seeder must abort instead of silently degrading to a
     * dry run. The execute flag alone (with any other mode) is not a write
     * request; that invocation simply runs read-only.
     *
     * @param  array{database: ?string, host: ?string, addr: ?string}  $identity
     */
    public static function mustAbort(BackfillOptions $options, array $identity): bool
    {
        $wantsWrite = $options->mode === BackfillOptions::MODE_EXECUTE;

        return $wantsWrite && ! self::writesAllowed($options, $identity);
    }
}
