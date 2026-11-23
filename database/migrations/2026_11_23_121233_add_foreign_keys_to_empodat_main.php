<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces the empodat_main foreign keys that the create migration declares
 * but production never had. The data was checked on production before each
 * key was added here: every value points to an existing row.
 *
 * Each key is added in two steps so the 100M-row table is never locked
 * against writes for the length of a full scan:
 *   1. ADD CONSTRAINT ... NOT VALID: instant; new and changed rows are checked
 *      from this moment on. Takes a brief SHARE ROW EXCLUSIVE lock, guarded by
 *      lock_timeout so it fails instead of queueing writes behind a long
 *      transaction.
 *   2. VALIDATE CONSTRAINT: scans the existing rows under SHARE UPDATE
 *      EXCLUSIVE, which does not block reads or writes.
 *
 * Runs outside a transaction, otherwise the step 1 lock would be held during
 * step 2. Safe to re-run: step 1 is skipped when a key exists, step 2 when it
 * is already validated.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    private const TABLE = 'empodat_main';

    /**
     * Same rules as in 2024_05_29_190148_create_empodat_main.php.
     *
     * @var list<array{column: string, references: string, on_update: string, on_delete: string}>
     */
    private const FOREIGN_KEYS = [
        ['column' => 'station_id', 'references' => 'empodat_stations', 'on_update' => 'cascade', 'on_delete' => 'restrict'],
        ['column' => 'matrix_id', 'references' => 'list_matrices', 'on_update' => 'cascade', 'on_delete' => 'restrict'],
        ['column' => 'concentration_indicator_id', 'references' => 'list_concentration_indicators', 'on_update' => 'cascade', 'on_delete' => 'restrict'],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEYS as $key) {
            $name = $this->constraintName($key['column']);

            if (! $this->constraintExists($name)) {
                DB::statement("SET lock_timeout = '30s'");

                try {
                    Schema::table(self::TABLE, function (Blueprint $table) use ($key, $name): void {
                        $table->foreign($key['column'], $name)
                            ->references('id')
                            ->on($key['references'])
                            ->onUpdate($key['on_update'])
                            ->onDelete($key['on_delete'])
                            ->notValid();
                    });
                } finally {
                    DB::statement('RESET lock_timeout');
                }
            }

            if (! $this->constraintExists($name, validated: true)) {
                DB::statement('ALTER TABLE '.self::TABLE.' VALIDATE CONSTRAINT '.$name);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::FOREIGN_KEYS) as $key) {
            DB::statement('ALTER TABLE '.self::TABLE.' DROP CONSTRAINT IF EXISTS '.$this->constraintName($key['column']));
        }
    }

    private function constraintName(string $column): string
    {
        return self::TABLE.'_'.$column.'_foreign';
    }

    private function constraintExists(string $name, bool $validated = false): bool
    {
        return DB::table('pg_constraint')
            ->whereRaw('conrelid = ?::regclass', [self::TABLE])
            ->where('conname', $name)
            ->when($validated, fn ($query) => $query->where('convalidated', true))
            ->exists();
    }
};
