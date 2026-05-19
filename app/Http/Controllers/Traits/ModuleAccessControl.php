<?php

namespace App\Http\Controllers\Traits;

use App\Models\DatabaseEntity;
use Illuminate\Support\Facades\Auth;

trait ModuleAccessControl
{
    /**
     * Check if the current user has access to a specific module based on database_entities settings.
     *
     * Access rules:
     * 1. Always allow admin and super_admin users
     * 2. If database_entities.is_public == false, only allow users with the specific module role
     * 3. If database_entities.is_public == true, allow all users (including not logged in users)
     *
     * @param  string  $moduleCode  The database_entities.code value (e.g., 'empodat_suspect', 'literature')
     * @param  string  $roleName  The role name required for access (e.g., 'empodat_suspect', 'literature')
     */
    protected function checkModuleAccess(string $moduleCode, string $roleName): bool
    {
        // Get the database entity configuration
        $databaseEntity = DatabaseEntity::where('code', $moduleCode)->first();

        // If database entity not found, deny access for safety
        if (! $databaseEntity) {
            return false;
        }

        // If module is public, allow access to everyone (including guests)
        if ($databaseEntity->is_public === true) {
            return true;
        }

        // Module is private (is_public == false)
        // Check if user is logged in
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has the specific module role
        return $user->hasRole($roleName);
    }

    /**
     * Abort with 403 error if user doesn't have access to the module.
     *
     * @param  string  $moduleCode  The database_entities.code value
     * @param  string  $roleName  The role name required for access
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeModuleAccess(string $moduleCode, string $roleName): void
    {
        if (! $this->checkModuleAccess($moduleCode, $roleName)) {
            abort(403, 'You do not have permission to access this module. Please contact an administrator if you believe you should have access.');
        }
    }
}
