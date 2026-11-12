<?php

declare(strict_types=1);

namespace App\Services\Susdat;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Display formatting shared by the SUSDAT pages, following the project rules: decimal
 * separator ".", thousand separator " ", one placeholder for an empty value and one date
 * format. Values are never rounded: a number keeps every decimal it has, only the trailing
 * zeros of a fixed-scale column (numeric(20,8) gives 199.25000000) are dropped.
 *
 * Identifiers (codes, CAS, PubChem CID, ChemSpider ID, DTXSID) are not numbers and must
 * never go through number() or count(), which would group their digits.
 */
final class SusdatDisplay
{
    /** Shown for one empty value. */
    public const EMPTY = 'N/A';

    /** Shown for an empty list or section (categories, sources, metadata). */
    public const EMPTY_LIST = 'No data available';

    /** Dates are stored and shown in UTC. */
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === [] || (is_string($value) && trim($value) === '');
    }

    /**
     * A whole count, e.g. 126496 -> "126 496".
     */
    public static function count(int|float|string|null $value): string
    {
        if ($value === null || ! is_numeric($value)) {
            return self::EMPTY;
        }

        return number_format((float) $value, 0, '.', ' ');
    }

    /**
     * A measured value as stored, with spaces between thousands, e.g. "1370059.52" ->
     * "1 370 059.52" and "199.25000000" -> "199.25". Text that is not a plain decimal
     * number ("NA", "1.0e-5", a formula) is returned unchanged.
     */
    public static function number(mixed $value): string
    {
        if (self::isEmpty($value)) {
            return self::EMPTY;
        }

        $text = is_float($value) ? (string) json_encode($value) : trim((string) $value);

        if (preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $text, $parts) !== 1) {
            return is_float($value) ? $text : (string) $value;
        }

        $integer = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $parts[2]);
        $decimals = rtrim($parts[3] ?? '', '0');

        return $parts[1].$integer.($decimals === '' ? '' : '.'.$decimals);
    }

    /**
     * A date and time in UTC, e.g. "2025-05-30 12:09:24".
     */
    public static function date(mixed $value): string
    {
        if (self::isEmpty($value)) {
            return self::EMPTY;
        }

        try {
            $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse((string) $value);
        } catch (Throwable) {
            return (string) $value;
        }

        return $date->utc()->format(self::DATE_FORMAT);
    }
}
