<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\Connection;
use PDO;
use RuntimeException;

/**
 * Makes a connection unable to write, in three independent layers:
 *
 *   1. Database: the session is switched to read-only (MariaDB
 *      `SET SESSION TRANSACTION READ ONLY`, PostgreSQL
 *      `SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY`) on every PDO of
 *      the connection and verified, so the server itself refuses any write.
 *   2. Application: a beforeExecuting hook rejects every statement that is not
 *      a plain read (SELECT, SHOW, EXPLAIN, harmless SET/RESET) before it is
 *      sent, and any attempt to switch the session back to read-write.
 *   3. Reconnect: Laravel re-runs a query on a fresh connection after a lost
 *      connection; the reconnector is replaced so every fresh session is
 *      locked (and verified) again before it is used.
 *
 * The legacy MariaDB is locked in EVERY mode; PostgreSQL in every mode except
 * execute.
 */
final class ReadOnlyGuard
{
    /** Functions with side effects that a SELECT could call. */
    private const SIDE_EFFECT_FUNCTIONS = '/\b(nextval|setval|pg_terminate_backend|pg_cancel_backend|pg_advisory_\w*|lo_import|lo_export|lo_unlink|dblink\w*|pg_reload_conf|pg_rotate_logfile|get_lock|release_lock)\s*\(/i';

    /** Keywords that never belong in a read: data-modifying CTEs, SELECT ... INTO, DDL, locks. */
    private const WRITE_KEYWORDS = '/\b(insert|update|delete|merge|into|truncate|copy|create|drop|alter|grant|revoke|call|lock|unlock|handler|rename)\b/i';

    /**
     * Locks the connection; returns a one-line confirmation for the report.
     */
    public static function lock(Connection $connection): string
    {
        self::lockSession($connection);

        $connection->setReconnector(static function (Connection $lost): Connection {
            $fresh = app('db')->reconnect($lost->getName());
            self::lockSession($fresh);

            return $fresh;
        });

        $connection->beforeExecuting(static function (string $query, array $bindings, Connection $conn): void {
            if (! self::isReadOnlyStatement($query)) {
                throw new RuntimeException(sprintf(
                    'READ-ONLY GUARD: blocked a non-read statement on connection "%s": %s',
                    $conn->getName(),
                    mb_substr(preg_replace('/\s+/', ' ', $query) ?? $query, 0, 200),
                ));
            }
        });

        return sprintf(
            'connection "%s" (%s) is read-only: session locked and verified, non-read statements blocked, reconnects re-locked',
            $connection->getName(),
            $connection->getDriverName(),
        );
    }

    /**
     * True for statements that cannot change data: SELECT/SHOW/EXPLAIN/DESCRIBE
     * without locking or file clauses, a WITH that contains no data-modifying
     * statement, and SET/RESET that do not touch the transaction mode.
     */
    public static function isReadOnlyStatement(string $sql): bool
    {
        $text = (string) preg_replace('~\A(?:\s+|--[^\n]*(?:\n|\z)|/\*.*?\*/)*~s', '', $sql);
        if (preg_match('/\A([a-z]+)/i', $text, $match) !== 1) {
            return false;
        }
        $first = strtolower($match[1]);

        if (in_array($first, ['set', 'reset'], true)) {
            return preg_match('/(transaction|characteristics|read[\s_]*write|read[\s_]*only|autocommit|sql_log_bin|global|persist)/i', $text) !== 1;
        }

        if (! in_array($first, ['select', 'show', 'explain', 'describe', 'desc', 'with'], true)) {
            return false;
        }

        $bare = self::stripLiterals($text);
        if (str_contains(rtrim($bare, " \t\r\n;"), ';')) {
            return false;
        }
        if (preg_match(self::WRITE_KEYWORDS, $bare) === 1) {
            return false;
        }
        if (preg_match('/\bfor\s+(share|no\s+key|key\s+share)\b/i', $bare) === 1) {
            return false;
        }

        return preg_match(self::SIDE_EFFECT_FUNCTIONS, $bare) !== 1;
    }

    /**
     * Switches every PDO of the connection to a read-only session and checks it.
     */
    private static function lockSession(Connection $connection): void
    {
        $pdos = [$connection->getPdo()];
        $read = $connection->getReadPdo();
        if ($read instanceof PDO && $read !== $pdos[0]) {
            $pdos[] = $read;
        }

        foreach ($pdos as $pdo) {
            if (! $pdo instanceof PDO) {
                throw new RuntimeException('READ-ONLY GUARD: connection "'.$connection->getName().'" has no PDO to lock.');
            }

            match ($connection->getDriverName()) {
                'mysql', 'mariadb' => self::lockMariaDb($pdo, $connection->getName()),
                'pgsql' => self::lockPostgres($pdo, $connection->getName()),
                default => throw new RuntimeException('READ-ONLY GUARD: unsupported driver '.$connection->getDriverName()),
            };
        }
    }

    private static function lockMariaDb(PDO $pdo, string $name): void
    {
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');

        $value = null;
        foreach (['@@session.tx_read_only', '@@session.transaction_read_only'] as $variable) {
            try {
                $value = $pdo->query('SELECT '.$variable)->fetchColumn();
                break;
            } catch (\PDOException) {
                continue;
            }
        }
        if ((string) $value !== '1') {
            throw new RuntimeException('READ-ONLY GUARD: could not verify the read-only session on "'.$name.'" (tx_read_only='.var_export($value, true).').');
        }
    }

    private static function lockPostgres(PDO $pdo, string $name): void
    {
        $pdo->exec('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY');

        $current = (string) $pdo->query('SHOW transaction_read_only')->fetchColumn();
        $default = (string) $pdo->query('SHOW default_transaction_read_only')->fetchColumn();
        if ($current !== 'on' || $default !== 'on') {
            throw new RuntimeException(sprintf(
                'READ-ONLY GUARD: could not verify the read-only session on "%s" (transaction_read_only=%s, default_transaction_read_only=%s).',
                $name,
                $current,
                $default,
            ));
        }
    }

    /**
     * Blanks out quoted literals and identifiers so a keyword inside a string
     * or a quoted column name does not count.
     */
    private static function stripLiterals(string $sql): string
    {
        return (string) preg_replace(['/\'(?:[^\'\\\\]|\\\\.|\'\')*\'/s', '/"(?:[^"]|"")*"/s', '/`(?:[^`]|``)*`/s'], ["''", '""', '``'], $sql);
    }
}
