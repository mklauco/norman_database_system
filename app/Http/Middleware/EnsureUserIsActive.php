<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuts off a user deactivated by an administrator: an open web session is
 * logged out on its next request, and an API token stops working at once.
 * Guests pass through untouched, so public pages stay public.
 */
class EnsureUserIsActive
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->isActive()) {
            return $next($request);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            abort(403, LoginRequest::INACTIVE_ACCOUNT_MESSAGE);
        }

        return redirect()->route('login')->withErrors(['email' => LoginRequest::INACTIVE_ACCOUNT_MESSAGE]);
    }
}
