<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ServerStatsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServerStatsServiceTest extends TestCase
{
    private ServerStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ServerStatsService;
    }

    public function test_disk_usage_reports_a_consistent_snapshot(): void
    {
        $disk = $this->service->diskUsage();

        $this->assertIsArray($disk);
        $this->assertGreaterThan(0, $disk['total_bytes']);
        $this->assertSame($disk['total_bytes'] - $disk['free_bytes'], $disk['used_bytes']);
        $this->assertGreaterThanOrEqual(0.0, $disk['used_percentage']);
        $this->assertLessThanOrEqual(100.0, $disk['used_percentage']);
        $this->assertNotSame('', $disk['total_human']);
    }

    public function test_uptime_is_either_unavailable_or_well_formed(): void
    {
        $uptime = $this->service->uptime();

        if (! is_readable('/proc/uptime')) {
            $this->assertNull($uptime);

            return;
        }

        $this->assertIsArray($uptime);
        $this->assertGreaterThan(0, $uptime['host_seconds']);
        $this->assertNotSame('', $uptime['host_human']);

        if ($uptime['container_seconds'] !== null) {
            $this->assertGreaterThanOrEqual(0, $uptime['container_seconds']);
            $this->assertLessThanOrEqual($uptime['host_seconds'], $uptime['container_seconds']);
        }
    }

    public function test_stats_are_cached(): void
    {
        Cache::flush();

        $this->assertFalse(Cache::has('system.server_stats'));

        $stats = $this->service->stats();

        $this->assertArrayHasKey('disk', $stats);
        $this->assertArrayHasKey('uptime', $stats);
        $this->assertTrue(Cache::has('system.server_stats'));
        $this->assertSame($stats, $this->service->stats());
    }

    public function test_format_bytes_uses_the_application_number_conventions(): void
    {
        $this->assertSame('512 B', $this->service->formatBytes(512));
        $this->assertSame('1 kB', $this->service->formatBytes(1024));
        $this->assertSame('1.0 GB', $this->service->formatBytes(1024 ** 3));
        $this->assertSame('1.5 GB', $this->service->formatBytes(1.5 * 1024 ** 3));
        $this->assertSame('2.0 TB', $this->service->formatBytes(2 * 1024 ** 4));
    }

    public function test_format_duration_collapses_to_the_two_largest_units(): void
    {
        $this->assertSame('0 min', $this->service->formatDuration(0));
        $this->assertSame('0 min', $this->service->formatDuration(-5));
        $this->assertSame('45 min', $this->service->formatDuration(2700));
        $this->assertSame('3 h 0 min', $this->service->formatDuration(10800));
        $this->assertSame('42 d 3 h', $this->service->formatDuration(42 * 86400 + 3 * 3600));
    }
}
