<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\File;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit log of the uploaded DCT files: who changed which file, when, and from what to what.
 */
class FileAuditController extends Controller
{
    /**
     * user_id filter value that selects changes made by seeders and commands (no user).
     */
    public const SYSTEM_USER = 0;

    public function index(Request $request): View
    {
        $userId = $request->filled('user_id') ? $request->integer('user_id') : null;
        $fileId = $request->filled('file_id') ? $request->integer('file_id') : null;

        $audits = Audit::query()
            ->where('auditable_type', File::class)
            ->with('user')
            ->when($fileId !== null, fn ($query) => $query->where('auditable_id', $fileId))
            ->when($userId === self::SYSTEM_USER, fn ($query) => $query->whereNull('user_id'))
            ->when($userId !== null && $userId !== self::SYSTEM_USER, fn ($query) => $query
                ->where('user_id', $userId)
                ->where('user_type', User::class))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $users = User::query()
            ->whereIn('id', Audit::query()
                ->select('user_id')
                ->where('auditable_type', File::class)
                ->where('user_type', User::class))
            ->select('id', 'first_name', 'last_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $file = $fileId !== null ? File::find($fileId) : null;

        return view('backend.files.audits', compact('audits', 'users', 'userId', 'fileId', 'file'));
    }

    /**
     * Downloads the file that an edit replaced, as it was before the change.
     */
    public function downloadPrevious(Audit $audit): StreamedResponse|RedirectResponse
    {
        $path = $audit->old_values['file_path'] ?? null;

        if ($audit->auditable_type !== File::class || ! is_string($path) || $path === '') {
            abort(404);
        }

        $name = $audit->old_values['original_name'] ?? basename($path);
        $historyPath = File::historyPath($path);

        if (Storage::disk('local')->exists($historyPath)) {
            return Storage::disk('local')->download($historyPath, $name);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, $name);
        }

        return redirect()->back()->with('error', 'The previous file is no longer on disk.');
    }
}
