<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\UsageStatisticsRequest;
use App\Models\User;
use App\Services\Backend\UsageStatisticsService;
use Illuminate\Contracts\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UsageStatisticsController extends Controller
{
    public function __construct(private readonly UsageStatisticsService $service) {}

    public function index(UsageStatisticsRequest $request): View
    {
        $year = $request->year();

        return view('backend.usage-statistics.index', [
            'year' => $year,
            'yearLabel' => $request->yearLabel(),
            'years' => $this->service->availableYears(),
            'searches' => $this->service->searchesByMonthAndModule($year),
            'downloads' => $this->service->downloadsByMonthAndModule($year),
        ]);
    }

    public function users(UsageStatisticsRequest $request): View
    {
        $year = $request->year();

        return view('backend.usage-statistics.users', [
            'year' => $year,
            'yearLabel' => $request->yearLabel(),
            'years' => $this->service->availableYears(),
            'usage' => $this->service->userUsage($year),
        ]);
    }

    public function showUser(UsageStatisticsRequest $request, User $user): View
    {
        $year = $request->year();

        return view('backend.usage-statistics.subject', [
            'year' => $year,
            'yearLabel' => $request->yearLabel(),
            'years' => $this->service->availableYears(),
            'subjectName' => trim($user->first_name.' '.$user->last_name),
            'subjectEmail' => $user->email,
            'exportParams' => ['user' => $user->id],
            'searches' => $this->service->searchesByMonthAndModule($year, $user->id),
            'downloads' => $this->service->downloadsByMonthAndModule($year, $user->id),
        ]);
    }

    public function guest(UsageStatisticsRequest $request): View
    {
        $year = $request->year();

        return view('backend.usage-statistics.subject', [
            'year' => $year,
            'yearLabel' => $request->yearLabel(),
            'years' => $this->service->availableYears(),
            'subjectName' => 'Guest',
            'subjectEmail' => null,
            'exportParams' => ['guest' => 1],
            'searches' => $this->service->searchesByMonthAndModule($year, null, true),
            'downloads' => $this->service->downloadsByMonthAndModule($year, null, true),
        ]);
    }

    public function logins(UsageStatisticsRequest $request): View
    {
        $year = $request->year();

        return view('backend.usage-statistics.logins', [
            'year' => $year,
            'yearLabel' => $request->yearLabel(),
            'years' => $this->service->availableYears(),
            'byMonth' => $this->service->loginsByMonth($year),
            'byGeo' => $this->service->loginsByGeoCountry($year),
            'byProfile' => $this->service->loginsByProfileCountry($year),
        ]);
    }

    public function export(UsageStatisticsRequest $request, string $table): BinaryFileResponse
    {
        $year = $request->year();
        $userId = $request->filled('user') ? (int) $request->validated('user') : null;
        $guestsOnly = $request->boolean('guest');

        $rows = match ($table) {
            'searches' => $this->monthModuleRows($this->service->searchesByMonthAndModule($year, $userId, $guestsOnly)),
            'downloads' => $this->monthModuleRows($this->service->downloadsByMonthAndModule($year, $userId, $guestsOnly)),
            'users' => $this->userRows($this->service->userUsage($year)),
            'logins-month' => $this->loginMonthRows($this->service->loginsByMonth($year)),
            'logins-geo' => $this->geoRows($this->service->loginsByGeoCountry($year)),
            'logins-profile' => $this->profileRows($this->service->loginsByProfileCountry($year)),
        };

        $suffix = $userId !== null ? '_user_'.$userId : ($guestsOnly ? '_guest' : '');
        $filename = 'usage_'.str_replace('-', '_', $table).'_'.($year ?? 'all').(in_array($table, ['searches', 'downloads'], true) ? $suffix : '').'.xlsx';

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $tempPath = storage_path('app/temp/'.uniqid('usage_', true).'.xlsx');

        $writer = SimpleExcelWriter::create($tempPath);
        $writer->addRows($rows);
        $writer->close();

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @param  array{modules: array<string,string>, periodName: string, periodLabels: array<int,string>, periods: array<int, array<string,int>>, periodTotals: array<int,int>, moduleTotals: array<string,int>, total: int}  $data
     * @return list<array<string, int|string>>
     */
    private function monthModuleRows(array $data): array
    {
        $rows = [];
        foreach ($data['periods'] as $period => $counts) {
            $row = [$data['periodName'] => $data['periodLabels'][$period]];
            foreach ($data['modules'] as $key => $label) {
                $row[$label] = (int) ($counts[$key] ?? 0);
            }
            $row['Total'] = (int) ($data['periodTotals'][$period] ?? 0);
            $rows[] = $row;
        }
        $total = [$data['periodName'] => 'Total'];
        foreach ($data['modules'] as $key => $label) {
            $total[$label] = (int) ($data['moduleTotals'][$key] ?? 0);
        }
        $total['Total'] = (int) $data['total'];
        $rows[] = $total;

        return $rows;
    }

    /**
     * @param  array{modules: array<string,string>, rows: list<array<string, mixed>>}  $data
     * @return list<array<string, int|string>>
     */
    private function userRows(array $data): array
    {
        $rows = [];
        foreach ($data['rows'] as $r) {
            $row = [
                'Name' => (string) $r['name'],
                'Email' => (string) ($r['email'] ?? ''),
                'Organisation' => (string) ($r['organisation'] ?? ''),
                'Country' => (string) ($r['country'] ?? ''),
            ];
            foreach ($data['modules'] as $key => $label) {
                $row[$label] = (int) ($r['searches'][$key] ?? 0);
            }
            $row['Searches total'] = (int) $r['searches_total'];
            $row['Downloads'] = (int) $r['downloads_total'];
            $row['Logins'] = (int) $r['logins'];
            $row['Last login'] = (string) ($r['last_login'] ?? '');
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array{periodName: string, periodLabels: array<int,string>, periods: array<int, array{logins:int, users:int, ips:int}>, total_logins: int, total_users: int, total_ips: int}  $data
     * @return list<array<string, int|string>>
     */
    private function loginMonthRows(array $data): array
    {
        $rows = [];
        foreach ($data['periods'] as $period => $m) {
            $rows[] = [$data['periodName'] => $data['periodLabels'][$period], 'Logins' => (int) $m['logins'], 'Distinct users' => (int) $m['users'], 'Distinct IPs' => (int) $m['ips']];
        }
        $rows[] = [$data['periodName'] => 'Total', 'Logins' => (int) $data['total_logins'], 'Distinct users' => (int) $data['total_users'], 'Distinct IPs' => (int) $data['total_ips']];

        return $rows;
    }

    /**
     * @param  list<array{country_code: string|null, country: string, logins: int, users: int}>  $data
     * @return list<array<string, int|string>>
     */
    private function geoRows(array $data): array
    {
        return array_map(fn (array $r): array => [
            'Code' => (string) ($r['country_code'] ?? ''),
            'Country' => $r['country'],
            'Logins' => (int) $r['logins'],
            'Users' => (int) $r['users'],
        ], $data);
    }

    /**
     * @param  list<array{country: string, users: int, logins: int}>  $data
     * @return list<array<string, int|string>>
     */
    private function profileRows(array $data): array
    {
        return array_map(fn (array $r): array => [
            'Country' => $r['country'],
            'Users' => (int) $r['users'],
            'Logins' => (int) $r['logins'],
        ], $data);
    }
}
