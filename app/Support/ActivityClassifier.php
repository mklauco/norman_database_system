<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DatabaseEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Decides whether a request is logged as user activity and derives channel, module and action.
 */
class ActivityClassifier
{
    public const CACHE_KEY = 'activity.module_codes';

    /**
     * Route-name prefix => module code, for prefixes that are not themselves a code.
     *
     * @var array<string, string>
     */
    private const ROUTE_ALIASES = [
        'codsearch' => 'empodat',
        'codhome' => 'empodat',
        'cod' => 'empodat',
        'dctitems' => 'empodat',
        'csv' => 'empodat',
        'substances' => 'susdat',
        'bioassays' => 'bioassay',
    ];

    /**
     * First URL segment => module code, for segments that are not themselves a code.
     *
     * @var array<string, string>
     */
    private const PATH_ALIASES = [
        'bioassays' => 'bioassay',
        'substances' => 'susdat',
    ];

    /**
     * @var list<string>
     */
    private const SKIPPED_PATH_PATTERNS = [
        'livewire/*', '_debugbar*', 'telescope*', 'horizon*', 'sanctum/csrf-cookie',
        'up', 'build/*', 'storage/*', '_ignition/*',
    ];

    /**
     * Routes fired by the page itself (timing beacon), not by a user decision.
     *
     * @var list<string>
     */
    private const SKIPPED_ROUTE_NAMES = ['codsearch.timing'];

    /**
     * @var list<string>|null
     */
    private static ?array $moduleCodes = null;

    public function shouldSkip(Request $request): bool
    {
        if (in_array($request->method(), ['OPTIONS', 'HEAD'], true)) {
            return true;
        }

        $route = $request->route();
        if ($route === null) {
            return true;
        }

        $name = (string) $route->getName();
        $isApi = $this->isApi($request);

        if ($name === '' && ! $isApi && ! $this->isLoginPost($request)) {
            return true;
        }

        if ($name !== '' && (str_starts_with($name, 'livewire.') || str_contains($name, '.livewire.')
            || str_starts_with($name, 'debugbar.') || str_starts_with($name, 'telescope')
            || str_starts_with($name, 'horizon') || str_starts_with($name, 'ignition.')
            || in_array($name, self::SKIPPED_ROUTE_NAMES, true))) {
            return true;
        }

        return $request->is(...self::SKIPPED_PATH_PATTERNS);
    }

    /**
     * @return array{channel: string, module: ?string, action: string, route_name: ?string}
     */
    public function classify(Request $request): array
    {
        $name = $request->route()?->getName();
        $name = ($name === null || $name === '') ? null : $name;
        $isApi = $this->isApi($request);

        return [
            'channel' => $isApi ? 'api' : 'web',
            'module' => $this->moduleFor($request, $name),
            'action' => $this->actionFor($request, $name, $isApi),
            'route_name' => $name,
        ];
    }

    /**
     * The POST login route is unnamed (the named 'login' route is the GET form).
     */
    public function isLoginPost(Request $request): bool
    {
        return $request->isMethod('POST') && $request->path() === 'login';
    }

    public function isApi(Request $request): bool
    {
        return $request->is('api/*');
    }

    public function moduleFor(Request $request, ?string $routeName): ?string
    {
        $segments = explode('/', trim($request->path(), '/'));
        if (($segments[0] ?? '') === 'api') {
            $segments = array_slice($segments, 2);
        }
        $first = $segments[0] ?? '';

        if ($first === 'backend') {
            return null;
        }

        $codes = $this->moduleCodes();

        if ($routeName !== null) {
            $prefix = $this->normalise(explode('.', $routeName)[0]);
            $prefix = self::ROUTE_ALIASES[$prefix] ?? $prefix;
            $prefix = $this->stripHomeSuffix($prefix, $codes);
            if (in_array($prefix, $codes, true)) {
                return $prefix;
            }
        }

        $first = $this->normalise($first);
        $first = self::PATH_ALIASES[$first] ?? $first;

        return in_array($first, $codes, true) ? $first : null;
    }

    public function actionFor(Request $request, ?string $routeName, bool $isApi): string
    {
        if ($isApi) {
            return 'api_call';
        }

        $method = $request->method();
        $name = strtolower((string) $routeName);

        if ($this->isLoginPost($request)) {
            return 'login';
        }
        if ($name === 'logout') {
            return 'logout';
        }

        $haystack = $name.' '.strtolower($request->path());
        if (str_contains($haystack, 'download') || str_contains($haystack, 'export')) {
            return 'download';
        }
        if (str_ends_with($name, '.show') || str_contains($name, 'record') || str_contains($name, 'detail')) {
            return 'record_view';
        }

        $segments = explode('.', $name);
        $last = end($segments);
        if ($last === 'search' || ($last === 'store' && in_array('search', $segments, true))
            || ($last === 'store' && str_contains($segments[0], 'search'))) {
            return 'search';
        }

        return 'page_view';
    }

    public function tokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof \Laravel\Sanctum\PersonalAccessToken ? (int) $token->getKey() : null;
    }

    /**
     * Route parameters under 'params' and, for GET requests, the sanitised query string under 'query'.
     *
     * @return array{params?: array<string, string>, query?: array<string, mixed>}|null
     */
    public function metaFor(Request $request): ?array
    {
        $params = [];
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $params[(string) $key] = mb_substr((string) $value, 0, 100);
            if (count($params) >= 10) {
                break;
            }
        }

        $meta = [];
        if ($params !== []) {
            $meta['params'] = $params;
        }
        $query = $request->isMethod('GET') ? $this->sanitisedQuery($request->query->all()) : [];
        if ($query !== []) {
            $meta['query'] = $query;
        }

        return $meta === [] ? null : $meta;
    }

    /**
     * At most 30 keys, scalars cut to 100 characters, no password/token keys, JSON size capped at 4 KB.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<string, mixed>
     */
    private function sanitisedQuery(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            $key = (string) $key;
            $lower = strtolower($key);
            if (str_contains($lower, 'password') || str_contains($lower, 'token')) {
                continue;
            }
            if (is_array($value)) {
                $items = [];
                foreach ($value as $item) {
                    if (is_scalar($item)) {
                        $items[] = mb_substr((string) $item, 0, 100);
                    }
                    if (count($items) >= 50) {
                        break;
                    }
                }
                $clean = $items;
            } elseif (is_scalar($value)) {
                $clean = mb_substr((string) $value, 0, 100);
            } else {
                continue;
            }

            $candidate = $out + [mb_substr($key, 0, 60) => $clean];
            if (strlen((string) json_encode($candidate)) > 4096) {
                continue;
            }
            $out = $candidate;
            if (count($out) >= 30) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function moduleCodes(): array
    {
        if (self::$moduleCodes !== null) {
            return self::$moduleCodes;
        }

        $codes = Cache::remember(self::CACHE_KEY, 21600, function (): array {
            $normalised = [];
            foreach (DatabaseEntity::query()->pluck('code') as $code) {
                $key = $this->normalise((string) $code);
                if ($key !== '' && ! in_array($key, ['code', 'enpty_code'], true)) {
                    $normalised[$key] = $key;
                }
            }

            return array_values($normalised);
        });

        return self::$moduleCodes = $codes;
    }

    public static function flushCodes(): void
    {
        self::$moduleCodes = null;
    }

    /**
     * Same normalisation as UsageStatisticsService::moduleKeyFor(): text before the first dot, prioritisation_* collapsed.
     */
    private function normalise(string $key): string
    {
        $key = trim($key);
        if (str_starts_with($key, 'prioritisation')) {
            return 'prioritisation';
        }
        $dot = strpos($key, '.');

        return $dot === false ? $key : substr($key, 0, $dot);
    }

    /**
     * @param  list<string>  $codes
     */
    private function stripHomeSuffix(string $prefix, array $codes): string
    {
        if (str_ends_with($prefix, 'home') && in_array(substr($prefix, 0, -4), $codes, true)) {
            return substr($prefix, 0, -4);
        }

        return $prefix;
    }
}
