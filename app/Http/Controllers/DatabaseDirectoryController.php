<?php

namespace App\Http\Controllers;

use App\Models\DatabaseEntity;
use Illuminate\Support\Facades\Auth;

class DatabaseDirectoryController extends Controller
{
    public function index()
    {
        // Get all databases that should be shown in dashboard
        $databases = DatabaseEntity::orderby('id', 'asc')
            ->where('show_in_dashboard', true)
            ->get();

        // Filter databases based on user permissions
        $databases = $databases->filter(function ($database) {
            return $this->canUserAccessModule($database);
        });

        return view('landing.index', [
            'databases' => $databases,
        ]);
    }

    /**
     * Check if the current user can access a specific module
     *
     * Access rules:
     * 1. If module is public (is_public == true), everyone can access
     * 2. If module is private (is_public == false):
     *    - Admin and super_admin users can always access
     *    - Users with the specific module role can access
     *    - Everyone else cannot access
     */
    private function canUserAccessModule(DatabaseEntity $database): bool
    {
        // If module is public, everyone can access
        if ($database->is_public === true) {
            return true;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has the specific module role
        // The role name should match the database code (e.g., 'empodat_suspect', 'literature')
        if ($user->hasRole($database->code)) {
            return true;
        }

        // User doesn't have permission
        return false;
    }
}
