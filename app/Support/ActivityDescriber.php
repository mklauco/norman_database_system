<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Backend\UserActivityLog;

/**
 * Turns a logged request into a readable area, a one-sentence description and a query summary.
 */
class ActivityDescriber
{
    /**
     * Route-name prefix => area, checked in order (longest prefixes first).
     *
     * @var array<string, string>
     */
    private const ROUTE_AREAS = [
        'home' => 'Database Modules (home page)',
        'update.dbentities' => 'Database Modules (home page)',
        'dashboard' => 'Dashboard',
        'usage-statistics' => 'Usage Statistics',
        'users' => 'User management',
        'files' => 'Uploaded DCT files',
        'templates' => 'DCT templates',
        'backend.system-settings' => 'System Settings',
        'system-settings' => 'System Settings',
        'querylog' => 'Query log',
        'export_downloads' => 'My downloads',
        'profile' => 'Profile',
        'apiresources' => 'API tokens',
        'projects' => 'Projects',
        'login' => 'Authentication',
        'logout' => 'Authentication',
        'register' => 'Authentication',
        'password' => 'Authentication',
        'verification' => 'Authentication',
        'backend.display' => 'Display settings',
        'backend.empodat.stations' => 'EMPODAT stations',
        'backend.notifications' => 'Notifications',
        'backend.server-payments' => 'Server payments',
        'backend.user-login-retention' => 'Login retention',
        'general_route' => 'General routes',
        'biota_sexs' => 'Literature reference lists',
        'common_names' => 'Literature reference lists',
        'concentration_units' => 'Literature reference lists',
        'habitat_types' => 'Literature reference lists',
        'life_stages' => 'Literature reference lists',
        'species' => 'Literature reference lists',
        'tissues' => 'Literature reference lists',
        'type_of_numeric_quantities' => 'Literature reference lists',
        'use_categories' => 'Literature reference lists',
        'telescope' => 'Telescope',
        'test' => 'Test pages',
    ];

    /**
     * Query keys that carry no information about what was asked for.
     *
     * @var list<string>
     */
    private const IGNORED_QUERY_KEYS = ['page', 'per_page', 'sort', 'direction', '_token', 'displayOption'];

    /**
     * @param  UserActivityLog|array<string, mixed>  $row
     * @param  array<string, string>  $moduleLabels  module code => label
     * @return array{area: string, description: string, details: ?string}
     */
    public function describe(UserActivityLog|array $row, array $moduleLabels): array
    {
        $data = $row instanceof UserActivityLog ? $row->getAttributes() : $row;
        $meta = $data['meta'] ?? null;
        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }
        $meta = is_array($meta) ? $meta : [];

        $module = $this->str($data['module'] ?? null);
        $route = $this->str($data['route_name'] ?? null);
        $path = '/'.ltrim((string) ($data['path'] ?? ''), '/');
        $action = (string) ($data['action'] ?? '');
        $channel = (string) ($data['channel'] ?? ($action === 'api_call' ? 'api' : 'web'));
        $method = strtoupper((string) ($data['method'] ?? 'GET'));

        $params = is_array($meta['params'] ?? null)
            ? $meta['params']
            : array_filter($meta, fn (mixed $v, mixed $k): bool => $k !== 'query' && $k !== 'params' && is_scalar($v), ARRAY_FILTER_USE_BOTH);
        $query = is_array($meta['query'] ?? null) ? $meta['query'] : [];

        $area = $this->area($module, $route, $path, $action, $channel, $moduleLabels);
        $description = $this->description($action, $route, $path, $method, $params);

        $details = in_array($action, ['search', 'api_call', 'download'], true) ? $this->summariseQuery($query) : null;

        return ['area' => $area, 'description' => $description, 'details' => $details];
    }

    /**
     * @param  array<string, string>  $moduleLabels
     */
    private function area(?string $module, ?string $route, string $path, string $action, string $channel, array $moduleLabels): string
    {
        if ($module !== null) {
            return $moduleLabels[$module] ?? $this->humanise($module);
        }
        if ($channel === 'api' || $action === 'api_call') {
            return 'API';
        }
        if ($action === 'login' || $action === 'logout') {
            return 'Authentication';
        }

        if ($route !== null) {
            $best = null;
            foreach (self::ROUTE_AREAS as $prefix => $label) {
                if (($route === $prefix || str_starts_with($route, $prefix.'.')) && ($best === null || strlen($prefix) > strlen($best))) {
                    $best = $prefix;
                }
            }
            if ($best !== null) {
                return self::ROUTE_AREAS[$best];
            }

            $segments = explode('.', $route);
            if ($segments[0] === 'backend' && isset($segments[1])) {
                return 'Backend › '.$this->humanise($segments[1]);
            }

            return $this->humanise($segments[0]);
        }

        $parts = array_values(array_filter(explode('/', $path), fn (string $p): bool => $p !== '' && ! ctype_digit($p)));
        if ($parts === []) {
            return 'Database Modules (home page)';
        }

        return implode(' › ', array_map(fn (string $p): string => $this->humanise($p), array_slice($parts, 0, 2)));
    }

    /**
     * @param  array<array-key, mixed>  $params
     */
    private function description(string $action, ?string $route, string $path, string $method, array $params): string
    {
        if ($action === 'api_call') {
            return 'API call: '.$method.' '.$path;
        }
        if ($action === 'login') {
            return 'Logged in';
        }
        if ($action === 'logout') {
            return 'Logged out';
        }

        $exact = [
            'home' => 'Opened the database modules page',
            'dashboard' => 'Opened the dashboard',
            'login' => 'Opened the login page',
            'register' => 'Opened the registration page',
            'usage-statistics.index' => 'Viewed usage statistics',
            'usage-statistics.activity' => 'Viewed user activity',
            'usage-statistics.activity.user' => 'Viewed activity of one user',
            'usage-statistics.activity.export' => 'Exported user activity to XLSX',
            'usage-statistics.users' => 'Viewed usage per user',
            'usage-statistics.users.show' => 'Viewed usage of one user',
            'usage-statistics.guest' => 'Viewed guest usage',
            'usage-statistics.logins' => 'Viewed logins',
            'usage-statistics.export' => 'Exported usage statistics to XLSX',
            'codsearch.filter' => 'Opened the search form',
            'codsearch.search' => 'Ran a search',
            'export_downloads.index' => 'Opened my downloads',
            'profile.edit' => 'Opened the profile',
            'profile.update' => 'Updated the profile',
            'backend.system-settings.index' => 'Opened system settings',
            'backend.system-settings.maintenance' => 'Opened maintenance settings',
        ];
        if ($route !== null && isset($exact[$route])) {
            return $exact[$route];
        }

        $segments = $route === null ? [] : explode('.', $route);
        $last = $segments === [] ? '' : (string) end($segments);

        if ($action === 'download') {
            return 'Downloaded / started an export';
        }
        if ($action === 'search') {
            return 'Ran a search';
        }
        if ($action === 'record_view') {
            $id = $this->recordId($params);

            return $id === null ? 'Opened a record' : 'Opened record #'.$id;
        }
        if ($segments !== [] && (str_ends_with($segments[0], 'home') || ($segments[1] ?? '') === 'home')) {
            return 'Opened the module home page';
        }
        if ($last === 'filter') {
            return 'Opened the search form';
        }

        if ($route === null) {
            return 'Opened '.$path;
        }

        $context = count($segments) > 1 ? $this->humanise($segments[count($segments) - 2]) : $this->humanise($segments[0]);
        $verbs = [
            'index' => 'Opened the list',
            'create' => 'Opened the create form',
            'store' => 'Saved a new item',
            'edit' => 'Opened the edit form',
            'update' => 'Updated an item',
            'destroy' => 'Deleted an item',
            'show' => 'Opened an item',
        ];
        if (isset($verbs[$last])) {
            return $verbs[$last].' ('.$context.')';
        }

        return $this->humanise($last).' ('.$this->humanise($segments[0]).')';
    }

    /**
     * @param  array<array-key, mixed>  $params
     */
    private function recordId(array $params): ?string
    {
        if ($params === []) {
            return null;
        }
        foreach (['id', 'record', 'code'] as $key) {
            if (isset($params[$key]) && is_scalar($params[$key]) && $params[$key] !== '') {
                return (string) $params[$key];
            }
        }
        $values = array_values(array_filter($params, fn (mixed $v): bool => is_scalar($v) && $v !== ''));

        return $values === [] ? null : (string) end($values);
    }

    /**
     * @param  array<array-key, mixed>  $query
     */
    private function summariseQuery(array $query): ?string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            $key = (string) $key;
            if (in_array($key, self::IGNORED_QUERY_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $items = array_values(array_filter($value, fn (mixed $v): bool => is_scalar($v) && trim((string) $v) !== ''));
                if ($items === []) {
                    continue;
                }
                $short = count($items) <= 3 && collect($items)->every(fn (mixed $v): bool => mb_strlen((string) $v) <= 20);
                $parts[] = $key.': '.($short ? implode(', ', array_map('strval', $items)) : count($items).' selected');

                continue;
            }
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            $parts[] = $key.': '.mb_substr((string) $value, 0, 60);
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    private function str(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function humanise(string $value): string
    {
        $value = trim(str_replace(['_', '-'], ' ', $value));

        return $value === '' ? '' : mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }
}
