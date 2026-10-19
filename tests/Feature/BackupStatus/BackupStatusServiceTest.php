<?php

declare(strict_types=1);

namespace Tests\Feature\BackupStatus;

use App\Services\BackupStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackupStatusServiceTest extends TestCase
{
    private const BASE_URL = 'http://uptime-kuma:3001';

    private const SLUG = 'nds-backups';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.backup_status.url' => self::BASE_URL,
            'services.backup_status.slug' => self::SLUG,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00', 'UTC'));
    }

    public function test_all_ok_shows_the_newest_backup_in_local_time_with_its_size(): void
    {
        $this->fakeKuma('heartbeats_all_ok.json');

        $status = app(BackupStatusService::class)->status();

        $this->assertTrue($status['available']);
        $this->assertFalse($status['warning']);
        $this->assertFalse($status['stale']);
        $this->assertSame('NDS2.0 nightly backup', $status['monitor_name']);
        $this->assertSame('ok', $status['latest']['state']);
        $this->assertSame('OK', $status['latest']['label']);
        $this->assertSame('4.2', $status['latest']['size_gb']);
        $this->assertSame('2026-10-20 01:42', $status['latest']['time']->format('Y-m-d H:i'));
        $this->assertSame('Europe/Bratislava', $status['latest']['time']->getTimezone()->getName());
        $this->assertSame('2026-10-20 01:42, 4.2 GB', $status['latest']['tooltip']);
    }

    public function test_only_the_last_30_entries_are_returned_oldest_first(): void
    {
        $this->fakeKuma('heartbeats_all_ok.json');

        $bars = app(BackupStatusService::class)->status()['bars'];

        $this->assertCount(BackupStatusService::BAR_COUNT, $bars);
        $this->assertSame('2026-09-21 01:42', $bars[0]['time']->format('Y-m-d H:i'));
        $this->assertSame('2026-10-20 01:42', $bars[29]['time']->format('Y-m-d H:i'));
    }

    public function test_newest_failed_is_a_warning(): void
    {
        $this->fakeKuma('heartbeats_newest_failed.json');

        $status = app(BackupStatusService::class)->status();

        $this->assertTrue($status['available']);
        $this->assertTrue($status['warning']);
        $this->assertFalse($status['stale']);
        $this->assertSame('failed', $status['latest']['state']);
        $this->assertSame('FAILED', $status['latest']['label']);
        $this->assertNull($status['latest']['size_gb']);
        $this->assertSame('2026-10-20 01:42, failed', $status['latest']['tooltip']);
        $this->assertSame('ok', $status['bars'][0]['state']);
    }

    public function test_null_ping_is_ok_without_a_size(): void
    {
        $this->fakeKuma('heartbeats_ping_null.json');

        $status = app(BackupStatusService::class)->status();

        $this->assertTrue($status['available']);
        $this->assertFalse($status['warning']);
        $this->assertSame('ok', $status['latest']['state']);
        $this->assertNull($status['latest']['size_gb']);
        $this->assertSame('2026-10-20 01:42, ok', $status['latest']['tooltip']);
    }

    public function test_newest_entry_older_than_26_hours_is_a_stale_warning(): void
    {
        $this->fakeKuma('heartbeats_stale.json');

        $status = app(BackupStatusService::class)->status();

        $this->assertTrue($status['available']);
        $this->assertSame('ok', $status['latest']['state']);
        $this->assertTrue($status['stale']);
        $this->assertTrue($status['warning']);
    }

    public function test_pending_and_maintenance_entries_are_grey(): void
    {
        $this->fakeKuma('heartbeats_mixed.json');

        $bars = app(BackupStatusService::class)->status()['bars'];

        $this->assertSame(['ok', 'other', 'other', 'ok'], array_column($bars, 'state'));
        $this->assertSame('2026-10-18 01:42, pending', $bars[1]['tooltip']);
        $this->assertSame('2026-10-19 01:42, maintenance', $bars[2]['tooltip']);
        $this->assertSame('5.1', $bars[3]['size_gb']);
    }

    public function test_unreachable_kuma_is_unavailable(): void
    {
        Http::fake(['*' => Http::failedConnection('Connection timed out after 5000 milliseconds')]);

        $status = app(BackupStatusService::class)->status();

        $this->assertFalse($status['available']);
        $this->assertFalse($status['warning']);
        $this->assertNull($status['latest']);
        $this->assertSame([], $status['bars']);
    }

    public function test_missing_status_page_is_unavailable(): void
    {
        Http::fake([
            self::BASE_URL.'/api/status-page/heartbeat/*' => Http::response([], 200),
            self::BASE_URL.'/api/status-page/*' => Http::response(['status' => 'fail', 'msg' => 'Status Page Not Found'], 404),
        ]);

        $status = app(BackupStatusService::class)->status();

        $this->assertFalse($status['available']);
        $this->assertNull($status['latest']);
    }

    public function test_server_error_is_unavailable(): void
    {
        Http::fake(['*' => Http::response('Bad Gateway', 502)]);

        $this->assertFalse(app(BackupStatusService::class)->status()['available']);
    }

    public function test_missing_configuration_is_unavailable_without_any_request(): void
    {
        config(['services.backup_status.url' => null]);
        Http::fake();

        $status = app(BackupStatusService::class)->status();

        $this->assertFalse($status['available']);
        Http::assertNothingSent();
    }

    public function test_result_is_cached_between_calls(): void
    {
        $this->fakeKuma('heartbeats_all_ok.json');

        app(BackupStatusService::class)->status();
        app(BackupStatusService::class)->status();

        Http::assertSentCount(2);
    }

    public function test_requests_use_the_configured_base_url_and_slug(): void
    {
        $this->fakeKuma('heartbeats_all_ok.json');

        app(BackupStatusService::class)->status();

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/api/status-page/'.self::SLUG);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/api/status-page/heartbeat/'.self::SLUG);
    }

    private function fakeKuma(string $heartbeatFixture): void
    {
        Http::fake([
            self::BASE_URL.'/api/status-page/heartbeat/'.self::SLUG => Http::response($this->fixture($heartbeatFixture)),
            self::BASE_URL.'/api/status-page/'.self::SLUG => Http::response($this->fixture('status_page.json')),
        ]);
    }

    /**
     * @return array<mixed>
     */
    private function fixture(string $name): array
    {
        $json = file_get_contents(base_path('tests/Fixtures/backup_status/'.$name));

        return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }
}
