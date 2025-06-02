<?php

namespace App\Livewire\Empodat;

use Livewire\Component;
use App\Models\Backend\File;

class FileSearch extends Component
{
    public $search = '';
    public $selectedFiles = [];
    public $searchResults = [];
    public $showDropdown = false;
    public $isLoading = false;

    protected $queryString = [
        'selectedFiles' => ['except' => []]
    ];

    public function mount($existingFiles = [])

    {
        $this->selectedFiles = is_array($existingFiles) ? $existingFiles : [];

        if (!empty($this->selectedFiles)) {
            $this->loadSelectedFilesData();
        }

        $this->searchFiles(); // Load default results
    }

public function updatedSearch($value)
{dd($value);
    $this->searchFiles($value);
}


    public function searchFiles()
{
    $this->isLoading = true;

    $query = File::query()
        ->whereNull('is_deleted') // Optional: exclude deleted files if needed
        ->orderByDesc('uploaded_at');

    if (strlen($this->search) >= 1) {
        $query->where('name', 'like', '%' . $this->search . '%');
    } else {
        $query->limit(5);
    }

    $this->searchResults = $query
        ->select('id', 'name', 'file_size', 'mime_type', 'uploaded_at')
        ->limit(15)
        ->get()
        ->map(function ($file) {
            return [
                'id' => $file->id,
                'display_name' => $file->name,
                'size' => $this->formatFileSize($file->file_size),
                'type' => $file->mime_type,
                'created_at' => optional($file->uploaded_at)->format('Y-m-d'),
            ];
        })
        ->toArray();

    $this->showDropdown = count($this->searchResults) > 0;
    $this->isLoading = false;
}


    public function selectFile($fileId)
    {
        if (!in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles[] = $fileId;
            $this->loadSelectedFilesData();
        }

        $this->search = '';
        $this->searchResults = [];
        $this->showDropdown = false;

        $this->emitUp('fileSelectionChanged', $this->selectedFiles);
    }

    public function removeFile($fileId)
    {
        $this->selectedFiles = array_values(array_filter($this->selectedFiles, function ($id) use ($fileId) {
            return $id != $fileId;
        }));

        $this->loadSelectedFilesData();
        $this->emitUp('fileSelectionChanged', $this->selectedFiles);
    }

    public function clearAll()
    {
        $this->selectedFiles = [];
        $this->loadSelectedFilesData();
        $this->emitUp('fileSelectionChanged', $this->selectedFiles);
    }

    public function hideDropdown()
    {
        $this->showDropdown = false;
    }

    private function loadSelectedFilesData()
{
    if (empty($this->selectedFiles)) {
        $this->selectedFilesData = [];
        return;
    }

    $this->selectedFilesData = File::whereIn('id', $this->selectedFiles)
        ->select('id', 'name', 'file_size', 'mime_type', 'uploaded_at')
        ->get()
        ->map(function ($file) {
            return [
                'id' => $file->id,
                'display_name' => $file->name,
                'size' => $this->formatFileSize($file->file_size),
                'type' => $file->mime_type,
                'created_at' => optional($file->uploaded_at)->format('Y-m-d'),
            ];
        })
        ->toArray();
}


    private function formatFileSize($bytes)
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / pow(1024, $power), 1) . ' ' . $units[$power];
    }

public function render()
{
    return view('livewire.empodat.file-search', [
        'selectedFilesData' => $this->selectedFilesData ?? [],
    ]);
}

}
