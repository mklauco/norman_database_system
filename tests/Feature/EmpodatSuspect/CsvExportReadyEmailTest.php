<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Mail\EmpodatSuspect\CsvExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportReadyEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function messageContent(array $overrides = []): array
    {
        return array_merge([
            'user' => 'Test User',
            'filename' => 'empodat_suspect_export_uid_5_20260613143022.csv',
            'download_link' => 'https://example.test/empodat_suspect/search/download/file.csv',
            'my_downloads_link' => 'https://example.test/backend/export-downloads?user_id=5',
            'is_public' => true,
            'total_records' => 10,
            'processing_time' => 1.5,
            'file_size' => '1 KB',
            'export_failed' => false,
        ], $overrides);
    }

    public function test_public_export_email_shows_direct_download_button(): void
    {
        $rendered = (new CsvExportReady($this->messageContent(['is_public' => true])))->render();

        $this->assertStringContainsString('Download CSV', $rendered);
        $this->assertStringContainsString('search/download/file.csv', $rendered);
        $this->assertStringNotContainsString('You must be logged in', $rendered);
    }

    public function test_non_public_export_email_requires_login_and_points_to_my_downloads(): void
    {
        $rendered = (new CsvExportReady($this->messageContent(['is_public' => false])))->render();

        $this->assertStringContainsString('You must be logged in to download', $rendered);
        $this->assertStringContainsString('Go to My Downloads', $rendered);
        $this->assertStringContainsString('export-downloads?user_id=5', $rendered);

        // The raw download link must NOT be offered while the module is non-public.
        $this->assertStringNotContainsString('search/download/file.csv', $rendered);
    }

    public function test_failure_email_is_unaffected_by_public_flag(): void
    {
        $rendered = (new CsvExportReady($this->messageContent([
            'export_failed' => true,
            'error' => 'Something went wrong',
            'is_public' => false,
        ])))->render();

        $this->assertStringContainsString('Empodat Suspect Export Processing Error', $rendered);
        $this->assertStringContainsString('Something went wrong', $rendered);
        $this->assertStringNotContainsString('You must be logged in', $rendered);
    }
}
