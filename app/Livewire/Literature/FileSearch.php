<?php

namespace App\Livewire\Literature;

use App\Models\Backend\File;
use Livewire\Component;

class FileSearch extends Component
{
    public $search = '';

    public $selectedFileIds = [];       // Track selected file IDs

    public $selectedFiles = [];         // Store selected file data

    public $existingFiles = [];         // For initialization

    public $isFocused = false;          // Track input focus state

    // Literature database entity ID from database_entities.csv
    const LITERATURE_ENTITY_ID = 17;

    public function mount($existingFiles = [])
    {
        if (! empty($existingFiles)) {
            $this->selectedFileIds = $existingFiles;
            $this->applyFileFilter();
        }
    }

    public function setFocus()
    {
        $this->isFocused = true;
    }

    public function removeFocus()
    {
        // Don't remove focus - let it persist until user types or clears
        // This prevents the list from disappearing when clicking checkboxes
    }

    public function keepFocus()
    {
        $this->isFocused = true;
    }

    public function render()
    {
        $results = [];
        $resultsAvailable = false;

        if (strlen($this->search) > 2) {
            // Filter files only for Literature module (database_entity_id = 17)
            $results = File::orderBy('id', 'asc')
                ->where('name', 'ilike', '%'.$this->search.'%')
                ->byDatabaseEntity(self::LITERATURE_ENTITY_ID)
                ->notDeleted()
                ->limit(10)
                ->get();

            $resultsAvailable = true;
        } elseif ($this->isFocused && strlen($this->search) === 0) {
            // Show all files when input is focused but empty
            $results = File::orderBy('id', 'asc')
                ->byDatabaseEntity(self::LITERATURE_ENTITY_ID)
                ->notDeleted()
                ->get();

            $resultsAvailable = true;
        }

        return view('livewire.literature.file-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'selectedFiles' => $this->selectedFiles,
        ]);
    }

    public function applyFileFilter()
    {
        // Only get files that belong to Literature module
        $this->selectedFiles = File::whereIn('id', $this->selectedFileIds)
            ->byDatabaseEntity(self::LITERATURE_ENTITY_ID)
            ->notDeleted()
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'uploaded_at' => optional($file->uploaded_at)->format('Y-m-d'),
                ];
            })
            ->toArray();

        $this->search = '';
        $this->isFocused = false;
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
        $this->isFocused = false;
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
