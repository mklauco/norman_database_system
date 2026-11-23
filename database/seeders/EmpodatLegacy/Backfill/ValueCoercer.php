<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Pure value conversion for the COPY text format. NULL stays NULL (faithful
 * copy); there is no "'' to NULL" or "NULL to ''" rewriting except where a PG
 * numeric column cannot hold the legacy text, which becomes NULL.
 */
final class ValueCoercer
{
    public const KIND_INT = 'i';

    public const KIND_NUMERIC = 'n';

    public const KIND_TEXT = 's';

    public static function convert(string $kind, mixed $value): ?string
    {
        return match ($kind) {
            self::KIND_INT => self::intText($value),
            self::KIND_NUMERIC => self::numericText($value),
            default => self::text($value),
        };
    }

    public static function intText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) (int) $value;
    }

    /**
     * Numeric text for a PG numeric / double precision column. Floats keep
     * their shortest round-trip representation (no precision=14 truncation).
     * Legacy text that is not a number (or a "1,5" style decimal) becomes NULL.
     */
    public static function numericText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_float($value)) {
            return is_finite($value) ? var_export($value, true) : null;
        }
        if (is_int($value)) {
            return (string) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (preg_match('/^[+-]?\d+,\d+$/', $text) === 1) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? $text : null;
    }

    public static function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_float($value)) {
            return var_export($value, true);
        }

        return (string) $value;
    }

    public static function doubleText(?float $value): ?string
    {
        if ($value === null || ! is_finite($value)) {
            return null;
        }

        return var_export($value, true);
    }

    /**
     * One COPY text-format line, without the trailing newline
     * (PDO::pgsqlCopyFromArray adds it).
     *
     * @param  list<string|null>  $fields
     */
    public static function copyLine(array $fields): string
    {
        $parts = [];
        foreach ($fields as $field) {
            if ($field === null) {
                $parts[] = '\\N';

                continue;
            }
            $parts[] = strtr(str_replace("\0", '', $field), [
                '\\' => '\\\\',
                "\t" => '\\t',
                "\n" => '\\n',
                "\r" => '\\r',
            ]);
        }

        return implode("\t", $parts);
    }
}
