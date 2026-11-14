<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Jobs\Hazards\FillHazardsComptoxSubstanceDataJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class HazardsComptoxSubstanceDataController extends Controller
{
    public function fill(Request $request): RedirectResponse
    {
        $token = (string) Str::uuid();
        $lockAcquired = Cache::add(
            FillHazardsComptoxSubstanceDataJob::REQUEST_TOKEN_CACHE_KEY,
            $token,
            FillHazardsComptoxSubstanceDataJob::REQUEST_TOKEN_TTL_SECONDS
        );

        if (! $lockAcquired) {
            return back()->with('error', 'Hazards substance data fill is already in progress.');
        }

        try {
            FillHazardsComptoxSubstanceDataJob::dispatch('manual', $token, (int) $request->user()->id);
        } catch (Throwable $e) {
            $currentToken = Cache::get(FillHazardsComptoxSubstanceDataJob::REQUEST_TOKEN_CACHE_KEY);
            if (is_string($currentToken) && hash_equals($currentToken, $token)) {
                Cache::forget(FillHazardsComptoxSubstanceDataJob::REQUEST_TOKEN_CACHE_KEY);
            }
            throw $e;
        }

        return back()->with('success', 'Hazards substance data fill has been queued successfully.');
    }
}
