<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

/**
 * Nightly database backup status, read from the "nds-backups" status page of
 * the Uptime Kuma instance that the backup job reports to.
 *
 * Kuma is queried server-side only, at its internal Docker address: the public
 * status-page URL is password-protected. The raw heartbeats are cached; the
 * derived state (warning, staleness) is computed on every call so that "older
 * than 26 hours" does not lag behind the cache.
 */
class BackupStatusService
{
    /**
     * @var array<int, string>
     */
    public const VIEWER_ROLES = ['super_admin', 'admin'];

    public const DISPLAY_TIMEZONE = 'Europe/Bratislava';

    public const BAR_COUNT = 30;

    public const STALE_AFTER_HOURS = 26;

    public const STATUS_FAILED = 0;

    public const STATUS_OK = 1;

    public const STATUS_PENDING = 2;

    public const STATUS_MAINTENANCE = 3;

    private const CACHE_KEY = 'system.backup_status';

    private const CACHE_TTL_SECONDS = 600;

    private const HTTP_TIMEOUT_SECONDS = 5;

    public function canView(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWER_ROLES);
    }

    /**
     * Backup status for display. 'available' is false whenever Kuma could not
     * be read or returned no heartbeats for the monitor.
     *
     * @return array{
     *     available: bool,
     *     monitor_name: string|null,
     *     bars: array<int, array{state: string, label: string, time: CarbonImmutable, size_gb: string|null, tooltip: string}>,
     *     latest: array{state: string, label: string, time: CarbonImmutable, size_gb: string|null, tooltip: string}|null,
     *     stale: bool,
     *     warning: bool,
     * }
     */
    public function status(): array
    {
        $snapshot = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => $this->fetchSnapshot());

        $bars = array_map(
            fn (array $heartbeat): array => $this->presentHeartbeat($heartbeat),
            array_slice($snapshot['heartbeats'], -self::BAR_COUNT),
        );

        $latest = $bars === [] ? null : $bars[array_key_last($bars)];

        $stale = $latest !== null
            && $latest['time']->lt(CarbonImmutable::now()->subHours(self::STALE_AFTER_HOURS));

        return [
            'available' => $latest !== null,
            'monitor_name' => $snapshot['monitor_name'],
            'bars' => $bars,
            'latest' => $latest,
            'stale' => $stale,
            'warning' => $latest !== null && ($latest['state'] === 'failed' || $stale),
        ];
    }

    /**
     * Reads the monitor and its heartbeats from Kuma. Never throws: every
     * failure yields an empty snapshot, which is cached like a successful one
     * so an unreachable Kuma does not add a 5 s timeout to every page load.
     *
     * @return array{monitor_name: string|null, heartbeats: array<int, array{status: int, time: string, ping: float|null}>}
     */
    private function fetchSnapshot(): array
    {
        $empty = ['monitor_name' => null, 'heartbeats' => []];

        $baseUrl = $this->baseUrl();
        $slug = $this->slug();

        if ($baseUrl === null || $slug === null) {
            return $empty;
        }

        try {
            $statusPage = $this->getJson($baseUrl.'/api/status-page/'.rawurlencode($slug));
            $monitor = $this->firstMonitor($statusPage);

            if ($monitor === null) {
                Log::warning('Backup status: no monitor on Kuma status page.', ['slug' => $slug]);

                return $empty;
            }

            $heartbeatPage = $this->getJson($baseUrl.'/api/status-page/heartbeat/'.rawurlencode($slug));
            $rawHeartbeats = $heartbeatPage['heartbeatList'][(string) $monitor['id']] ?? null;

            return [
                'monitor_name' => $monitor['name'],
                'heartbeats' => is_array($rawHeartbeats) ? $this->normaliseHeartbeats($rawHeartbeats) : [],
            ];
        } catch (Throwable $exception) {
            Log::warning('Backup status: Kuma unavailable.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    private function getJson(string $url): array
    {
        $json = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
            ->acceptJson()
            ->get($url)
            ->throw()
            ->json();

        if (! is_array($json)) {
            throw new UnexpectedValueException('Response from '.$url.' is not a JSON object.');
        }

        return $json;
    }

    /**
     * @param  array<mixed>  $statusPage
     * @return array{id: int, name: string|null}|null
     */
    private function firstMonitor(array $statusPage): ?array
    {
        foreach ($statusPage['publicGroupList'] ?? [] as $group) {
            foreach ($group['monitorList'] ?? [] as $monitor) {
                if (isset($monitor['id']) && is_numeric($monitor['id'])) {
                    return [
                        'id' => (int) $monitor['id'],
                        'name' => isset($monitor['name']) ? (string) $monitor['name'] : null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Keeps only well-formed heartbeats, oldest first, as Kuma returns them.
     *
     * @param  array<mixed>  $rawHeartbeats
     * @return array<int, array{status: int, time: string, ping: float|null}>
     */
    private function normaliseHeartbeats(array $rawHeartbeats): array
    {
        $heartbeats = [];

        foreach ($rawHeartbeats as $heartbeat) {
            if (! is_array($heartbeat) || ! isset($heartbeat['status'], $heartbeat['time']) || ! is_numeric($heartbeat['status'])) {
                continue;
            }

            try {
                CarbonImmutable::parse((string) $heartbeat['time'], 'UTC');
            } catch (Throwable) {
                continue;
            }

            $heartbeats[] = [
                'status' => (int) $heartbeat['status'],
                'time' => (string) $heartbeat['time'],
                'ping' => isset($heartbeat['ping']) && is_numeric($heartbeat['ping']) ? (float) $heartbeat['ping'] : null,
            ];
        }

        return $heartbeats;
    }

    /**
     * Kuma's "ping" field carries the archive size in MiB for this monitor.
     *
     * @param  array{status: int, time: string, ping: float|null}  $heartbeat
     * @return array{state: string, label: string, time: CarbonImmutable, size_gb: string|null, tooltip: string}
     */
    private function presentHeartbeat(array $heartbeat): array
    {
        [$state, $label] = match ($heartbeat['status']) {
            self::STATUS_OK => ['ok', 'OK'],
            self::STATUS_FAILED => ['failed', 'FAILED'],
            self::STATUS_PENDING => ['other', 'PENDING'],
            self::STATUS_MAINTENANCE => ['other', 'MAINTENANCE'],
            default => ['other', 'UNKNOWN'],
        };

        $time = CarbonImmutable::parse($heartbeat['time'], 'UTC')->setTimezone(self::DISPLAY_TIMEZONE);

        $sizeGb = $state === 'ok' && $heartbeat['ping'] !== null
            ? number_format($heartbeat['ping'] / 1024, 1, '.', ' ')
            : null;

        $detail = match (true) {
            $state === 'failed' => 'failed',
            $sizeGb !== null => $sizeGb.' GB',
            default => strtolower($label),
        };

        return [
            'state' => $state,
            'label' => $label,
            'time' => $time,
            'size_gb' => $sizeGb,
            'tooltip' => $time->format('Y-m-d H:i').', '.$detail,
        ];
    }

    private function baseUrl(): ?string
    {
        $url = config('services.backup_status.url');

        return is_string($url) && $url !== '' ? rtrim($url, '/') : null;
    }

    private function slug(): ?string
    {
        $slug = config('services.backup_status.slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }
}
