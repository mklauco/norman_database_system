<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreTemplateRequest;
use App\Http\Requests\Backend\StoreTemplateVersionRequest;
use App\Http\Requests\Backend\UpdateTemplateRequest;
use App\Models\Backend\Template;
use App\Models\DatabaseEntity;
use App\Services\Backend\TemplateVersionService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateController extends Controller
{
    public function __construct(private readonly TemplateVersionService $versions) {}

    /**
     * List the templates with their version history; templates without an active version are listed apart.
     */
    public function index(): View
    {
        [$activeTemplates, $archivedTemplates] = $this->versions->groups()
            ->map(function (Collection $versions): Collection {
                $current = $this->versions->current($versions);

                return $versions->where('id', '!=', $current->id)->prepend($current)->values();
            })
            ->partition(fn (Collection $versions): bool => (bool) $versions->first()->is_active)
            ->all();

        return view('backend.templates.index', compact('activeTemplates', 'archivedTemplates'));
    }

    public function create(): View
    {
        return view('backend.templates.upsert', [
            'template' => new Template,
            'databaseEntities' => $this->databaseEntities(),
            'isCreate' => true,
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $template = $this->versions->create(
            $request->safe()->except('template_file'),
            $request->file('template_file'),
            Auth::id()
        );

        return redirect()->route('templates.show', $template)
            ->with('success', 'Template created successfully');
    }

    public function show(Template $template): View
    {
        $template->load(['databaseEntity', 'creator', 'updater']);
        $versions = $this->versions->history($template);

        return view('backend.templates.show', compact('template', 'versions'));
    }

    public function edit(Template $template): View
    {
        return view('backend.templates.upsert', [
            'template' => $template,
            'databaseEntities' => $this->databaseEntities(),
            'isCreate' => false,
        ]);
    }

    /**
     * Update the details of one version; its file never changes.
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $template->update([...$request->validated(), 'updated_by' => Auth::id()]);

        return redirect()->route('templates.show', $template)
            ->with('success', 'Template updated successfully');
    }

    public function createVersion(Template $template): View
    {
        $versions = $this->versions->history($template);

        return view('backend.templates.new_version', [
            'current' => $this->versions->current($versions),
            'versions' => $versions,
            'nextVersion' => $this->versions->suggestNextVersion($versions),
        ]);
    }

    public function storeVersion(StoreTemplateVersionRequest $request, Template $template): RedirectResponse
    {
        $newVersion = $this->versions->createVersion(
            $template,
            $request->safe()->except('template_file'),
            $request->file('template_file'),
            Auth::id()
        );

        return redirect()->route('templates.show', $newVersion)
            ->with('success', 'New version uploaded, the previous version is archived');
    }

    public function archive(Template $template): RedirectResponse
    {
        $this->versions->archive($template, Auth::id());

        return redirect()->route('templates.show', $template)
            ->with('success', 'Template archived');
    }

    public function restore(Template $template): RedirectResponse
    {
        $this->versions->restore($template, Auth::id());

        return redirect()->route('templates.show', $template)
            ->with('success', 'Version restored, it is the active version now');
    }

    /**
     * Download the template file. Archived versions are for admins only.
     */
    public function download(Template $template): StreamedResponse|RedirectResponse
    {
        abort_unless($template->is_active || Auth::user()->hasAnyRole(['super_admin', 'admin']), 403);

        $disk = $this->versions->disk($template);

        if ($disk === null) {
            return redirect()->back()
                ->with('error', 'Template file not found');
        }

        return Storage::disk($disk)->download(
            $template->file_path,
            $template->name.'_v'.$template->version.'.'.pathinfo($template->file_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Display a listing of active templates for a specific database entity code.
     *
     * @param  string  $code
     */
    public function specificIndex($code): View
    {
        // Find the database entity by code
        $databaseEntity = DatabaseEntity::where('code', $code)->firstOrFail();

        // Get active templates for this database entity
        $templates = Template::with(['databaseEntity', 'creator'])
            ->where('database_entity_id', $databaseEntity->id)
            ->where('is_active', true)
            ->orderBy('valid_from', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('backend.templates.specific_index', compact('templates', 'databaseEntity'));
    }

    /**
     * @return EloquentCollection<int, DatabaseEntity>
     */
    private function databaseEntities(): EloquentCollection
    {
        return DatabaseEntity::whereNull('parent_id')
            ->whereNotLike('dashboard_route_name', '%https://%')
            ->orderBy('name')
            ->get();
    }
}
