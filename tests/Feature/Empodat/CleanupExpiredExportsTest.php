<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Models\Backend\ExportDownload;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies the retention policy logic of `exports:cleanup` against a real
 * disk + database, but isolated to a sandbox subdirectory and a transaction
 * that's rolled back so production export files and ExportDownload rows
 * are not touched.
 */
class CleanupExpiredExportsTest extends TestCase
{
    private string $sandboxDir;

    protected function setUp(): void
    {
        parent::setUp();

        // ExportDownload writes go through the default Laravel connection,
        // which phpunit.xml points at sqlite/:memory:. Restore the real
        // database so the model can read/write the export_downloads table.
        $database = $this->resolveRealPostgresDatabase();
        if ($database === null) {
            $this->markTestSkipped(
                'Cannot resolve a real PostgreSQL database (read .env DB_DATABASE or set TEST_PG_DATABASE).'
            );
        }

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => $database,
        ]);
        DB::purge('pgsql');

        try {
            DB::connection('pgsql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: '.$e->getMessage());
        }

        $this->sandboxDir = storage_path('app/exports/_retention_test_'.bin2hex(random_bytes(4)));
        mkdir($this->sandboxDir, 0775, true);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        if (is_dir($this->sandboxDir)) {
            foreach (glob($this->sandboxDir.'/*') as $f) {
                @unlink($f);
            }
            @rmdir($this->sandboxDir);
        }

        parent::tearDown();
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $oldFile = $this->writeFile('old.csv', '...', age: 48 * 3600);
        $newFile = $this->writeFile('new.csv', '...', age: 1 * 3600);

        $this->artisan('exports:cleanup', ['--hours' => 24, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertFileExists($oldFile, 'Dry-run must not delete anything');
        $this->assertFileExists($newFile);
    }

    public function test_files_older_than_window_are_deleted(): void
    {
        $oldFile = $this->writeFile('old.csv', 'old content', age: 48 * 3600);
        $newFile = $this->writeFile('new.csv', 'new content', age: 1 * 3600);

        $this->artisan('exports:cleanup', ['--hours' => 24])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($oldFile, 'File older than 24h must be deleted');
        $this->assertFileExists($newFile, 'File within window must remain');
    }

    public function test_export_download_row_is_marked_when_file_deleted(): void
    {
        $filename = 'test_export_'.bin2hex(random_bytes(4)).'.csv';
        $this->writeFile($filename, 'data', age: 48 * 3600);

        $rowId = DB::table('export_downloads')->insertGetId([
            'user_id' => null,
            'filename' => $filename,
            'format' => 'csv',
            'database_key' => 'empodat',
            'status' => 'completed',
            'file_size_bytes' => 1234,
            'file_size_formatted' => '1.2 KB',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('exports:cleanup', ['--hours' => 24])
            ->assertExitCode(0);

        $row = ExportDownload::find($rowId);
        $this->assertNotNull($row);
        $this->assertNull($row->file_size_bytes, 'file_size_bytes should be cleared');
        $this->assertNull($row->file_size_formatted, 'file_size_formatted should be cleared');
        $this->assertSame('completed', $row->status, 'status must be preserved for history');
        $this->assertStringContainsString('24h retention', (string) $row->message);
    }

    public function test_orphan_file_is_deleted_with_no_db_update(): void
    {
        $filename = 'orphan_'.bin2hex(random_bytes(4)).'.csv';
        $path = $this->writeFile($filename, 'orphan', age: 48 * 3600);

        $this->artisan('exports:cleanup', ['--hours' => 24])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($path);
        $this->assertFalse(
            DB::table('export_downloads')->where('filename', $filename)->exists(),
            'No phantom row should be created'
        );
    }

    public function test_invalid_hours_returns_failure(): void
    {
        $this->artisan('exports:cleanup', ['--hours' => 0])
            ->assertExitCode(1);
    }

    private function writeFile(string $name, string $content, int $age): string
    {
        $path = $this->sandboxDir.'/'.$name;
        file_put_contents($path, $content);
        $mtime = time() - $age;
        touch($path, $mtime);

        return $path;
    }

    private function resolveRealPostgresDatabase(): ?string
    {
        $override = getenv('TEST_PG_DATABASE');
        if (is_string($override) && $override !== '' && $override !== ':memory:') {
            return $override;
        }

        $envFile = base_path('.env');
        if (! is_readable($envFile)) {
            return null;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^DB_DATABASE\s*=\s*"?([^"\s]+)"?\s*$/', $line, $m)) {
                $value = trim($m[1]);
                if ($value !== '' && $value !== ':memory:') {
                    return $value;
                }
            }
        }

        return null;
    }
}
