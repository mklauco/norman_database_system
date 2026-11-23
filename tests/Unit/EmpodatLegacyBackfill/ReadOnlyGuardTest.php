<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatLegacyBackfill;

use Database\Seeders\EmpodatLegacy\Backfill\ReadOnlyGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReadOnlyGuardTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function readStatements(): array
    {
        return [
            'legacy bucket count' => ['SELECT id DIV 1000000 AS bucket, COUNT(*) AS cnt, CAST(SUM(id) AS CHAR) AS idsum FROM `dct_analysis` WHERE id >= ? AND id <= ? GROUP BY id DIV 1000000'],
            'pg bucket count' => ['SELECT id / 1000000 AS bucket, count(*) AS cnt, sum(id)::text AS idsum FROM "empodat_minor" GROUP BY id / 1000000'],
            'id fetch' => ['SELECT id FROM "empodat_main" WHERE id >= ? AND id < ? ORDER BY id'],
            'legacy row read' => ['SELECT `id`, `sus_id`, `country`, `station_name` FROM dct_analysis WHERE id >= ? AND id <= ? ORDER BY id LIMIT 5000'],
            'lookup scan' => ['SELECT sus_id, id_method, id_data, `matrix` AS matrix_id, country, COUNT(*) AS cnt FROM dct_analysis WHERE id >= ? AND id <= ? GROUP BY sus_id, id_method, id_data, `matrix`, country'],
            'loose index scan' => ['WITH RECURSIVE t AS ( SELECT min(method_id) AS v FROM empodat_main UNION ALL SELECT (SELECT min(method_id) FROM empodat_main WHERE method_id > t.v) FROM t WHERE t.v IS NOT NULL) SELECT v FROM t WHERE v IS NOT NULL'],
            'builder select with timestamps' => ['select "id", "updated_at", "created_at", "is_deleted" from "files" where "database_entity_id" = ?'],
            'builder count' => ['select count(*) as aggregate from "empodat_main" where "id" in (?, ?, ?)'],
            'privilege check' => ["SELECT has_table_privilege(current_user, ?, 'INSERT') AS can_insert, has_table_privilege(current_user, ?, 'SELECT') AS can_select"],
            'legacy grants' => ["SELECT DISTINCT privilege_type FROM information_schema.user_privileges WHERE grantee = CONCAT('''', REPLACE(CURRENT_USER(), '@', '''@'''), '''')"],
            'session check' => ["SELECT current_setting('transaction_read_only') AS ro, current_setting('default_transaction_read_only') AS dro"],
            'legacy session check' => ['SELECT @@session.tx_read_only AS ro'],
            'planner setting' => ['SET enable_sort = off'],
            'planner reset' => ['RESET enable_sort'],
            'show' => ['SHOW transaction_read_only'],
            'keyword inside a literal' => ["SELECT 'insert into x; drop table y' AS label"],
            'leading comment' => ["/* report */ -- note\n SELECT 1"],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function writeStatements(): array
    {
        return [
            'insert' => ['INSERT INTO "empodat_main" ("id") VALUES (?)'],
            'insert after comment' => ['/* harmless */ INSERT INTO x VALUES (1)'],
            'update' => ['update "files" set "name" = ? where "id" = ?'],
            'delete' => ['DELETE FROM empodat_main WHERE id = 1'],
            'copy' => ['COPY stg (id) FROM STDIN'],
            'temp table' => ['CREATE TEMP TABLE stg_legacy_backfill (LIKE "empodat_main" INCLUDING DEFAULTS) ON COMMIT DROP'],
            'truncate' => ['TRUNCATE empodat_main'],
            'drop' => ['DROP TABLE x'],
            'alter' => ['ALTER TABLE x ADD COLUMN y int'],
            'pg back to read write' => ['SET SESSION CHARACTERISTICS AS TRANSACTION READ WRITE'],
            'mariadb back to read write' => ['SET SESSION TRANSACTION READ WRITE'],
            'pg read only off' => ['SET transaction_read_only = off'],
            'pg default read only off' => ['set default_transaction_read_only = off'],
            'mariadb tx_read_only off' => ['SET SESSION tx_read_only = 0'],
            'global setting' => ['SET GLOBAL max_connections = 10'],
            'select for update' => ['SELECT id FROM files WHERE id = 1 FOR UPDATE'],
            'select for share' => ['SELECT id FROM files FOR SHARE'],
            'lock in share mode' => ['SELECT id FROM dct_list LOCK IN SHARE MODE'],
            'select into outfile' => ["SELECT * FROM dct_list INTO OUTFILE '/tmp/x'"],
            'select into table' => ['SELECT * INTO new_table FROM files'],
            'data modifying cte' => ['WITH gone AS (DELETE FROM files WHERE id = 1 RETURNING id) SELECT * FROM gone'],
            'sequence' => ["SELECT nextval('files_id_seq')"],
            'advisory lock' => ['SELECT pg_advisory_lock(1)'],
            'stacked statements' => ['SELECT 1; DROP TABLE x'],
            'lock table' => ['LOCK TABLE files'],
            'call' => ['CALL InsertInBatches(1, 1, 1, 0)'],
            'replace into' => ['REPLACE INTO x VALUES (1)'],
            'empty' => [''],
        ];
    }

    #[DataProvider('readStatements')]
    public function test_read_statements_pass(string $sql): void
    {
        $this->assertTrue(ReadOnlyGuard::isReadOnlyStatement($sql), $sql);
    }

    #[DataProvider('writeStatements')]
    public function test_write_statements_are_blocked(string $sql): void
    {
        $this->assertFalse(ReadOnlyGuard::isReadOnlyStatement($sql), $sql);
    }
}
