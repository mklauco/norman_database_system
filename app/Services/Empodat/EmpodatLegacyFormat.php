<?php

declare(strict_types=1);

namespace App\Services\Empodat;

use DateTimeInterface;

/**
 * Legacy NDS display rules for the EMPODAT unit and sampling date, on plain
 * scalars so the search table, the CSV export and the API can share them
 * without loading models. Same output as EmpodatRecordDisplay::recordUnit()
 * and ::samplingDate() for the same input.
 *
 * Pass the raw column values (strings), not model-cast values: the
 * EmpodatMinor casts turn `sampling_date` and `sampling_date_t` into Carbon.
 */
final class EmpodatLegacyFormat
{
    /**
     * The unit of the concentration value, LoD and LoQ: the record's own
     * `empodat_minor.unit_extra` when filled (as plain text), else the
     * matrix unit as given (pass it through EmpodatRecordDisplay::plainUnit()).
     */
    public static function unit(?string $unitExtra, ?string $matrixUnit): ?string
    {
        if ($unitExtra !== null && trim($unitExtra) !== '') {
            return EmpodatRecordDisplay::plainUnit($unitExtra);
        }

        return $matrixUnit;
    }

    /**
     * The single sampling date: "12.06.2012", "09.08.2010 13:25:00",
     * "July 2019" or "2017", and "start – stop" when an end date exists.
     * Aggregate records (`dcod_id` = 2) have none: null.
     */
    public static function samplingDate(
        mixed $year,
        mixed $sampleDate,
        mixed $month,
        mixed $day,
        mixed $time,
        mixed $stopDate,
        mixed $stopYear,
        mixed $stopMonth,
        mixed $stopDay,
        mixed $stopTime,
        mixed $dcodId,
    ): ?string {
        if ((int) $dcodId === 2) {
            return null;
        }

        $start = self::datePartsToString(
            self::partsFromDatetime($sampleDate) ?? [(int) $year, $month, $day],
            $time,
        );

        if ($start === null) {
            return null;
        }

        $stop = self::datePartsToString(
            self::partsFromDatetime($stopDate) ?? [$stopYear, $stopMonth, $stopDay],
            $stopTime,
        );

        return $stop === null || $stop === $start ? $start : $start.' – '.$stop;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function partsFromDatetime(mixed $value): ?array
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim($value), $m) !== 1 || (int) $m[1] < 1) {
            return null;
        }

        return [(int) $m[1], (int) $m[2], (int) $m[3]];
    }

    /**
     * @param  array{0: mixed, 1: mixed, 2: mixed}  $parts  year, month, day
     */
    private static function datePartsToString(array $parts, mixed $time): ?string
    {
        [$year, $month, $day] = array_map(static fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $parts);

        if ($year < 1) {
            return null;
        }

        if ($month < 1 || $month > 12) {
            return (string) $year;
        }

        if ($day < 1 || ! checkdate($month, $day, $year)) {
            return date('F', mktime(0, 0, 0, $month, 1)).' '.$year;
        }

        $formatted = sprintf('%02d.%02d.%04d', $day, $month, $year);

        if (is_string($time) && preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $t) === 1
            && ((int) $t[1] + (int) $t[2] + (int) ($t[3] ?? 0)) > 0) {
            $formatted .= sprintf(' %02d:%02d:%02d', (int) $t[1], (int) $t[2], (int) ($t[3] ?? 0));
        }

        return $formatted;
    }
}
