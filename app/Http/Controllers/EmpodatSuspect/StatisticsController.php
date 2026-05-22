<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use App\Jobs\EmpodatSuspect\GenerateEmpodatSuspectStatisticsJob;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the EmpodatSuspect module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // Check if user has the specific module role
        if ($user->hasRole('empodat_suspect')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Display statistics overview page
     */
    public function index()
    {
        // Get empodat_suspect database entity
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();
        $allStats = [];

        if ($empodatSuspectEntity) {
            // Get all unique statistic keys for this entity
            $statisticKeys = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $empodatSuspectEntity->number_of_records ?? 0;

        return view('empodat_suspect.statistics.index', [
            'empodatSuspectEntity' => $empodatSuspectEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Queue the statistics generation job.
     *
     * The work runs against ~34M partitioned rows and used to block the HTTP
     * request for many minutes. It now dispatches a ShouldQueue job; the job is
     * unique by key, so double-clicks and concurrent runs collapse into one.
     */
    public function generateStatistics()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        GenerateEmpodatSuspectStatisticsJob::dispatch();

        return back()->with('success', 'Statistics generation queued — typically takes up to 5 minutes. Refresh this page to see the new "Updated:" timestamps on each card.');
    }

    /**
     * Display substances by sample code statistics
     */
    public function substancesBySampleCode()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_sample_code')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.substances_by_sample_code', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.substances_by_sample_code', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSampleCodes' => $statisticsRecord->meta_data['total_sample_codes'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by sample code statistics
     */
    public function recordsBySampleCode()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_sample_code')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_sample_code', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_sample_code', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSampleCodes' => $statisticsRecord->meta_data['total_sample_codes'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display substances by country statistics
     */
    public function substancesByCountry()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.substances_by_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.substances_by_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function recordsByCountry()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by confidence interval statistics
     */
    public function recordsByConfidenceInterval()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_confidence_interval')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_confidence_interval', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_confidence_interval', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalWithIpMax' => $statisticsRecord->meta_data['total_with_ip_max'] ?? 0,
            'totalWithoutIpMax' => $statisticsRecord->meta_data['total_without_ip_max'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
