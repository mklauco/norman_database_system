<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Template;
use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $templates = Template::with(['databaseEntity', 'creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('backend.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $template = new Template();
        $databaseEntities = DatabaseEntity::orderBy('name')->get();
        $isCreate = true;
        
        return view('backend.templates.upsert', compact('template', 'databaseEntities', 'isCreate'));
    }

    /**
     * Store a newly created template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'valid_from' => 'nullable|date',
            'database_entity_id' => 'nullable|exists:database_entities,id',
            'template_file' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:10240', // 10MB max
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template = new Template([
            'name' => $request->name,
            'description' => $request->description,
            'version' => $request->version ?? '1',
            'valid_from' => $request->valid_from,
            'database_entity_id' => $request->database_entity_id,
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Handle file upload if provided
        if ($request->hasFile('template_file')) {
            $file = $request->file('template_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('templates', $fileName, 'public');
            $template->file_path = $filePath;
        }

        $template->save();

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully');
    }

    /**
     * Display the specified template.
     *
     * @param  \App\Models\Backend\Template  $template
     * @return \Illuminate\View\View
     */
    public function show(Template $template)
    {
        $template->load(['databaseEntity', 'creator', 'updater', 'files']);
        
        return view('backend.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template.
     *
     * @param  \App\Models\Backend\Template  $template
     * @return \Illuminate\View\View
     */
    public function edit(Template $template)
    {
        $databaseEntities = DatabaseEntity::orderBy('name')->get();
        $isCreate = false;
        
        return view('backend.templates.upsert', compact('template', 'databaseEntities', 'isCreate'));
    }

    /**
     * Update the specified template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Backend\Template  $template
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Template $template)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'valid_from' => 'nullable|date',
            'database_entity_id' => 'nullable|exists:database_entities,id',
            'template_file' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:10240', // 10MB max
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template->name = $request->name;
        $template->description = $request->description;
        $template->version = $request->version ?? $template->version;
        $template->valid_from = $request->valid_from;
        $template->database_entity_id = $request->database_entity_id;
        $template->is_active = $request->has('is_active');
        $template->updated_by = Auth::id();

        // Handle file upload if provided
        if ($request->hasFile('template_file')) {
            // Delete old file if exists
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }
            
            $file = $request->file('template_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('templates', $fileName, 'public');
            $template->file_path = $filePath;
        }

        $template->save();

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully');
    }

    /**
     * Remove the specified template from storage.
     *
     * @param  \App\Models\Backend\Template  $template
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Template $template)
    {
        // Check if there are any files using this template
        if ($template->files()->count() > 0) {
            return redirect()->route('templates.index')
                ->with('error', 'Cannot delete this template because it is being used by one or more files');
        }

        // Delete the file if exists
        if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted successfully');
    }

    /**
     * Download the template file.
     *
     * @param  \App\Models\Backend\Template  $template
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Template $template)
    {
        if (!$template->file_path || !Storage::disk('public')->exists($template->file_path)) {
            return redirect()->back()
                ->with('error', 'Template file not found');
        }

        return Storage::disk('public')->download(
            $template->file_path, 
            $template->name . '_v' . $template->version . '.' . pathinfo($template->file_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Display a listing of active templates for a specific database entity code.
     *
     * @param  string  $code
     * @return \Illuminate\View\View
     */
    public function specificIndex($code)
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
}