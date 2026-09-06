<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Reads host disk usage and uptime from inside the application container.
 *
 * Both values are obtained without shelling out:
 *  - disk usage via statvfs on a bind-mounted path (resolves to the host filesystem)
 *  - uptime via /proc, which is not namespaced by Docker and therefore reports the host
 *
 * On non-Linux hosts (local macOS development) the uptime readings are unavailable
 * and the corresponding values are returned as null.
 */
class ServerStatsService
{
    private const CACHE_KEY = 'system.server_stats';

    private const CACHE_TTL_SECONDS = 60;

    /**
     * USER_HZ, the unit of /proc/<pid>/stat starttime. 100 on every Linux
     * platform this application runs on.
     */
    private const CLOCK_TICKS_PER_SECOND = 100;

    /**
     * Cached snapshot of the server statistics shown on the System Settings page.
     *
     * @return array{disk: array<string, mixed>|null, uptime: array<string, mixed>|null}
     */
    public function stats(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => [
            'disk' => $this->diskUsage(),
            'uptime' => $this->uptime(),
        ]);
    }

    /**
     * Disk usage of the filesystem holding Laravel's storage directory.
     *
     * In production storage_path() is a symlink into the ./shared bind mount, so
     * statvfs resolves to the host volume rather than the container overlay.
     *
     * @return array{total_bytes: int, free_bytes: int, used_bytes: int, used_percentage: float, total_human: string, free_human: string, used_human: string}|null
     */
    public function diskUsage(): ?array
    {
        $path = storage_path();

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return null;
        }

        $used = $total - $free;

        return [
            'total_bytes' => (int) $total,
            'free_bytes' => (int) $free,
            'used_bytes' => (int) $used,
            'used_percentage' => round($used / $total * 100, 1),
            'total_human' => $this->formatBytes($total),
            'free_human' => $this->formatBytes($free),
            'used_human' => $this->formatBytes($used),
        ];
    }

    /**
     * Host uptime, plus the uptime of this container when it can be determined.
     *
     * @return array{host_seconds: int, host_human: string, container_seconds: int|null, container_human: string|null}|null
     */
    public function uptime(): ?array
    {
        $hostSeconds = $this->hostUptimeSeconds();

        if ($hostSeconds === null) {
            return null;
        }

        $containerSeconds = $this->containerUptimeSeconds($hostSeconds);

        return [
            'host_seconds' => $hostSeconds,
            'host_human' => $this->formatDuration($hostSeconds),
            'container_seconds' => $containerSeconds,
            'container_human' => $containerSeconds === null ? null : $this->formatDuration($containerSeconds),
        ];
    }

    /**
     * Format a byte count using the application's number formatting conventions.
     */
    public function formatBytes(float $bytes): string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        $decimals = $unitIndex >= 3 ? 1 : 0;

        return number_format($bytes, $decimals, '.', ' ').' '.$units[$unitIndex];
    }

    /**
     * Format a duration in seconds as a compact human readable string.
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0;
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return sprintf('%d d %d h', $days, $hours);
        }

        if ($hours > 0) {
            return sprintf('%d h %d min', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Kernel uptime in seconds, read from /proc/uptime.
     *
     * Docker does not namespace this file, so inside the container it reports the
     * uptime of the VPS itself.
     */
    private function hostUptimeSeconds(): ?int
    {
        $raw = @file_get_contents('/proc/uptime');

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $seconds = (float) explode(' ', trim($raw))[0];

        return $seconds > 0 ? (int) $seconds : null;
    }

    /**
     * Uptime of this container in seconds, derived from the start time of PID 1.
     */
    private function containerUptimeSeconds(int $hostSeconds): ?int
    {
        $raw = @file_get_contents('/proc/1/stat');

        if (! is_string($raw)) {
            return null;
        }

        $commEnd = strrpos($raw, ')');

        if ($commEnd === false) {
            return null;
        }

        // Fields after "pid (comm)" start at field 3 (state), so field 22
        // (starttime) sits at index 19 of the remainder.
        $fields = explode(' ', trim(substr($raw, $commEnd + 1)));

        if (! isset($fields[19]) || ! is_numeric($fields[19])) {
            return null;
        }

        $startTicks = (int) $fields[19];

        if ($startTicks <= 0) {
            return null;
        }

        $containerSeconds = $hostSeconds - intdiv($startTicks, self::CLOCK_TICKS_PER_SECOND);

        return $containerSeconds >= 0 ? $containerSeconds : null;
    }
}
