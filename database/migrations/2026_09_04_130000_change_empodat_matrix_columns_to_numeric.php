<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The measurement columns added in the 2026_09_03 migrations were created as
 * varchar. They hold numbers, so they are converted to numeric(20, 6) here.
 *
 * This is a forward-only type change on tables that already exist in
 * production; the original migrations are left exactly as they ran.
 *
 * Raw DDL rather than `->change()`: PostgreSQL will not cast varchar to
 * numeric without a USING clause — it refuses on the column types alone, even
 * when the table is empty — and Laravel's PostgresGrammar::compileChange()
 * emits no USING clause.
 */
return new class extends Migration
{
    /**
     * Columns to convert, with the varchar length to restore on rollback.
     *
     * @var array<string, array<string, int>>
     */
    private array $columns = [
        'empodat_matrix_water_ground' => [
            'alkalinity' => 100,
            'nh4' => 100,
            'dissolved_o2' => 100,
            'cod' => 100,
            'so42' => 100,
            'hco3' => 100,
            'toc' => 100,
            'cl' => 100,
            'po43' => 100,
            'calcium' => 100,
            'iron' => 100,
            'magnesium' => 100,
            'manganese' => 100,
        ],
        'empodat_matrix_sewage_sludge' => [
            'cod' => 100,
            'toc' => 100,
            'conductivity' => 100,
            'bod5' => 100,
            'orthophosphate_po43' => 100,
            'p_total' => 100,
            'nitrate_no3' => 100,
            'ammonium_nh4' => 100,
            'n_total' => 100,
            'sludge_retention_time' => 100,
            'flow' => 255,
        ],
        'empodat_matrix_suspended_matter' => [
            'flow' => 255,
        ],
        'empodat_matrix_water_waste' => [
            'cod' => 100,
            'toc' => 100,
            'conductivity' => 100,
            'orthophosphate_po43' => 100,
            'p_total' => 100,
            'n_no2' => 100,
            'nitrate_no3' => 100,
            'ammonium_nh4' => 100,
            'n_total' => 100,
            'bod5' => 100,
        ],
    ];

    public function up(): void
    {
        foreach (array_keys($this->columns) as $table) {
            DB::statement($this->toNumericSql($table));
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->columns) as $table) {
            DB::statement($this->toVarcharSql($table));
        }
    }

    /**
     * Kept as a separate method so the exact DDL can be reviewed and tested
     * without executing a migration.
     */
    private function toNumericSql(string $table): string
    {
        $changes = [];

        foreach (array_keys($this->columns[$table]) as $column) {
            $changes[] = sprintf(
                'ALTER COLUMN %1$s TYPE numeric(20, 6) USING NULLIF(btrim(%1$s), \'\')::numeric',
                $column
            );
        }

        return sprintf('ALTER TABLE %s %s', $table, implode(', ', $changes));
    }

    private function toVarcharSql(string $table): string
    {
        $changes = [];

        foreach ($this->columns[$table] as $column => $length) {
            $changes[] = sprintf(
                'ALTER COLUMN %1$s TYPE varchar(%2$d) USING %1$s::varchar',
                $column,
                $length
            );
        }

        return sprintf('ALTER TABLE %s %s', $table, implode(', ', $changes));
    }
};
