<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use InvalidArgumentException;

/**
 * A 1:1-by-id side table (empodat_minor or an empodat_matrix_*) and how its
 * columns are read from the legacy table. Columns are read under the same name
 * unless an alias redirects them; a NULL alias fills the PG column with NULL.
 */
final class TableSpec
{
    /** @var array<string, string> pg column => kind (i, n, s) */
    private array $columns = [];

    /**
     * @param  array<string, string|null>  $aliases  pg column => legacy column or null
     */
    public function __construct(
        public readonly string $pgTable,
        public readonly string $legacyTable,
        string $columnSpec,
        private readonly array $aliases = [],
    ) {
        foreach (explode(',', $columnSpec) as $item) {
            $parts = explode(':', trim($item));
            if (count($parts) !== 2 || ! in_array($parts[1], ['i', 'n', 's'], true)) {
                throw new InvalidArgumentException('Bad column spec item "'.$item.'" for '.$pgTable);
            }
            $this->columns[$parts[0]] = $parts[1];
        }
    }

    /**
     * PG columns (without id) => kind.
     *
     * @return array<string, string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * PG column order used for COPY, id first.
     *
     * @return list<string>
     */
    public function pgColumns(): array
    {
        return ['id', ...array_keys($this->columns)];
    }

    /**
     * Legacy column read for a PG column, or null when the PG column is NULL-filled.
     */
    public function legacyColumn(string $pgColumn): ?string
    {
        if (array_key_exists($pgColumn, $this->aliases)) {
            return $this->aliases[$pgColumn];
        }

        return $pgColumn;
    }

    /**
     * Distinct legacy columns to SELECT, id first.
     *
     * @return list<string>
     */
    public function legacySelect(): array
    {
        $select = ['id'];
        foreach (array_keys($this->columns) as $pgColumn) {
            $legacy = $this->legacyColumn($pgColumn);
            if ($legacy !== null && ! in_array($legacy, $select, true)) {
                $select[] = $legacy;
            }
        }

        return $select;
    }

    /**
     * PG columns that are NULL-filled for lack of a legacy source.
     *
     * @return list<string>
     */
    public function nullFilled(): array
    {
        return array_values(array_filter(
            array_keys($this->columns),
            fn (string $c): bool => $this->legacyColumn($c) === null,
        ));
    }

    /**
     * The values a legacy row becomes, keyed by PG column (id first), plus the
     * PG columns whose non-empty legacy value could not be kept and became NULL
     * (e.g. "<0.5" in a numeric column).
     *
     * @return array{values: array<string, string|null>, nulled: list<string>}
     */
    public function convert(object $row): array
    {
        $values = ['id' => (string) (int) $row->id];
        $nulled = [];
        foreach ($this->columns as $pgColumn => $kind) {
            $legacy = $this->legacyColumn($pgColumn);
            if ($legacy === null) {
                $values[$pgColumn] = null;

                continue;
            }
            $raw = $row->{$legacy} ?? null;
            $converted = ValueCoercer::convert($kind, $raw);
            $values[$pgColumn] = $converted;
            if ($converted === null && $raw !== null && trim((string) $raw) !== '') {
                $nulled[] = $pgColumn;
            }
        }

        return ['values' => $values, 'nulled' => $nulled];
    }

    /**
     * One COPY line (see ValueCoercer::copyLine) for a legacy row.
     */
    public function copyLine(object $row): string
    {
        return ValueCoercer::copyLine(array_values($this->convert($row)['values']));
    }
}
