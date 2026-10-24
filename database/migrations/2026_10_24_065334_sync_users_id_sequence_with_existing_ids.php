<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Imported users were inserted with explicit ids, which left the id sequence
     * behind MAX(id). Move it past the highest id so new users get theirs from it.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            SELECT setval(
                pg_get_serial_sequence('users', 'id'),
                COALESCE((SELECT MAX(id) FROM users), 1),
                (SELECT MAX(id) FROM users) IS NOT NULL
            )
        SQL);
    }

    /**
     * Nothing to undo: a sequence ahead of MAX(id) is always valid.
     */
    public function down(): void
    {
        //
    }
};
