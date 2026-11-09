<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Reverts SusdatSourceSubstanceRepairSeeder: puts the rows of the newest backup
 * file back into susdat_source_substance and removes source S133.
 *
 * susdat_substances is only read; the run checks that the relation between its id
 * and code is unchanged. Everything that writes runs in one transaction, so any
 * failure changes nothing.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatSourceSubstanceRestoreSeeder --force
 */
class SusdatSourceSubstanceRestoreSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $backupPath = $this->newestBackup();
        $backup = $this->readBackup($backupPath);
        $this->say("Backup {$backupPath}: {$backup['rows']} rows, SHA-256 ".hash_file('sha256', $backupPath).'.');
        $this->assertSame(
            'Backup fingerprint (only the original rows may be restored)',
            $backup['fingerprint'],
            SusdatSourceSubstanceRepairSeeder::ORIGINAL_LINKS_FINGERPRINT,
        );

        $result = DB::transaction(fn (): array => $this->restore($backupPath, $backup['rows'], $backup['fingerprint']));
        $committedBy = SusdatSourceSubstanceRepairSeeder::utcNow();

        $this->report([
            'susdat_source_substance restore: environment='.app()->environment(),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "links before {$result['links_before']}, links after {$result['links_after']}, S133 rows removed {$result['sources_removed']}",
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "links fingerprint before {$result['links_fingerprint_before']}, after {$result['links_fingerprint_after']}",
            'peak memory '.round(memory_get_peak_usage(true) / 1048576).' MB',
        ]);
    }

    /**
     * Runs inside the transaction.
     *
     * @return array<string, int|string>
     */
    private function restore(string $backupPath, int $backupRows, string $backupFingerprint): array
    {
        DB::statement("SET LOCAL lock_timeout = '".SusdatSourceSubstanceRepairSeeder::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_source_substance IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
        $idCodeFingerprint = SusdatSourceSubstanceRepairSeeder::idCodeFingerprint();
        $linksBefore = DB::table('susdat_source_substance')->count();
        $linksFingerprintBefore = SusdatSourceSubstanceRepairSeeder::linksFingerprint();

        DB::table('susdat_source_substance')->delete();

        $chunk = [];

        foreach ($this->backupRows($backupPath) as $row) {
            $chunk[] = $row;

            if (count($chunk) === SusdatSourceSubstanceRepairSeeder::CHUNK_SIZE) {
                DB::table('susdat_source_substance')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('susdat_source_substance')->insert($chunk);
        }

        $sourcesRemoved = DB::table('suspect_list_exchange_sources')
            ->where('code', SusdatSourceSubstanceRepairSeeder::NEW_SOURCE_CODE)
            ->delete();

        $linksAfter = DB::table('susdat_source_substance')->count();
        $linksFingerprintAfter = SusdatSourceSubstanceRepairSeeder::linksFingerprint();

        $this->assertSame('Links after the restore', $linksAfter, $backupRows);
        $this->assertSame('Links fingerprint after the restore', $linksFingerprintAfter, $backupFingerprint);
        $this->assertSame('id/code fingerprint', SusdatSourceSubstanceRepairSeeder::idCodeFingerprint(), $idCodeFingerprint);

        return [
            'started_at' => $startedAt,
            'finished_at' => SusdatSourceSubstanceRepairSeeder::utcNow(),
            'links_before' => $linksBefore,
            'links_after' => $linksAfter,
            'sources_removed' => $sourcesRemoved,
            'id_code_fingerprint' => $idCodeFingerprint,
            'links_fingerprint_before' => $linksFingerprintBefore,
            'links_fingerprint_after' => $linksFingerprintAfter,
        ];
    }

    private function newestBackup(): string
    {
        $files = glob(storage_path(SusdatSourceSubstanceRepairSeeder::DIRECTORY.'/'.SusdatSourceSubstanceRepairSeeder::BACKUP_PREFIX.'*.csv')) ?: [];
        sort($files, SORT_STRING);

        if ($files === []) {
            throw new RuntimeException('No backup file found. Nothing was changed.');
        }

        return (string) end($files);
    }

    /**
     * @return array{rows: int, fingerprint: string}
     */
    private function readBackup(string $path): array
    {
        $keys = [];

        foreach ($this->backupRows($path) as $row) {
            $keys[] = SusdatSourceSubstanceRepairSeeder::pairKey($row['source_id'], $row['substance_id']);
        }

        return ['rows' => count($keys), 'fingerprint' => SusdatSourceSubstanceRepairSeeder::fingerprintOf($keys)];
    }

    /**
     * @return iterable<array{source_id: int, substance_id: int, created_at: string|null, updated_at: string|null}>
     */
    private function backupRows(string $path): iterable
    {
        foreach (File::lines($path)->skip(1) as $line) {
            if ($line === '') {
                continue;
            }

            [$sourceId, $substanceId, $createdAt, $updatedAt] = explode(',', $line);

            yield [
                'source_id' => (int) $sourceId,
                'substance_id' => (int) $substanceId,
                'created_at' => $createdAt === '' ? null : $createdAt,
                'updated_at' => $updatedAt === '' ? null : $updatedAt,
            ];
        }
    }

    private function assertSame(string $description, int|string $actual, int|string $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("{$description}: expected {$expected}, found {$actual}. Nothing was changed.");
        }
    }

    private function say(string $line): void
    {
        $this->command?->info($line);
    }

    /**
     * @param  list<string>  $lines
     */
    private function report(array $lines): void
    {
        foreach ($lines as $line) {
            $this->say($line);
            Log::info($line);
        }
    }
}
