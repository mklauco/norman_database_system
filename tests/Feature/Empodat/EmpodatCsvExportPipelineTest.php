<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Jobs\Empodat\EmpodatCsvExportJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Pipeline-level test for the PDO server-side cursor CSV export.
 *
 * Verifies the streaming/error mechanics of streamSelectToCsv() against a
 * real PostgreSQL connection using generate_series — no fixtures, no writes,
 * no dependency on the multi-million-row empodat_main table.
 */
class EmpodatCsvExportPipelineTest extends TestCase
{
    /**
     * phpunit.xml overrides DB_DATABASE to ":memory:" for the sqlite default.
     * The streaming code targets the named "pgsql" connection (which still
     * pulls DB_DATABASE from env), so restore the real database name from
     * the project's .env before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = $this->resolveRealPostgresDatabase();

        if ($database === null) {
            $this->markTestSkipped(
                'Cannot resolve a real PostgreSQL database (read .env DB_DATABASE or set TEST_PG_DATABASE).'
            );
        }

        config(['database.connections.pgsql.database' => $database]);
        DB::purge('pgsql');

        try {
            DB::connection('pgsql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: '.$e->getMessage());
        }
    }

    public function test_stream_select_to_csv_writes_bom_headers_and_rows(): void
    {
        $job = new EmpodatCsvExportJob(0, new User);
        $tempPath = tempnam(sys_get_temp_dir(), 'empodat_csv_pipeline_');

        try {
            $headers = ['ID', 'Name', 'Value'];
            $sql = "SELECT i AS id, 'name_' || i AS name, (i * 10) AS value "
                  .'FROM generate_series(1, 3) AS i';

            $rowCount = $this->invokeStream($job, $sql, $headers, $tempPath);

            $this->assertSame(3, $rowCount, 'Should report 3 data rows');

            $content = file_get_contents($tempPath);
            $this->assertNotFalse($content);

            $this->assertSame(
                "\xEF\xBB\xBF",
                substr($content, 0, 3),
                'File must start with UTF-8 BOM'
            );

            $csv = substr($content, 3);
            $lines = array_values(array_filter(
                preg_split('/\r\n|\n/', $csv),
                static fn ($l) => $l !== ''
            ));

            $this->assertCount(4, $lines, 'Expected 1 header row + 3 data rows');
            $this->assertSame('ID,Name,Value', $lines[0]);
            $this->assertSame('1,name_1,10', $lines[1]);
            $this->assertSame('2,name_2,20', $lines[2]);
            $this->assertSame('3,name_3,30', $lines[3]);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_stream_select_to_csv_handles_zero_row_result(): void
    {
        $job = new EmpodatCsvExportJob(0, new User);
        $tempPath = tempnam(sys_get_temp_dir(), 'empodat_csv_empty_');

        try {
            $headers = ['ID'];
            $sql = 'SELECT i FROM generate_series(1, 0) AS i';

            $rowCount = $this->invokeStream($job, $sql, $headers, $tempPath);

            $this->assertSame(0, $rowCount);

            $content = file_get_contents($tempPath);
            $this->assertNotFalse($content);

            // BOM + header line only
            $csv = substr($content, 3);
            $lines = array_values(array_filter(
                preg_split('/\r\n|\n/', $csv),
                static fn ($l) => $l !== ''
            ));
            $this->assertCount(1, $lines);
            $this->assertSame('ID', $lines[0]);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_stream_select_to_csv_escapes_csv_special_characters(): void
    {
        $job = new EmpodatCsvExportJob(0, new User);
        $tempPath = tempnam(sys_get_temp_dir(), 'empodat_csv_escape_');

        try {
            $headers = ['Text'];
            // Comma, double-quote, newline — each triggers fputcsv enclosure
            $sql = "SELECT 'has,comma' UNION ALL SELECT 'has\"quote' UNION ALL SELECT E'has\nnewline'";

            $rowCount = $this->invokeStream($job, $sql, $headers, $tempPath);

            $this->assertSame(3, $rowCount);

            $content = file_get_contents($tempPath);
            $csv = substr($content, 3); // strip BOM

            $this->assertStringContainsString('"has,comma"', $csv);
            $this->assertStringContainsString('"has""quote"', $csv);
            $this->assertStringContainsString("\"has\nnewline\"", $csv);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_stream_select_to_csv_cleans_up_on_sql_error(): void
    {
        $job = new EmpodatCsvExportJob(0, new User);
        $tempPath = tempnam(sys_get_temp_dir(), 'empodat_csv_err_');

        try {
            $headers = ['ID'];
            $sql = 'SELECT * FROM table_that_definitely_does_not_exist_xyzzy';

            $threw = false;
            try {
                $this->invokeStream($job, $sql, $headers, $tempPath);
            } catch (\Throwable $e) {
                $threw = true;
                $this->assertInstanceOf(\PDOException::class, $e);
            }

            $this->assertTrue($threw, 'Expected PDOException to be thrown');
            $this->assertFileDoesNotExist(
                $tempPath,
                'Partial output file must be removed on error'
            );
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function invokeStream(EmpodatCsvExportJob $job, string $sql, array $headers, string $path): int
    {
        $r = new ReflectionClass($job);
        $m = $r->getMethod('streamSelectToCsv');
        $m->setAccessible(true);

        return $m->invokeArgs($job, [$sql, $headers, $path]);
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
