<?php

declare(strict_types=1);

namespace Tests\Feature\ARBG;

use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * The ARB/G CSV export (issues #29, #30).
 *
 * It once ran through the shared EMPODAT export job, which selected
 * empodat_main.id against arbg_gene_main and failed with "missing FROM-clause
 * entry for table empodat_main". Each module now exports on its own; these
 * tests run the real query so that class of leak cannot come back unnoticed.
 */
class CsvDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    public function test_guests_cannot_export_gene_data(): void
    {
        $this->get(route('arbg.gene.search.download', $this->queryLog('arbg.gene')))
            ->assertRedirect();
    }

    public function test_guests_cannot_export_bacteria_data(): void
    {
        $this->get(route('arbg.bacteria.search.download', $this->queryLog('arbg.bacteria')))
            ->assertRedirect();
    }

    public function test_gene_export_produces_a_csv(): void
    {
        $this->assertExportProducesCsv('arbg.gene', 'arbg.gene.search.download', 'Gene Name');
    }

    public function test_bacteria_export_produces_a_csv(): void
    {
        $this->assertExportProducesCsv('arbg.bacteria', 'arbg.bacteria.search.download', 'Sample Matrix');
    }

    public function test_gene_export_applies_its_filters_without_leaking_another_module(): void
    {
        $queryLog = $this->queryLog('arbg.gene', [
            'countrySearch' => '["AT"]',
            'matrixSearch' => '["8"]',
            'geneNameSearch' => '[]',
            'organisationSearch' => '[]',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('arbg.gene.search.download', $queryLog))
            ->assertRedirect();

        $this->assertSame('completed', ExportDownload::query()->latest('id')->first()->status);
    }

    private function assertExportProducesCsv(string $databaseKey, string $route, string $expectedHeading): void
    {
        $queryLog = $this->queryLog($databaseKey);

        $response = $this->actingAs(User::factory()->create())
            ->get(route($route, $queryLog));

        $response->assertRedirect();

        $export = ExportDownload::query()->latest('id')->first();
        $this->assertNotNull($export, 'The export was never recorded.');
        $this->assertSame('completed', $export->status, 'Export failed: '.($export->message ?? ''));

        $download = $this->get($response->headers->get('Location'));
        $download->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $download->headers->get('content-type'));
        $this->assertStringContainsString(
            $expectedHeading,
            $download->baseResponse instanceof BinaryFileResponse
                ? (string) file_get_contents($download->baseResponse->getFile()->getPathname())
                : $download->streamedContent()
        );
    }

    /**
     * @param  array<string, string>  $request
     */
    private function queryLog(string $databaseKey, array $request = []): int
    {
        QueryLog::query()->insert([
            'content' => json_encode(['request' => $request, 'bindings' => []]),
            'query' => 'select 1',
            'database_key' => $databaseKey,
            'query_hash' => hash('sha256', $databaseKey),
            'total_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return QueryLog::query()->latest('id')->first()->id;
    }
}
