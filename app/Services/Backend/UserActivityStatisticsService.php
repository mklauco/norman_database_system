<?php

declare(strict_types=1);

namespace App\Services\Backend;

use App\Models\Backend\UserActivityLog;
use App\Models\Backend\UserLoginRetention;
use App\Models\List\Country;
use App\Models\User;
use App\Support\ActivityDescriber;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Daily timeline of logged-in user activity (user_activity_logs).
 */
class UserActivityStatisticsService
{
    public const DISPLAY_TIMEZONE = 'Europe/Bratislava';

    /**
     * @var array<string, string>
     */
    public const ACTION_LABELS = [
        'page_view' => 'Page view',
        'search' => 'Search',
        'record_view' => 'Record view',
        'download' => 'Download',
        'api_call' => 'API call',
        'login' => 'Login',
        'logout' => 'Logout',
    ];

    public function __construct(private readonly UsageStatisticsService $usage) {}

    /**
     * Today's date (Y-m-d) in the display timezone.
     */
    public function today(): string
    {
        return CarbonImmutable::now(self::DISPLAY_TIMEZONE)->format('Y-m-d');
    }

    /**
     * Start and end of $date in the display timezone, expressed in the stored (application) timezone.
     *
     * @return array{0: string, 1: string}
     */
    public function dayBounds(string $date): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $date, self::DISPLAY_TIMEZONE)->startOfDay();
        $end = $start->endOfDay();
        $stored = (string) config('app.timezone');

        return [
            $start->setTimezone($stored)->format('Y-m-d H:i:s'),
            $end->setTimezone($stored)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Users and actions that have at least one row on $date.
     *
     * @return array{users: array<int,string>, actions: array<string,string>}
     */
    public function filterOptions(string $date): array
    {
        [$from, $to] = $this->dayBounds($date);

        $userIds = UserActivityLog::query()->whereBetween('occurred_at', [$from, $to])->distinct()->pluck('user_id')->all();
        $actionKeys = UserActivityLog::query()->whereBetween('occurred_at', [$from, $to])->distinct()->pluck('action')->all();

        $users = [];
        if ($userIds !== []) {
            foreach (User::query()->select(['id', 'first_name', 'last_name', 'email'])->whereIn('id', $userIds)->orderBy('first_name')->orderBy('last_name')->get() as $user) {
                $users[$user->id] = trim($user->first_name.' '.$user->last_name).' ('.$user->email.')';
            }
        }

        $actions = [];
        foreach (self::ACTION_LABELS as $key => $label) {
            if (in_array($key, $actionKeys, true)) {
                $actions[$key] = $label;
            }
        }

        return ['users' => $users, 'actions' => $actions];
    }

    /**
     * Every row of $date (newest first), each with area, description and details attached.
     * A day is small, so area filtering happens in PHP on this collection.
     *
     * @return Collection<int, UserActivityLog>
     */
    public function describedDay(string $date): Collection
    {
        $rows = $this->timelineQuery($date, null, null)->get();
        $labels = $this->usage->moduleLabels(array_values(array_unique(array_filter($rows->pluck('module')->all()))));
        $describer = new ActivityDescriber;

        return $rows->each(function (UserActivityLog $row) use ($describer, $labels): void {
            $d = $describer->describe($row, $labels);
            $row->setAttribute('area', $d['area']);
            $row->setAttribute('description', $d['description']);
            $row->setAttribute('details', $d['details']);
        });
    }

    /**
     * Distinct areas of the given rows, sorted by name.
     *
     * @param  Collection<int, UserActivityLog>  $rows
     * @return list<string>
     */
    public function areasOf(Collection $rows): array
    {
        $areas = $rows->pluck('area')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return array_map('strval', $areas);
    }

    /**
     * Rows of $date (newest first) with the user name joined in.
     *
     * @return Builder<UserActivityLog>
     */
    public function timelineQuery(string $date, ?int $userId, ?string $action): Builder
    {
        [$from, $to] = $this->dayBounds($date);

        return UserActivityLog::query()
            ->join('users', 'users.id', '=', 'user_activity_logs.user_id')
            ->whereBetween('user_activity_logs.occurred_at', [$from, $to])
            ->when($userId !== null, fn (Builder $q) => $q->where('user_activity_logs.user_id', $userId))
            ->when($action !== null, fn (Builder $q) => $q->where('user_activity_logs.action', $action))
            ->select([
                'user_activity_logs.id',
                'user_activity_logs.user_id',
                'user_activity_logs.occurred_at',
                'user_activity_logs.channel',
                'user_activity_logs.module',
                'user_activity_logs.action',
                'user_activity_logs.route_name',
                'user_activity_logs.method',
                'user_activity_logs.path',
                'user_activity_logs.status',
                'user_activity_logs.duration_ms',
                'user_activity_logs.meta',
                'users.first_name',
                'users.last_name',
            ])
            ->orderByDesc('user_activity_logs.occurred_at')
            ->orderByDesc('user_activity_logs.id');
    }

    /**
     * Country per user: the profile country, else the country of the IP address of the latest login.
     *
     * @param  list<int>  $userIds
     * @return array<int, array{country: string, source: 'profile'|'ip'}>
     */
    public function countriesForUsers(array $userIds): array
    {
        $userIds = array_values(array_unique($userIds));
        if ($userIds === []) {
            return [];
        }

        $users = User::query()->select(['id', 'country_id'])->whereIn('id', $userIds)->get()->keyBy('id');

        $countryNames = Country::query()
            ->whereIn('id', $users->pluck('country_id')->filter()->unique()->all())
            ->pluck('name', 'id')
            ->all();

        $ipCodes = UserLoginRetention::query()
            ->selectRaw("distinct on (user_id) user_id, nullif(upper(trim(meta_data->>'country')), '') as code")
            ->whereIn('user_id', $userIds)
            ->orderBy('user_id')
            ->orderByDesc('login_datetime')
            ->pluck('code', 'user_id')
            ->all();

        $codes = array_values(array_unique(array_filter($ipCodes)));
        $ipNames = $codes === [] ? [] : Country::query()->whereIn('code', $codes)->pluck('name', 'code')->all();

        $result = [];
        foreach ($userIds as $uid) {
            $countryId = $users->get($uid)?->country_id;
            if ($countryId !== null && isset($countryNames[$countryId])) {
                $result[$uid] = ['country' => (string) $countryNames[$countryId], 'source' => 'profile'];
            } elseif (($ipCodes[$uid] ?? null) !== null) {
                $code = (string) $ipCodes[$uid];
                $result[$uid] = ['country' => (string) ($ipNames[$code] ?? $code), 'source' => 'ip'];
            }
        }

        return $result;
    }
}
