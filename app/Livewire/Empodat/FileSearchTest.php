<?php

namespace App\Livewire\Empodat;

use App\Models\Backend\File;
use Livewire\Component;

class FileSearchTest extends Component
{
    public $search = '';

    public $selectedFileIds = [];       // Track selected file IDs

    public $selectedFiles = [];         // Store selected file data

    public $existingFiles = [];         // For initialization

    public $currentResults = [];        // Store current search results

    public function mount($existingFiles = [])
    {
        if (! empty($existingFiles)) {
            $this->selectedFileIds = is_array($existingFiles) ? $existingFiles : [$existingFiles];
            $this->applyFileFilter();
        }
    }

    public function render()
    {
        $results = [];
        $resultsAvailable = false;

        // Trim the search string to remove any leading/trailing whitespace
        $searchTerm = trim($this->search);

        if (strlen($searchTerm) > 2) {
            $results = File::orderBy('original_name', 'asc')
                ->where('database_entity_id', 2) // Only empodat files
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'ilike', '%'.$searchTerm.'%')
                        ->orWhere('original_name', 'ilike', '%'.$searchTerm.'%');
                })
                ->get();

            $resultsAvailable = true;

            // Store current results for select all functionality
            $this->currentResults = $results->pluck('id')->toArray();
        }

        return view('livewire.empodat.file-search-test', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'selectedFiles' => $this->selectedFiles,
        ]);
    }

    public function applyFileFilter()
    {
        // Remove duplicates
        $this->selectedFileIds = array_unique($this->selectedFileIds);

        $this->selectedFiles = File::whereIn('id', $this->selectedFileIds)
            ->where('database_entity_id', 2) // Only empodat files
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->original_name ?: $file->name,
                    'size' => $this->formatFileSize($file->file_size),
                    'type' => $file->mime_type,
                    'uploaded_at' => optional($file->uploaded_at)->format('Y-m-d'),
                ];
            })
            ->toArray();

        $this->search = '';
        $this->currentResults = [];
    }

    public function selectAllDisplayed()
    {
        // Merge current results with already selected IDs
        $this->selectedFileIds = array_unique(array_merge(
            $this->selectedFileIds,
            $this->currentResults
        ));

        $this->applyFileFilter();
    }

    public function removeFile($fileId)
    {
        $this->selectedFileIds = array_filter($this->selectedFileIds, function ($id) use ($fileId) {
            return (string) $id !== (string) $fileId;
        });

        // Re-index array to avoid gaps
        $this->selectedFileIds = array_values($this->selectedFileIds);

        $this->applyFileFilter();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedFileIds = [];
        $this->selectedFiles = [];
        $this->currentResults = [];
    }

    private function formatFileSize($bytes)
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / pow(1024, $power), 1).' '.$units[$power];
    }
}
