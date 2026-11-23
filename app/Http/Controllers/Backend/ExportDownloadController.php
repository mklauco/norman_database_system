<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExportDownloadController extends Controller
{
    /**
     * List export downloads. A super_admin sees the latest downloads of all users
     * (guests included) unless a user is picked; everyone else sees only their own.
     */
    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->hasRole('super_admin');

        $usersWithExports = collect();
        if ($isSuperAdmin) {
            $usersWithExports = User::query()
                ->whereHas('exportDownloads')
                ->select('id', 'first_name', 'last_name', 'email')
                ->withCount('exportDownloads')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        $userId = $isSuperAdmin ? ($request->integer('user_id') ?: null) : $currentUser->id;
        $allUsers = $isSuperAdmin && $userId === null;

        $exportDownloads = ExportDownload::query()
            ->with('user:id,first_name,last_name,email')
            ->when(! $allUsers, fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        $user = $userId !== null ? User::find($userId) : null;

        return view('backend.export_downloads.index', compact(
            'exportDownloads',
            'userId',
            'user',
            'allUsers',
            'isSuperAdmin',
            'usersWithExports'
        ));
    }
}
