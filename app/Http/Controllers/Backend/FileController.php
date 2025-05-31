<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\File;
use App\Models\Backend\Template;
use App\Models\Backend\Project;
use App\Models\DatabaseEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    /**
     * Display a listing of the files.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $files = File::with(['template', 'databaseEntity', 'uploader', 'project'])
            // ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return view('backend.files.index', compact('files'));
    }

    /**
     * Show the form for creating a new file.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $file = new File();
        $templates = Template::where('is_active', true)->orderBy('name')->get();
        $databaseEntities = DatabaseEntity::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $isCreate = true;
        
        return view('backend.files.upsert', compact(
            'file', 
            'templates', 
            'databaseEntities', 
            'projects', 
            'isCreate'
        ));
    }

    /**
     * Store a newly created file in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'database_entity_id' => 'nullable|exists:database_entities,id',
            'project_id' => 'nullable|exists:projects,id',
            'file' => 'required|file|max:20480', // 20MB max
            'processing_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle file upload
        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileName = time() . '_' . $originalName;
        $filePath = $uploadedFile->storeAs('uploads', $fileName, 'public');

        // Create file record
        $file = new File([
            'name' => $request->name ?? $originalName,
            'original_name' => $originalName,
            'description' => $request->description,
            'file_path' => $filePath,
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
            'template_id' => $request->template_id,
            'database_entity_id' => $request->database_entity_id,
            'project_id' => $request->project_id,
            'processing_notes' => $request->processing_notes,
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'is_deleted' => false,
        ]);

        $file->save();

        return redirect()->route('files.index')
            ->with('success', 'File uploaded successfully');
    }

    /**
     * Display the specified file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\View\View
     */
    public function show(File $file)
    {
        $file->load(['template', 'databaseEntity', 'uploader', 'project', 'empodatRecords']);
        
        return view('backend.files.show', compact('file'));
    }

    /**
     * Show the form for editing the specified file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\View\View
     */
    public function edit(File $file)
    {
        $templates = Template::where('is_active', true)->orderBy('name')->get();
        $databaseEntities = DatabaseEntity::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $isCreate = false;
        
        return view('backend.files.upsert', compact(
            'file', 
            'templates', 
            'databaseEntities', 
            'projects', 
            'isCreate'
        ));
    }

    /**
     * Update the specified file in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, File $file)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'database_entity_id' => 'nullable|exists:database_entities,id',
            'project_id' => 'nullable|exists:projects,id',
            'new_file' => 'nullable|file|max:20480', // 20MB max
            'processing_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle new file upload if provided
        if ($request->hasFile('new_file')) {
            // Delete old file if exists
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            
            $uploadedFile = $request->file('new_file');
            $originalName = $uploadedFile->getClientOriginalName();
            $fileName = time() . '_' . $originalName;
            $filePath = $uploadedFile->storeAs('uploads', $fileName, 'public');
            
            $file->original_name = $originalName;
            $file->file_path = $filePath;
            $file->file_size = $uploadedFile->getSize();
            $file->mime_type = $uploadedFile->getMimeType();
        }

        // Update file record
        $file->name = $request->name;
        $file->description = $request->description;
        $file->template_id = $request->template_id;
        $file->database_entity_id = $request->database_entity_id;
        $file->project_id = $request->project_id;
        $file->processing_notes = $request->processing_notes;

        $file->save();

        return redirect()->route('files.index')
            ->with('success', 'File updated successfully');
    }

    /**
     * Soft delete the specified file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(File $file)
    {
        $file->softDelete();

        return redirect()->route('files.index')
            ->with('success', 'File deleted successfully');
    }

    /**
     * Permanently delete the specified file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDestroy(File $file)
    {
        // Delete the file from storage
        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        // Delete the file record and its relationships
        $file->empodatRecords()->detach();
        $file->delete();

        return redirect()->route('files.index')
            ->with('success', 'File permanently deleted');
    }

    /**
     * Restore a soft deleted file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(File $file)
    {
        $file->restore();

        return redirect()->route('files.index')
            ->with('success', 'File restored successfully');
    }

    /**
     * Show deleted files.
     *
     * @return \Illuminate\View\View
     */
    public function deleted()
    {
        $files = File::with(['template', 'databaseEntity', 'uploader', 'project'])
            ->deleted()
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
            
        return view('backend.files.deleted', compact('files'));
    }

    /**
     * Download the file.
     *
     * @param  \App\Models\Backend\File  $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(File $file)
    {
        if (!$file->file_path || !Storage::disk('public')->exists($file->file_path)) {
            return redirect()->back()
                ->with('error', 'File not found');
        }

        return Storage::disk('public')->download(
            $file->file_path, 
            $file->original_name
        );
    }

    /**
     * Filter files by project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function filterByProject(Request $request)
    {
        $projectId = $request->get('project_id');
        
        $query = File::with(['template', 'databaseEntity', 'uploader', 'project'])
            ->notDeleted()
            ->orderBy('created_at', 'desc');
            
        if ($projectId) {
            $query->byProject($projectId);
        }
        
        $files = $query->paginate(20);
        $projects = Project::orderBy('name')->get();
        
        return view('backend.files.index', compact('files', 'projects', 'projectId'));
    }

    /**
     * Filter files by database entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function filterByEntity(Request $request)
    {
        $entityId = $request->get('database_entity_id');
        
        $query = File::with(['template', 'databaseEntity', 'uploader', 'project'])
            ->notDeleted()
            ->orderBy('created_at', 'desc');
            
        if ($entityId) {
            $query->byDatabaseEntity($entityId);
        }
        
        $files = $query->paginate(20);
        $databaseEntities = DatabaseEntity::orderBy('name')->get();
        
        return view('backend.files.index', compact('files', 'databaseEntities', 'entityId'));
    }
}