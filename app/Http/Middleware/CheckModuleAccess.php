<?php

namespace App\Http\Middleware;

use App\Models\DatabaseEntity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * Access rules:
     * 1. Always allow admin and super_admin users
     * 2. If database_entities.is_public == false, only allow users with the specific module role
     * 3. If database_entities.is_public == true, allow all users (including not logged in users)
     *
     * @param  string  $moduleCode  The database_entities.code value (e.g., 'empodat_suspect', 'literature')
     * @param  string  $roleName  The role name required for access (e.g., 'empodat_suspect', 'literature')
     */
    public function handle(Request $request, Closure $next, string $moduleCode, string $roleName): Response
    {
        // Get the database entity configuration
        $databaseEntity = DatabaseEntity::where('code', $moduleCode)->first();

        // If database entity not found, deny access for safety
        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone (including guests)
        if ($databaseEntity->is_public === true) {
            return $next($request);
        }

        // Module is private (is_public == false)
        // Check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module. Please contact an administrator if you believe you should have access.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has the specific module role
        if ($user->hasRole($roleName)) {
            return $next($request);
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module. Please contact an administrator if you believe you should have access.');
    }
}
