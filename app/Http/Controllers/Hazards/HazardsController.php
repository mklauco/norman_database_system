<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Jobs\Hazards\FetchHazardsDataJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class HazardsController extends Controller
{
    /**
     * Dispatch hazards API fetch as a background job.
     */
    public function fetch(Request $request): RedirectResponse
    {
        $token = (string) Str::uuid();
        $lockAcquired = Cache::add(
            FetchHazardsDataJob::REQUEST_TOKEN_CACHE_KEY,
            $token,
            FetchHazardsDataJob::REQUEST_TOKEN_TTL_SECONDS
        );

        if (! $lockAcquired) {
            return back()->with('error', 'Hazards API fetch is already in progress.');
        }

        try {
            FetchHazardsDataJob::dispatch('manual', $token);
        } catch (Throwable $e) {
            $currentToken = Cache::get(FetchHazardsDataJob::REQUEST_TOKEN_CACHE_KEY);
            if (is_string($currentToken) && hash_equals($currentToken, $token)) {
                Cache::forget(FetchHazardsDataJob::REQUEST_TOKEN_CACHE_KEY);
            }
            throw $e;
        }

        return back()->with('success', 'Hazards API fetch has been queued successfully.');
    }
}
