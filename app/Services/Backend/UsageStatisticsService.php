<?php

declare(strict_types=1);

namespace App\Services\Backend;

use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\Backend\UserLoginRetention;
use App\Models\DatabaseEntity;
use App\Models\List\Country;
use App\Models\List\DataSourceOrganisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aggregated usage statistics (searches, downloads, logins) computed in SQL.
 */
class UsageStatisticsService
{
    public const GUEST_KEY = 'guest';

    /**
     * Module key for a raw database_key: part before the first '.', any 'prioritisation_*' becomes 'prioritisation', null or '' becomes 'unknown'.
     */
    public function moduleKeyFor(?string $databaseKey): string
    {
        $key = trim((string) $databaseKey);

        if ($key === '') {
            return 'unknown';
        }

        if (str_starts_with($key, 'prioritisation')) {
            return 'prioritisation';
        }

        $dot = strpos($key, '.');
        if ($dot !== false) {
            $key = substr($key, 0, $dot);
        }

        return $key === '' ? 'unknown' : $key;
    }

    /**
     * @param  list<string>  $moduleKeys
     * @return array<string, string> module key => label, ordered by label
     */
    public function moduleLabels(array $moduleKeys): array
    {
        $moduleKeys = array_values(array_unique($moduleKeys));

        $names = $moduleKeys === []
            ? []
            : DatabaseEntity::query()
                ->whereIn('code', $moduleKeys)
                ->orderBy('id')
                ->pluck('name', 'code')
                ->all();

        $labels = [];
        foreach ($moduleKeys as $key) {
            $labels[$key] = match (true) {
                $key === 'unknown' => 'Unknown',
                isset($names[$key]) && $names[$key] !== '' => (string) $names[$key],
                default => ucfirst($key),
            };
        }

        uasort($labels, fn (string $a, string $b): int => strcasecmp($a, $b));

        return $labels;
    }

    /**
     * Years with any activity in query_logs, user_login_retentions or export_downloads, plus the current year.
     *
     * @return list<int>
     */
    public function availableYears(): array
    {
        $years = [(int) now()->year];

        $sources = [
            [QueryLog::query(), 'created_at'],
            [UserLoginRetention::query(), 'login_datetime'],
            [ExportDownload::query(), 'created_at'],
        ];

        foreach ($sources as [$query, $column]) {
            $rows = $query
                ->selectRaw("distinct date_part('year', {$column}) as y")
                ->whereNotNull($column)
                ->pluck('y');

            foreach ($rows as $year) {
                $years[] = (int) $year;
            }
        }

        $years = array_values(array_unique($years));
        rsort($years);

        return $years;
    }

    /**
     * Searches in $year (null = all years) grouped by month (or by year when $year is null) and module. $userId null = everyone; when $guestsOnly is true only user_id IS NULL rows.
     *
     * @return array{modules: array<string,string>, periodName: string, periodLabels: array<int,string>, periods: array<int, array<string,int>>, periodTotals: array<int,int>, moduleTotals: array<string,int>, total: int}
     */
    public function searchesByMonthAndModule(?int $year, ?int $userId = null, bool $guestsOnly = false): array
    {
        return $this->byMonthAndModule(QueryLog::query(), $year, $userId, $guestsOnly);
    }

    /**
     * Downloads in $year (null = all years) grouped by month (or by year when $year is null) and module. Every export_downloads row is counted except those with status 'failed'
     * (completed and still processing exports count as usage).
     *
     * @return array{modules: array<string,string>, periodName: string, periodLabels: array<int,string>, periods: array<int, array<string,int>>, periodTotals: array<int,int>, moduleTotals: array<string,int>, total: int}
     */
    public function downloadsByMonthAndModule(?int $year, ?int $userId = null, bool $guestsOnly = false): array
    {
        return $this->byMonthAndModule($this->downloadsQuery(), $year, $userId, $guestsOnly);
    }

    /**
     * One row per user with any search, download or login in $year, plus one guest row when guests searched or downloaded.
     *
     * @return array{modules: array<string,string>, rows: list<array{user_id: int|null, is_guest: bool, name: string, email: string|null, organisation: string|null, country: string|null, searches: array<string,int>, searches_total: int, downloads_total: int, logins: int, last_login: string|null}>}
     */
    public function userUsage(?int $year): array
    {
        $searchRows = QueryLog::query()
            ->selectRaw('user_id, database_key, count(*) as c')
            ->tap(fn (Builder $q) => $this->applyYear($q, 'created_at', $year))
            ->groupBy('user_id', 'database_key')
            ->get();

        $downloadRows = $this->downloadsQuery()
            ->selectRaw('user_id, count(*) as c')
            ->tap(fn (Builder $q) => $this->applyYear($q, 'created_at', $year))
            ->groupBy('user_id')
            ->get();

        $loginRows = UserLoginRetention::query()
            ->selectRaw('user_id, count(*) as c')
            ->tap(fn (Builder $q) => $this->applyYear($q, 'login_datetime', $year))
            ->groupBy('user_id')
            ->get();

        $searches = [];
        $moduleKeys = [];
        foreach ($searchRows as $row) {
            $uid = $row->user_id === null ? self::GUEST_KEY : (string) $row->user_id;
            $module = $this->moduleKeyFor($row->database_key);
            $moduleKeys[$module] = $module;
            $searches[$uid][$module] = ($searches[$uid][$module] ?? 0) + (int) $row->c;
        }

        $downloads = [];
        foreach ($downloadRows as $row) {
            $downloads[$row->user_id === null ? self::GUEST_KEY : (string) $row->user_id] = (int) $row->c;
        }

        $logins = [];
        foreach ($loginRows as $row) {
            $logins[(string) $row->user_id] = (int) $row->c;
        }

        $modules = $this->moduleLabels(array_values($moduleKeys));

        $userIds = array_values(array_filter(
            array_unique(array_merge(array_keys($searches), array_keys($downloads), array_keys($logins))),
            fn (int|string $id): bool => (string) $id !== self::GUEST_KEY,
        ));

        $users = $userIds === []
            ? collect()
            : User::query()
                ->select(['id', 'first_name', 'last_name', 'email', 'organisation_id', 'organisation_other', 'country_id'])
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

        $organisationNames = DataSourceOrganisation::query()
            ->whereIn('id', $users->pluck('organisation_id')->filter()->unique()->all())
            ->pluck('name', 'id')
            ->all();

        $countryNames = Country::query()
            ->whereIn('id', $users->pluck('country_id')->filter()->unique()->all())
            ->pluck('name', 'id')
            ->all();

        $lastLogins = $userIds === []
            ? []
            : UserLoginRetention::query()
                ->selectRaw('user_id, max(login_datetime) as last_login')
                ->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->pluck('last_login', 'user_id')
                ->all();

        $allKeys = $userIds;
        if (isset($searches[self::GUEST_KEY]) || isset($downloads[self::GUEST_KEY])) {
            $allKeys[] = self::GUEST_KEY;
        }

        $rows = [];
        foreach ($allKeys as $key) {
            $key = (string) $key;
            $isGuest = $key === self::GUEST_KEY;
            $user = $isGuest ? null : $users->get((int) $key);

            $perModule = [];
            foreach (array_keys($modules) as $module) {
                $perModule[$module] = $searches[$key][$module] ?? 0;
            }

            $lastLogin = $isGuest ? null : ($lastLogins[(int) $key] ?? null);

            $rows[] = [
                'user_id' => $isGuest ? null : (int) $key,
                'is_guest' => $isGuest,
                'name' => $isGuest ? 'Guest' : ($user !== null ? trim($user->first_name.' '.$user->last_name) : 'User #'.$key),
                'email' => $user?->email,
                'organisation' => $user === null ? null : ($organisationNames[$user->organisation_id] ?? ($user->organisation_other ?: null)),
                'country' => $user === null ? null : ($countryNames[$user->country_id] ?? null),
                'searches' => $perModule,
                'searches_total' => array_sum($perModule),
                'downloads_total' => $downloads[$key] ?? 0,
                'logins' => $logins[$key] ?? 0,
                'last_login' => $lastLogin !== null ? substr((string) $lastLogin, 0, 16) : null,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$b['searches_total'], $a['name']] <=> [$a['searches_total'], $b['name']]);

        return ['modules' => $modules, 'rows' => $rows];
    }

    /**
     * By month for a given $year, by year when $year is null. users = distinct user_id in that period; total_users = distinct over the whole range.
     *
     * @return array{periodName: string, periodLabels: array<int,string>, periods: array<int, array{logins:int, users:int}>, total_logins: int, total_users: int}
     */
    public function loginsByMonth(?int $year): array
    {
        $groupExpr = $year === null ? "date_part('year', login_datetime)" : "date_part('month', login_datetime)";

        $rows = UserLoginRetention::query()
            ->selectRaw("{$groupExpr} as p, count(*) as logins, count(distinct user_id) as users")
            ->tap(fn (Builder $q) => $this->applyYear($q, 'login_datetime', $year))
            ->groupByRaw($groupExpr)
            ->get();

        [$periodName, $periodLabels] = $this->periodAxis($year, $rows->pluck('p')->map(fn ($p): int => (int) $p)->all());

        $periods = [];
        foreach (array_keys($periodLabels) as $key) {
            $periods[$key] = ['logins' => 0, 'users' => 0];
        }

        $totalLogins = 0;
        foreach ($rows as $row) {
            $periods[(int) $row->p] = ['logins' => (int) $row->logins, 'users' => (int) $row->users];
            $totalLogins += (int) $row->logins;
        }

        $totalUsers = (int) UserLoginRetention::query()
            ->tap(fn (Builder $q) => $this->applyYear($q, 'login_datetime', $year))
            ->distinct()
            ->count('user_id');

        return [
            'periodName' => $periodName,
            'periodLabels' => $periodLabels,
            'periods' => $periods,
            'total_logins' => $totalLogins,
            'total_users' => $totalUsers,
        ];
    }

    /**
     * Logins in $year grouped by IP-geolocated country (meta_data->>'country'); unmatched or missing codes are labelled 'Unknown'.
     *
     * @return list<array{country_code: string|null, country: string, logins: int, users: int}>
     */
    public function loginsByGeoCountry(?int $year): array
    {
        $rows = UserLoginRetention::query()
            ->selectRaw("nullif(upper(trim(meta_data->>'country')), '') as code, count(*) as logins, count(distinct user_id) as users")
            ->tap(fn (Builder $q) => $this->applyYear($q, 'login_datetime', $year))
            ->groupByRaw("nullif(upper(trim(meta_data->>'country')), '')")
            ->get();

        $codes = $rows->pluck('code')->filter()->values()->all();
        $names = $codes === []
            ? []
            : Country::query()->whereIn('code', $codes)->pluck('name', 'code')->all();

        $result = [];
        foreach ($rows as $row) {
            $code = $row->code === null ? null : (string) $row->code;
            $result[] = [
                'country_code' => $code,
                'country' => $code === null ? 'Unknown' : (string) ($names[$code] ?? $code),
                'logins' => (int) $row->logins,
                'users' => (int) $row->users,
            ];
        }

        usort($result, fn (array $a, array $b): int => [$b['logins'], $a['country']] <=> [$a['logins'], $b['country']]);

        return $result;
    }

    /**
     * Users who logged in during $year grouped by their profile country; users without a country are labelled 'Not set'.
     *
     * @return list<array{country: string, users: int, logins: int}>
     */
    public function loginsByProfileCountry(?int $year): array
    {
        $rows = UserLoginRetention::query()
            ->join('users', 'users.id', '=', 'user_login_retentions.user_id')
            ->leftJoin('list_countries', 'list_countries.id', '=', 'users.country_id')
            ->selectRaw('list_countries.name as country, count(*) as logins, count(distinct user_login_retentions.user_id) as users')
            ->tap(fn (Builder $q) => $this->applyYear($q, 'user_login_retentions.login_datetime', $year))
            ->groupBy('list_countries.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'country' => $row->country === null ? 'Not set' : (string) $row->country,
                'users' => (int) $row->users,
                'logins' => (int) $row->logins,
            ];
        }

        usort($result, fn (array $a, array $b): int => [$b['users'], $a['country']] <=> [$a['users'], $b['country']]);

        return $result;
    }

    /**
     * @return Builder<ExportDownload>
     */
    private function downloadsQuery(): Builder
    {
        return ExportDownload::query()->where('status', '!=', 'failed');
    }

    /**
     * Restricts $query to the calendar year (index-friendly range); no-op when $year is null.
     *
     * @param  Builder<*>  $query
     */
    private function applyYear(Builder $query, string $column, ?int $year): void
    {
        if ($year === null) {
            return;
        }

        $query->whereBetween($column, [sprintf('%04d-01-01 00:00:00', $year), sprintf('%04d-12-31 23:59:59', $year)]);
    }

    /**
     * Period axis: months of $year, or every available year (ascending, plus any in $extraYears) when $year is null.
     *
     * @param  list<int>  $extraKeys
     * @return array{0: string, 1: array<int, string>}
     */
    private function periodAxis(?int $year, array $extraKeys): array
    {
        if ($year !== null) {
            $labels = [];
            for ($m = 1; $m <= 12; $m++) {
                $labels[$m] = date('F', mktime(0, 0, 0, $m, 1));
            }

            return ['Month', $labels];
        }

        $years = array_values(array_unique(array_merge($this->availableYears(), $extraKeys)));
        sort($years);

        $labels = [];
        foreach ($years as $y) {
            $labels[$y] = (string) $y;
        }

        return ['Year', $labels];
    }

    /**
     * @param  Builder<QueryLog>|Builder<ExportDownload>  $query
     * @return array{modules: array<string,string>, periodName: string, periodLabels: array<int,string>, periods: array<int, array<string,int>>, periodTotals: array<int,int>, moduleTotals: array<string,int>, total: int}
     */
    private function byMonthAndModule(Builder $query, ?int $year, ?int $userId, bool $guestsOnly): array
    {
        $this->applyYear($query, 'created_at', $year);

        if ($guestsOnly) {
            $query->whereNull('user_id');
        } elseif ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $groupExpr = $year === null ? "date_part('year', created_at)" : "date_part('month', created_at)";

        $rows = $query
            ->selectRaw("{$groupExpr} as p, database_key, count(*) as c")
            ->groupByRaw("{$groupExpr}, database_key")
            ->get();

        $counts = [];
        $moduleKeys = [];
        $seenPeriods = [];
        foreach ($rows as $row) {
            $module = $this->moduleKeyFor($row->database_key);
            $moduleKeys[$module] = $module;
            $p = (int) $row->p;
            $seenPeriods[] = $p;
            $counts[$p][$module] = ($counts[$p][$module] ?? 0) + (int) $row->c;
        }

        $modules = $this->moduleLabels(array_values($moduleKeys));
        [$periodName, $periodLabels] = $this->periodAxis($year, $seenPeriods);

        $periods = [];
        $periodTotals = [];
        $moduleTotals = array_fill_keys(array_keys($modules), 0);
        $total = 0;

        foreach (array_keys($periodLabels) as $p) {
            $periods[$p] = [];
            $periodTotals[$p] = 0;
            foreach (array_keys($modules) as $module) {
                $value = $counts[$p][$module] ?? 0;
                $periods[$p][$module] = $value;
                $periodTotals[$p] += $value;
                $moduleTotals[$module] += $value;
                $total += $value;
            }
        }

        return [
            'modules' => $modules,
            'periodName' => $periodName,
            'periodLabels' => $periodLabels,
            'periods' => $periods,
            'periodTotals' => $periodTotals,
            'moduleTotals' => $moduleTotals,
            'total' => $total,
        ];
    }
}
