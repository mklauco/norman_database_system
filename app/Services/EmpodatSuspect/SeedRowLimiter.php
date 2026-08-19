<?php

declare(strict_types=1);

namespace App\Services\EmpodatSuspect;

/**
 * Opt-in cap on the number of `empodat_suspect_main` rows written PER SOURCE
 * FILE by the EmpodatSuspect seeders.
 *
 * Re-importing all 15 EMPODAT Suspect source spreadsheets takes hours. When
 * `EMPODAT_SUSPECT_SEED_ROW_LIMIT` is set (see `config/empodat_suspect.php`),
 * a developer can smoke-test the whole pipeline against a small, bounded
 * sample per file instead of a full reload.
 *
 * Semantics of the cap — read carefully before calling {@see reached()}:
 *   - The limit counts main rows written for ONE source file. It is not a
 *     count of spreadsheet rows, and not a running total across files —
 *     each source file's import is checked against the same configured
 *     number independently.
 *   - Callers must call {@see reached()} only at SOURCE-ROW boundaries, i.e.
 *     after every main row spawned by one spreadsheet row has been written.
 *     Each spreadsheet row spawns one main row per station column, so
 *     stopping mid source-row would truncate the station list and produce a
 *     sample covering only the first few stations. Stopping at the boundary
 *     instead preserves full station diversity while still bounding the
 *     total size.
 *   - 10 000 is the intended value in practice: it sits comfortably above
 *     the seeders' `BATCH_SIZE` of 4 000, so a capped run still exercises
 *     multiple batch flushes plus a partial final flush — exactly where
 *     batch-boundary bugs hide.
 *
 * This class does not enforce where it is called; it only reports state.
 * Getting the call site right is the seeder's responsibility.
 *
 * Default is NO CAP. A developer's real, full production-grade reload also
 * runs on their local machine, so a cap that switched itself on by default
 * would silently truncate the very import they are trying to complete. The
 * cap is opt-in only, via `EMPODAT_SUSPECT_SEED_ROW_LIMIT` in `.env`.
 *
 * Hard production guard: every public method here behaves as "no cap" when
 * `app()->isProduction()`, regardless of configuration. Even if the env var
 * leaks into a production `.env`, it is ignored — a silently truncated
 * production import is the worst possible outcome of this feature.
 */
class SeedRowLimiter
{
    /**
     * The row limit configured via `config('empodat_suspect.seed_row_limit')`,
     * validated to be a positive integer, or null when none is configured
     * (unset, non-numeric, zero, or negative all resolve to null).
     */
    private readonly ?int $configuredLimit;

    public function __construct()
    {
        $this->configuredLimit = $this->resolveConfiguredLimit();
    }

    /**
     * Whether the row cap should be enforced right now: a valid limit is
     * configured AND we are not running in production.
     */
    public function isActive(): bool
    {
        return $this->configuredLimit !== null && ! app()->isProduction();
    }

    /**
     * The effective per-source-file row limit, or null when uncapped.
     *
     * Returns null whenever {@see isActive()} is false — including in
     * production, even if a limit is configured — so callers never need to
     * check `isActive()` separately before trusting this value.
     */
    public function limit(): ?int
    {
        return $this->isActive() ? $this->configuredLimit : null;
    }

    /**
     * Whether the cap has been reached for a source file, given the number
     * of main rows written so far for THAT file. Always false when uncapped
     * (including in production).
     *
     * Callers must only invoke this at source-row boundaries — see the
     * class docblock for why.
     */
    public function reached(int $mainRowsWritten): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $mainRowsWritten >= $this->configuredLimit;
    }

    /**
     * One-line, human-readable banner describing the current mode, meant
     * for seeder console output.
     */
    public function banner(): string
    {
        if (! $this->isActive()) {
            return 'FULL IMPORT — no row cap';
        }

        return sprintf('ROW CAP ACTIVE — %d rows/file', $this->configuredLimit);
    }

    /**
     * Read and validate the configured limit. Never throws: a configured
     * value that is missing, non-numeric, zero, or negative is treated as
     * "no cap".
     */
    private function resolveConfiguredLimit(): ?int
    {
        $configured = config('empodat_suspect.seed_row_limit');

        if (! is_numeric($configured)) {
            return null;
        }

        $limit = (int) $configured;

        return $limit > 0 ? $limit : null;
    }
}
