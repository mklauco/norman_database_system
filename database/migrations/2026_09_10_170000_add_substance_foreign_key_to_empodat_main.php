<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the missing foreign key from `empodat_main.substance_id` to
     * `susdat_substances.id`.
     *
     * `empodat_main` had only two foreign keys — on `country_id` and `file_id`
     * — so nothing prevented a row pointing at a substance that does not
     * exist. Production currently holds no such rows; the development database
     * holds 18 729 of them across 151 distinct ids, inherited from the v1
     * import, which is why this constraint is added NOT VALID.
     *
     * NOT VALID means:
     *   - every new and updated row is checked from now on, everywhere
     *   - existing rows are not scanned, so the statement is near-instant and
     *     cannot stall a deploy
     *   - it cannot fail on a database that still holds bad rows
     *
     * Certifying the existing 101 million rows is a separate, deliberate step,
     * run by hand against production when convenient:
     *
     *     ALTER TABLE empodat_main
     *         VALIDATE CONSTRAINT empodat_main_substance_id_foreign;
     *
     * That takes only SHARE UPDATE EXCLUSIVE, so it blocks neither reads nor
     * writes. The equivalent anti-join scan measured 20 seconds on production
     * and 4 seconds on a warm development database.
     *
     * `ON DELETE SET NULL` matches the two foreign keys already on the table.
     *
     * `lock_timeout` is set because ADD CONSTRAINT needs a brief ACCESS
     * EXCLUSIVE lock: without a timeout it queues behind any long-running query
     * on `empodat_main` and every subsequent query queues behind it. Failing
     * fast and retrying is far better than blocking the site.
     */
    private const CONSTRAINT = 'empodat_main_substance_id_foreign';

    public function up(): void
    {
        if ($this->constraintExists()) {
            return;
        }

        DB::statement("SET lock_timeout = '5s'");

        DB::statement('
            ALTER TABLE empodat_main
                ADD CONSTRAINT '.self::CONSTRAINT.'
                FOREIGN KEY (substance_id) REFERENCES susdat_substances(id)
                ON DELETE SET NULL
                NOT VALID
        ');

        DB::statement("SET lock_timeout = '0'");
    }

    public function down(): void
    {
        if (! $this->constraintExists()) {
            return;
        }

        DB::statement("SET lock_timeout = '5s'");
        DB::statement('ALTER TABLE empodat_main DROP CONSTRAINT '.self::CONSTRAINT);
        DB::statement("SET lock_timeout = '0'");
    }

    private function constraintExists(): bool
    {
        return DB::selectOne("
            select 1
            from pg_constraint
            where conrelid = 'empodat_main'::regclass
              and conname = ?
        ", [self::CONSTRAINT]) !== null;
    }
};
