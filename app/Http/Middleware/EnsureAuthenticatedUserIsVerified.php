<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every web request from a logged-in user whose email address is not
 * verified yet, except the routes needed to verify, fix the address or log out.
 * Guests pass through untouched, so public pages stay public.
 */
class EnsureAuthenticatedUserIsVerified
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROUTES = [
        'verification.notice',
        'verification.verify',
        'verification.send',
        'logout',
        'profile.edit',
        'profile.update',
        'profile.destroy',
    ];

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user instanceof MustVerifyEmail
            || $user->hasVerifiedEmail()
            || $request->routeIs(...self::ALLOWED_ROUTES)
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your email address is not verified.');
        }

        return Redirect::guest(route('verification.notice'));
    }
}
