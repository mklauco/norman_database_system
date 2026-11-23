<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;

/**
 * Thrown when at least one preflight check fails; nothing has been written.
 */
final class PreflightFailed extends RuntimeException
{
    /**
     * @param  list<string>  $failures
     */
    public function __construct(public readonly array $failures)
    {
        parent::__construct('Preflight failed: '.implode('; ', $failures));
    }
}
