<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Models\Susdat\Substance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SubstancePicker extends Component
{
    private const INLINE_LIMIT = 30;

    private const DIALOG_PER_PAGE = 50;

    private const MIN_CHARS = 3;

    private const SCOPES = ['all', 'ecotox', 'pnec', 'quality', 'hazards'];

    private const SEARCH_TYPES = ['cas_number', 'name', 'stdinchikey', 'code'];

    public bool $multiple = true;

    public string $scope = 'all';

    public string $search = '';

    public string $searchType = 'name';

    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $dialogOpen = false;

    /** @var array<int, int> */
    public array $tempIds = [];

    public int $dialogPage = 1;

    public string $dialogSort = 'name';

    public string $dialogDir = 'asc';

    public function mount(bool $multiple = true, string $scope = 'all', mixed $existingSubstances = []): void
    {
        $this->multiple = $multiple;
        $this->scope = in_array($scope, self::SCOPES, true) ? $scope : 'all';

        $ids = $this->normaliseIds($existingSubstances);

        if ($ids === []) {
            return;
        }

        $found = Substance::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $ordered = array_values(array_filter($ids, fn (int $id): bool => in_array($id, $found, true)));

        $this->selectedIds = $this->multiple ? $ordered : array_slice($ordered, 0, 1);
    }

    /**
     * Accepts null, an array, a scalar id or a JSON encoded array.
     *
     * @return array<int, int>
     */
    private function normaliseIds(mixed $value): array
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                $value = is_array($decoded) ? $decoded : [];
            } else {
                $value = $trimmed === '' ? [] : [$trimmed];
            }
        }

        if (is_int($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_scalar($item) && is_numeric($item) && (int) $item > 0 && (string) (int) $item === trim((string) $item)) {
                $ids[] = (int) $item;
            }
        }

        $ids = array_values(array_unique($ids));

        return $this->multiple ? $ids : array_slice($ids, 0, 1);
    }

    public function updatedSearchType(string $value): void
    {
        if (! in_array($value, self::SEARCH_TYPES, true)) {
            $this->searchType = 'name';
        }
    }

    public function pick(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        if ($this->multiple) {
            if (in_array($id, $this->selectedIds, true)) {
                $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
            } else {
                $this->selectedIds[] = $id;
            }

            return;
        }

        $this->selectedIds = [$id];
        $this->search = '';
    }

    public function removeSubstance(int $id): void
    {
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function openDialog(): void
    {
        $this->tempIds = $this->selectedIds;
        $this->dialogPage = 1;
        $this->dialogSort = 'name';
        $this->dialogDir = 'asc';
        $this->dialogOpen = true;
    }

    public function closeDialog(): void
    {
        $this->dialogOpen = false;
        $this->tempIds = [];
    }

    public function toggleTemp(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        if (! $this->multiple) {
            $this->tempIds = [$id];

            return;
        }

        if (in_array($id, $this->tempIds, true)) {
            $this->tempIds = array_values(array_diff($this->tempIds, [$id]));
        } else {
            $this->tempIds[] = $id;
        }
    }

    public function applyTemp(): void
    {
        $this->selectedIds = $this->multiple
            ? array_values(array_unique($this->tempIds))
            : array_slice($this->tempIds, 0, 1);

        if (! $this->multiple) {
            $this->search = '';
        }

        $this->closeDialog();
    }

    public function sortBy(string $column): void
    {
        $allowed = $this->scope === 'all' ? ['name', 'cas_number', 'code'] : ['name', 'cas_number', 'code', 'records'];

        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->dialogSort === $column) {
            $this->dialogDir = $this->dialogDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->dialogSort = $column;
            $this->dialogDir = 'asc';
        }

        $this->dialogPage = 1;
    }

    public function previousPage(): void
    {
        $this->dialogPage = max(1, $this->dialogPage - 1);
    }

    public function nextPage(): void
    {
        $this->dialogPage++;
    }

    public function render(): View
    {
        $term = trim($this->search);
        $searchable = mb_strlen($term) >= self::MIN_CHARS;

        $results = collect();
        $total = 0;

        if ($searchable) {
            $filtered = $this->filteredQuery($term);
            $total = (clone $filtered)->count();
            $results = $this->withRecords($filtered)
                ->orderBy('name')
                ->orderBy('id')
                ->limit(self::INLINE_LIMIT)
                ->get();
        }

        $dialogRows = collect();
        $lastPage = 1;

        if ($this->dialogOpen && $searchable) {
            $filtered = $this->filteredQuery($term);
            $dialogTotal = (clone $filtered)->count();
            $lastPage = max(1, (int) ceil($dialogTotal / self::DIALOG_PER_PAGE));
            $this->dialogPage = min(max(1, $this->dialogPage), $lastPage);

            $sort = $this->dialogSort === 'records' && $this->scope === 'all' ? 'name' : $this->dialogSort;
            $dir = $this->dialogDir === 'desc' ? 'desc' : 'asc';

            $dialogRows = $this->withRecords($filtered)
                ->orderBy($sort, $dir)
                ->orderBy('id')
                ->forPage($this->dialogPage, self::DIALOG_PER_PAGE)
                ->get();
        }

        return view('livewire.shared.substance-picker', [
            'searchable' => $searchable,
            'term' => $term,
            'results' => $results,
            'total' => $total,
            'inlineLimit' => self::INLINE_LIMIT,
            'selected' => $this->loadSelected(),
            'dialogRows' => $dialogRows,
            'lastPage' => $lastPage,
            'hasRecords' => $this->scope !== 'all',
        ]);
    }

    /**
     * @return Collection<int, Substance>
     */
    private function loadSelected(): Collection
    {
        if ($this->selectedIds === []) {
            return collect();
        }

        $rows = $this->withRecords(Substance::query())
            ->whereIn('id', $this->selectedIds)
            ->get()
            ->keyBy('id');

        return collect($this->selectedIds)
            ->map(fn (int $id): ?Substance => $rows->get($id))
            ->filter()
            ->values();
    }

    /**
     * Substances matching the search term and the scope (no select list).
     *
     * @return Builder<Substance>
     */
    private function filteredQuery(string $term): Builder
    {
        $query = Substance::query();

        $scopeRows = $this->scopeRows();
        if ($scopeRows !== null) {
            $query->whereExists($scopeRows->selectRaw('1'));
        }

        $type = in_array($this->searchType, self::SEARCH_TYPES, true) ? $this->searchType : 'name';

        if ($type === 'cas_number') {
            $dashless = str_replace('-', '', $term);
            $query->where(function (Builder $q) use ($term, $dashless): void {
                $q->where('susdat_substances.cas_number', 'ilike', '%'.$this->escapeLike($term).'%');
                if ($dashless !== '') {
                    $q->orWhereRaw("REPLACE(susdat_substances.cas_number, '-', '') ILIKE ?", ['%'.$this->escapeLike($dashless).'%']);
                }
            });
        } elseif ($type === 'stdinchikey') {
            $query->where('susdat_substances.stdinchikey', 'ilike', $this->escapeLike($term).'%');
        } elseif ($type === 'code') {
            $code = preg_replace('/^NS/i', '', $term) ?? $term;
            $query->where('susdat_substances.code', 'ilike', '%'.$this->escapeLike($code).'%');
        } else {
            $query->where('susdat_substances.name', 'ilike', '%'.$this->escapeLike($term).'%');
        }

        return $query;
    }

    /**
     * Adds the select list and, for restricted scopes, the `records` count.
     *
     * @param  Builder<Substance>  $query
     * @return Builder<Substance>
     */
    private function withRecords(Builder $query): Builder
    {
        $query->select([
            'susdat_substances.id',
            'susdat_substances.code',
            'susdat_substances.name',
            'susdat_substances.cas_number',
            'susdat_substances.stdinchikey',
        ]);

        $scopeRows = $this->scopeRows();
        if ($scopeRows !== null) {
            if ($this->scope === 'hazards') {
                $scopeRows->selectRaw('COUNT(*)');
            } else {
                $scopeRows->select($this->scopeTable().'.record_count');
            }

            $query->selectSub($scopeRows->limit(1), 'records');
        }

        return $query;
    }

    private function scopeTable(): string
    {
        return match ($this->scope) {
            'ecotox' => 'ecotox_main_3_substance_distinct',
            'pnec', 'quality' => 'ecotox_pnec3_substance_distinct',
            default => 'hazards_comptox_substance_data',
        };
    }

    /**
     * Correlated rows that restrict a scope; null for the unrestricted scope.
     */
    private function scopeRows(): ?QueryBuilder
    {
        if (! in_array($this->scope, ['ecotox', 'pnec', 'quality', 'hazards'], true)) {
            return null;
        }

        $table = $this->scopeTable();

        if ($this->scope === 'hazards') {
            return DB::table($table)
                ->whereColumn($table.'.susdat_substance_id', 'susdat_substances.id')
                ->whereNull($table.'.withdrawn_at');
        }

        $rows = DB::table($table)->whereColumn($table.'.substance_id', 'susdat_substances.id');

        if ($this->scope === 'quality') {
            $rows->where($table.'.record_count', '>', 0);
        }

        return $rows;
    }

    /**
     * Escapes the LIKE wildcards so the user's text is matched literally.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
