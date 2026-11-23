<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Backend\UserActivityLog;
use App\Support\ActivityClassifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records one row per request made by an authenticated user, after the response is sent.
 */
class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('activity_started_at', microtime(true));

        if (! $request->is('api/*')) {
            $request->attributes->set('activity_user_id', $request->user()?->getAuthIdentifier());
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $userId = $request->user()?->getAuthIdentifier() ?? $request->attributes->get('activity_user_id');
            if ($userId === null) {
                return;
            }

            $classifier = new ActivityClassifier;
            if ($classifier->shouldSkip($request)) {
                return;
            }

            $classified = $classifier->classify($request);
            $startedAt = $request->attributes->get('activity_started_at');
            $meta = $classifier->metaFor($request);

            UserActivityLog::query()->insert([
                'user_id' => $userId,
                'occurred_at' => now(),
                'channel' => $classified['channel'],
                'module' => $classified['module'],
                'action' => $classified['action'],
                'route_name' => $classified['route_name'] === null ? null : mb_substr($classified['route_name'], 0, 191),
                'method' => mb_substr($request->method(), 0, 8),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500),
                'status' => $response->getStatusCode(),
                'duration_ms' => is_float($startedAt) ? (int) round((microtime(true) - $startedAt) * 1000) : null,
                'token_id' => $classifier->tokenId($request),
                'meta' => $meta === null ? null : json_encode($meta),
            ]);
        } catch (Throwable $e) {
            Log::warning('User activity logging failed: '.$e->getMessage());
        }
    }
}
