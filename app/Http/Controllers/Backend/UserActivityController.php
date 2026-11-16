<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\UserActivityTimelineRequest;
use App\Models\Backend\UserActivityLog;
use App\Models\User;
use App\Services\Backend\UserActivityStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserActivityController extends Controller
{
    public function __construct(private readonly UserActivityStatisticsService $service) {}

    public function index(UserActivityTimelineRequest $request): View
    {
        $date = $request->selectedDate();
        $today = $this->service->today();
        $filters = array_filter([
            'user' => $request->selectedUserId(),
            'area' => $request->selectedArea(),
            'action' => $request->selectedAction(),
        ], fn (mixed $v): bool => $v !== null);

        $dayRows = $this->service->describedDay($date);
        $areas = $this->service->areasOf($this->filterRows($dayRows, $request->selectedUserId(), null, null));
        $filtered = $this->filterRows($dayRows, $request->selectedUserId(), $request->selectedArea(), $request->selectedAction());

        $perPage = 200;
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $timeline = (new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        ))->withQueryString();

        $countries = $this->service->countriesForUsers($timeline->getCollection()->pluck('user_id')->unique()->values()->all());

        $day = CarbonImmutable::createFromFormat('Y-m-d', $date, UserActivityStatisticsService::DISPLAY_TIMEZONE);

        return view('backend.usage-statistics.activity', [
            'hideYearPicker' => true,
            'date' => $date,
            'today' => $today,
            'previousDate' => $day->subDay()->format('Y-m-d'),
            'nextDate' => $date < $today ? $day->addDay()->format('Y-m-d') : null,
            'filters' => $filters,
            'options' => $this->service->filterOptions($date) + ['areas' => $areas],
            'timeline' => $timeline,
            'countries' => $countries,
            'actionLabels' => UserActivityStatisticsService::ACTION_LABELS,
        ]);
    }

    /**
     * @param  Collection<int, UserActivityLog>  $rows
     * @return Collection<int, UserActivityLog>
     */
    private function filterRows(Collection $rows, ?int $userId, ?string $area, ?string $action): Collection
    {
        return $rows
            ->when($userId !== null, fn (Collection $c) => $c->filter(fn ($r): bool => (int) $r->user_id === $userId))
            ->when($area !== null, fn (Collection $c) => $c->filter(fn ($r): bool => $r->area === $area))
            ->when($action !== null, fn (Collection $c) => $c->filter(fn ($r): bool => $r->action === $action))
            ->values();
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('usage-statistics.activity', ['user' => $user->id]);
    }

    public function export(UserActivityTimelineRequest $request, string $table): BinaryFileResponse
    {
        $date = $request->selectedDate();
        $userId = $request->selectedUserId();

        $records = $this->filterRows($this->service->describedDay($date), $userId, $request->selectedArea(), $request->selectedAction());

        $rows = [];
        $countries = $this->service->countriesForUsers($records->pluck('user_id')->unique()->values()->all());

        foreach ($records as $r) {
            $rows[] = [
                'Time' => $r->occurred_at->copy()->setTimezone(UserActivityStatisticsService::DISPLAY_TIMEZONE)->format('H:i:s'),
                'User' => trim($r->first_name.' '.$r->last_name),
                'Country' => $countries[$r->user_id]['country'] ?? '',
                'Area' => $r->area,
                'What' => $r->description,
                'Details' => (string) $r->details,
                'Action' => UserActivityStatisticsService::ACTION_LABELS[$r->action] ?? $r->action,
                'Page' => (string) $r->route_name,
                'Path' => $r->path,
                'Method' => $r->method,
                'Status' => $r->status === null ? '' : (string) $r->status,
                'Duration ms' => $r->duration_ms ?? '',
            ];
        }

        $filename = sprintf(
            'nds-usage_%s_%s_%s.xlsx',
            $table,
            $userId !== null ? 'user-'.$userId : 'all-users',
            $date,
        );

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $tempPath = storage_path('app/temp/'.uniqid('usage_', true).'.xlsx');

        $writer = SimpleExcelWriter::create($tempPath);
        $writer->addRows($rows);
        $writer->close();

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
