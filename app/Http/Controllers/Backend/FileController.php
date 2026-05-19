<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\File;
use App\Models\Backend\Project;
use App\Models\Backend\Template;
use App\Models\DatabaseEntity;
use App\Services\FileRescanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Display a listing of the files.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 100);
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $databaseEntityId = $request->input('database_entity_id', '');

        $query = File::with(['template', 'databaseEntity', 'uploader', 'project']);

        // Apply search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('original_name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Apply database entity filter
        if (! empty($databaseEntityId)) {
            $query->where('database_entity_id', $databaseEntityId);
        }

        // Apply sorting - only allow safe columns
        $allowedSortColumns = ['id', 'name', 'original_name', 'file_size', 'uploaded_at', 'created_at', 'updated_at'];
        if (in_array($sort, $allowedSortColumns)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('id', 'desc');
        }

        $files = $query->paginate($perPage)->appends($request->except('page'));
        $databaseEntities = DatabaseEntity::whereNull('parent_id')->orderBy('id')->get();

        return view('backend.files.index', [
            'files' => $files,
            'columns' => $this->getVisibleColumns(),
            'search' => $search,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'databaseEntities' => $databaseEntities,
            'databaseEntityId' => $databaseEntityId,
        ]);
    }

    /**
     * Get file data for AJAX requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileData(Request $request)
    {
        $perPage = $request->input('per_page', 100);
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $entityId = $request->input('database_entity_id', '');
        $projectId = $request->input('project_id', '');

        $query = File::with(['template', 'databaseEntity', 'uploader', 'project']);

        // Apply search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('original_name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Apply filters
        if (! empty($entityId)) {
            $query->byDatabaseEntity($entityId);
        }

        if (! empty($projectId)) {
            $query->byProject($projectId);
        }

        // Apply sorting
        if (in_array($sort, ['id', 'name', 'original_name', 'file_size', 'uploaded_at', 'created_at', 'updated_at'])) {
            $query->orderBy($sort, $direction);
        }

        $results = $query->paginate($perPage);

        return response()->json($results);
    }

    /**
     * Get visible columns for the table.
     *
     * @return array
     */
    private function getVisibleColumns()
    {
        return [
            'id',
            'name',
            'project',
            'database_entity',
            'template',
            'size',
            'uploaded_by',
            'uploaded_at',
        ];
    }

    /**
     * Show the form for creating a new file.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $file = new File;
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
            'is_protected' => 'nullable|boolean',
            'number_of_records' => 'nullable|integer|min:0',
            'uploaded_by' => 'nullable|exists:users,id',
            'doi' => 'nullable|string|max:255',
            'main_id_from' => 'nullable|integer',
            'main_id_to' => 'nullable|integer',
            'analysis_number' => 'nullable|integer',
            'source_id_from' => 'nullable|integer',
            'source_id_to' => 'nullable|integer',
            'source_number' => 'nullable|integer',
            'method_id_from' => 'nullable|integer',
            'method_id_to' => 'nullable|integer',
            'method_number' => 'nullable|integer',
            'list_type' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'matrice_dct' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle file upload
        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileName = time().'_'.$originalName;
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
            'uploaded_by' => $request->uploaded_by ?? Auth::id(),
            'uploaded_at' => now(),
            'is_deleted' => false,
            'is_protected' => $request->is_protected ?? false,
            'number_of_records' => $request->number_of_records ?? 0,
            'doi' => $request->doi,
            'main_id_from' => $request->main_id_from,
            'main_id_to' => $request->main_id_to,
            'analysis_number' => $request->analysis_number,
            'source_id_from' => $request->source_id_from,
            'source_id_to' => $request->source_id_to,
            'source_number' => $request->source_number,
            'method_id_from' => $request->method_id_from,
            'method_id_to' => $request->method_id_to,
            'method_number' => $request->method_number,
            'list_type' => $request->list_type,
            'note' => $request->note,
            'matrice_dct' => $request->matrice_dct,
        ]);

        $file->save();

        return redirect()->route('files.index')
            ->with('success', 'File uploaded successfully');
    }

    /**
     * Display the specified file.
     *
     * @return \Illuminate\View\View
     */
    public function show(File $file)
    {
        // Load only essential relationships without heavy queries
        $file->load(['template', 'databaseEntity', 'uploader', 'project']);

        return view('backend.files.show', compact('file'));
    }

    /**
     * Show the form for editing the specified file.
     *
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
            'is_protected' => 'nullable|boolean',
            'number_of_records' => 'nullable|integer|min:0',
            'uploaded_by' => 'nullable|exists:users,id',
            'doi' => 'nullable|string|max:255',
            'main_id_from' => 'nullable|integer',
            'main_id_to' => 'nullable|integer',
            'analysis_number' => 'nullable|integer',
            'source_id_from' => 'nullable|integer',
            'source_id_to' => 'nullable|integer',
            'source_number' => 'nullable|integer',
            'method_id_from' => 'nullable|integer',
            'method_id_to' => 'nullable|integer',
            'method_number' => 'nullable|integer',
            'list_type' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'matrice_dct' => 'nullable|integer',
            'is_deleted' => 'nullable|boolean',
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
            $fileName = time().'_'.$originalName;
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
        $file->is_protected = $request->is_protected ?? false;
        $file->number_of_records = $request->number_of_records ?? 0;
        $file->uploaded_by = $request->uploaded_by;
        $file->doi = $request->doi;
        $file->main_id_from = $request->main_id_from;
        $file->main_id_to = $request->main_id_to;
        $file->analysis_number = $request->analysis_number;
        $file->source_id_from = $request->source_id_from;
        $file->source_id_to = $request->source_id_to;
        $file->source_number = $request->source_number;
        $file->method_id_from = $request->method_id_from;
        $file->method_id_to = $request->method_id_to;
        $file->method_number = $request->method_number;
        $file->list_type = $request->list_type;
        $file->note = $request->note;
        $file->matrice_dct = $request->matrice_dct;
        $file->is_deleted = $request->is_deleted ?? false;

        $file->save();

        return redirect()->route('files.edit', $file)
            ->with('success', 'File updated successfully.');
    }

    /**
     * Soft delete the specified file.
     *
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
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(File $file)
    {
        if (! $file->file_path || ! Storage::disk('public')->exists($file->file_path)) {
            return redirect()->back()
                ->with('error', 'File not found');
        }

        return Storage::disk('public')->download(
            $file->file_path,
            $file->original_name
        );
    }

    /**
     * Build the filtered query used for exports (mirrors index filters).
     */
    private function buildExportQuery(Request $request)
    {
        $search = $request->input('search', '');
        $databaseEntityId = $request->input('database_entity_id', '');
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');

        $query = File::with(['databaseEntity', 'uploader', 'project']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('original_name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (! empty($databaseEntityId)) {
            $query->where('database_entity_id', $databaseEntityId);
        }

        $allowedSortColumns = ['id', 'name', 'original_name', 'file_size', 'uploaded_at', 'created_at', 'updated_at'];
        if (in_array($sort, $allowedSortColumns)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Export the filtered files list as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        $query = $this->buildExportQuery($request);
        $filename = 'files_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'File Name', 'Project', 'Database', 'ID From', 'ID To',
                'Records', 'Protected', 'Deleted', 'Uploaded By', 'Date',
            ]);

            $query->chunk(500, function ($files) use ($out) {
                foreach ($files as $file) {
                    fputcsv($out, [
                        $file->id,
                        $file->original_name ?? $file->name ?? '',
                        $file->project->name ?? '',
                        $file->databaseEntity->name ?? '',
                        $file->main_id_from,
                        $file->main_id_to,
                        $file->number_of_records ?? 0,
                        $file->is_protected ? 'Yes' : 'No',
                        $file->is_deleted ? 'Yes' : 'No',
                        $file->uploader ? trim($file->uploader->first_name.' '.$file->uploader->last_name) : '',
                        $file->uploaded_at ? $file->uploaded_at->format('Y-m-d') : '',
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /**
     * Export the filtered files list as Markdown table (download as .md).
     */
    public function exportMarkdown(Request $request): StreamedResponse
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        $query = $this->buildExportQuery($request);
        $filename = 'files_'.now()->format('Ymd_His').'.md';

        $headers = [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $escape = function ($value): string {
            $value = (string) ($value ?? '');
            $value = str_replace(['|', "\r\n", "\n", "\r"], ['\\|', ' ', ' ', ' '], $value);

            return trim($value);
        };

        $formatNum = function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return number_format((float) $value, 0, '.', ' ');
        };

        return response()->stream(function () use ($query, $escape, $formatNum) {
            $out = fopen('php://output', 'w');

            $columns = ['ID', 'File Name', 'Project', 'Database', 'ID From', 'ID To', 'Records', 'Protected', 'Deleted', 'Uploaded By', 'Date'];
            fwrite($out, '| '.implode(' | ', $columns)." |\n");
            fwrite($out, '|'.str_repeat(' --- |', count($columns))."\n");

            $query->chunk(500, function ($files) use ($out, $escape, $formatNum) {
                foreach ($files as $file) {
                    $row = [
                        $file->id,
                        $escape($file->original_name ?? $file->name ?? '-'),
                        $escape($file->project->name ?? '-'),
                        $escape($file->databaseEntity->name ?? '-'),
                        $formatNum($file->main_id_from),
                        $formatNum($file->main_id_to),
                        $formatNum($file->number_of_records ?? 0),
                        $file->is_protected ? 'Yes' : 'No',
                        $file->is_deleted ? 'Yes' : 'No',
                        $escape($file->uploader ? trim($file->uploader->first_name.' '.$file->uploader->last_name) : '-'),
                        $file->uploaded_at ? $file->uploaded_at->format('Y-m-d') : '-',
                    ];
                    fwrite($out, '| '.implode(' | ', $row)." |\n");
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /**
     * Filter files by project.
     *
     * @return \Illuminate\View\View
     */
    public function filterByProject(Request $request)
    {
        $projectId = $request->get('project_id');

        $query = File::with(['template', 'databaseEntity', 'uploader', 'project'])
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
     * @return \Illuminate\View\View
     */
    public function filterByEntity(Request $request)
    {
        $entityId = $request->get('database_entity_id');

        $query = File::with(['template', 'databaseEntity', 'uploader', 'project'])
            ->orderBy('created_at', 'desc');

        if ($entityId) {
            $query->byDatabaseEntity($entityId);
        }

        $files = $query->paginate(20);
        $databaseEntities = DatabaseEntity::orderBy('name')->get();

        return view('backend.files.index', compact('files', 'databaseEntities', 'entityId'));
    }

    /**
     * Rescan file to update main_id_from, main_id_to, and number_of_records.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rescan(File $file, FileRescanService $rescanService)
    {
        $result = $rescanService->rescan($file);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message'].
                ' Records: '.number_format($result['data']['number_of_records']).
                ', ID range: '.number_format($result['data']['main_id_from'] ?? 0).
                ' - '.number_format($result['data']['main_id_to'] ?? 0));
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
