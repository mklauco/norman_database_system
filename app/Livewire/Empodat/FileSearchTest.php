<?php

namespace App\Livewire\Empodat;

use Livewire\Component;
use App\Models\Backend\File;

class FileSearchTest extends Component
{
    public $search = '';
    public $selectedFileIds = [];       // Track selected file IDs
    public $selectedFiles = [];         // Store selected file data
    public $existingFiles = [];         // For initialization

    public function mount($existingFiles = [])
    {
        if (!empty($existingFiles)) {
            $this->selectedFileIds = $existingFiles;
            $this->applyFileFilter();
        }
    }

    public function render()
    {
        $results = [];
        $resultsAvailable = false;

        if (strlen($this->search) > 2) {
            $results = File::orderBy('id', 'asc')
                ->where('name', 'ilike', '%' . $this->search . '%')
                ->limit(10)
                ->get();

            $resultsAvailable = true;
        }

        return view('livewire.empodat.file-search-test', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'selectedFiles' => $this->selectedFiles,
        ]);
    }

    public function applyFileFilter()
    {
        $this->selectedFiles = File::whereIn('id', $this->selectedFileIds)
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'size' => $this->formatFileSize($file->file_size),
                    'type' => $file->mime_type,
                    'uploaded_at' => optional($file->uploaded_at)->format('Y-m-d'),
                ];
            })
            ->toArray();

        $this->search = '';
    }

    public function removeFile($fileId)
    {
        $this->selectedFileIds = array_filter($this->selectedFileIds, function ($id) use ($fileId) {
            return (string) $id !== (string) $fileId;
        });

        $this->applyFileFilter();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedFileIds = [];
        $this->selectedFiles = [];
    }

    private function formatFileSize($bytes)
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / pow(1024, $power), 1) . ' ' . $units[$power];
    }
}
