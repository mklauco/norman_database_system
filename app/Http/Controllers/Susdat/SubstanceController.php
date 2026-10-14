<?php

namespace App\Http\Controllers\Susdat;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use App\Models\Susdat\UseCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SubstanceController extends Controller
{
    public function audits(Substance $substance)
    {
        $audits = $substance->audits()->with('user')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('susdat.audits', [
            'substance' => $substance,
            'audits' => $audits,
        ]);
    }

    public function withAudits()
    {
        $substances = Substance::whereHas('audits')->withCount('audits')->orderBy('audits_count', 'desc')->paginate(50)->withQueryString();

        return view('susdat.with-audits', [
            'substances' => $substances,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $substancesCount = Substance::count();

        // Apply ordering
        $orderColumn = 'code';
        $orderDirection = 'asc';

        if (! is_null($request->input('order_by_column')) && ! is_null($request->input('order_by_direction'))) {
            $viewColumns = $this->getViewColumns();
            $columnIndex = $request->input('order_by_column');
            $columnName = $viewColumns[$columnIndex] ?? 'NORMAN SusDat ID';

            $columnMapping = [
                '' => 'id',
                'NORMAN SusDat ID' => 'code',
                'Name' => 'name',
                'CAS_RN' => 'cas_number',
                'StdInChIKey' => 'stdinchikey',
                'DTXSID' => 'dtxid',
                'PubChem_CID' => 'pubchem_cid',
                'Molecular Formula' => 'molecular_formula',
                'SMILES' => 'smiles',
                'Monoisotopic Mass' => 'mass_iso',
            ];

            $orderColumn = $columnMapping[$columnName] ?? 'code';
            $orderDirection = $this->orderByList((int) $request->input('order_by_direction')) ?? 'asc';
        }

        $substances = Substance::select(['id', 'code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'dtxid', 'pubchem_cid', 'molecular_formula', 'mass_iso', 'deleted_at'])
            ->orderBy($orderColumn, $orderDirection)
            ->paginate(100)
            ->withQueryString();

        // Log query for CSV export (only on first page load)
        $queryLogId = null;
        if (! $request->has('page')) {
            $queryLogId = $this->logIndexQuery($substancesCount);
        } else {
            // Get the most recent index query log
            $queryLogId = QueryLog::where('database_key', 'susdat')
                ->whereRaw("content::text LIKE '%\"listAll\":true%'")
                ->orderBy('id', 'desc')
                ->value('id');
        }

        // Get category IDs for the paginated substances
        $substanceIds = $substances->pluck('id')->toArray();
        $categoryIds = [];

        if (! empty($substanceIds)) {
            $categoryIds = DB::table('susdat_category_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select(['substance_id AS id'])
                ->selectRaw("STRING_AGG(category_id::text, '|' ORDER BY category_id) AS category_ids")
                ->groupBy('substance_id')
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        // Get source IDs for the paginated substances
        $sourceIds = [];

        if (! empty($substanceIds)) {
            $sourceIds = DB::table('susdat_source_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select(['substance_id AS id'])
                ->selectRaw("STRING_AGG(source_id::text, '|' ORDER BY source_id) AS source_ids")
                ->groupBy('substance_id')
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        // Add category_ids to substances
        foreach ($substances as $substance) {
            $substance->category_ids = $categoryIds[$substance->id]->category_ids ?? null;
        }

        // Get categories and sources for display
        $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->sanitized_name;
        }

        return view('susdat.index', [
            'columns' => $this->getViewColumns(),
            'substances' => $substances,
            'substancesCount' => $substancesCount,
            'query_log_id' => $queryLogId,
            'request' => $request,
            'sourceIds' => $sourceIds,
            'activeCategoryids' => [],
            'activeSourceids' => [],
            'sources' => $sources,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'categoriesList' => $categories->pluck('name', 'id')->toArray(),
            'orderByDirection' => $this->orderByList(),
            'filter' => [
                'order_by_direction' => (int) ($request->input('order_by_direction') ?? 0),
                'order_by_column' => $request->input('order_by_column') ?? 1,
            ],
            'searchParameters' => [],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $nextCode = (int) Substance::where('code', '<', '00400000')->max('code') + 1;
        $nextCode = str_pad((string) $nextCode, 8, '0', STR_PAD_LEFT);

        $substance = new Substance(['code' => $nextCode]);
        $editables = $this->getEditableColumns();

        $categories = Category::orderBy('name', 'asc')->get();
        $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->sanitized_name;
        }

        return view('susdat.create', [
            'substance' => $substance,
            'categories' => $categories,
            'sources' => $sources,
            'sourceList' => $sourceList,
            'editables' => $editables,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:susdat_substances,code',
            'name' => 'required|string|max:1000',
        ]);

        $substance = new Substance;
        $editables = $this->getEditableColumns();

        foreach ($editables as $key) {
            if ($request->has($key)) {
                if (substr($key, 0, 8) == 'metadata') {
                    $substance->$key = json_encode($request->input($key));
                } else {
                    $substance->$key = $request->input($key);
                }
            }

            // Handle new metadata key/value pairs
            if (substr($key, 0, 8) == 'metadata') {
                $newKeys = $request->input($key.'_new_key', []);
                $newValues = $request->input($key.'_new_value', []);
                $newData = [];
                foreach ($newKeys as $i => $newKey) {
                    $newKey = trim($newKey);
                    if ($newKey !== '' && isset($newValues[$i])) {
                        $newData[$newKey] = trim($newValues[$i]);
                    }
                }
                if (! empty($newData)) {
                    $substance->$key = json_encode($newData);
                }
            }
        }

        $substance->added_by = Auth::id();

        try {
            $substance->save();
            session()->flash('success', 'Substance created successfully');

            return redirect()->route('substances.show', ['substance' => $substance->id]);
        } catch (Exception $e) {
            session()->flash('failure', 'An error occurred while creating the substance. '.$e->getMessage());

            return redirect()
                ->route('substances.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $substance = Substance::with(['categories', 'sources'])->findOrFail($id);

        return view('susdat.show', [
            'substance' => $substance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $substance = Substance::findOrFail($id);
        $editables = $this->getEditableColumns();

        $categories = Category::orderBy('name', 'asc')->get();
        $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->sanitized_name;
        }

        return view('susdat.edit', [
            'substance' => $substance,
            'categories' => $categories,
            'sources' => $sources,
            'sourceList' => $sourceList,
            'editables' => $editables,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $substance = Substance::findOrFail($id);
        $editables = $this->getEditableColumns();

        foreach ($editables as $key) {
            if ($key === 'code') {
                continue;
            }

            if ($request->has($key)) {
                if (substr($key, 0, 8) == 'metadata') {
                    $existing = $request->input($key) ?? [];

                    // Merge new key/value pairs
                    $newKeys = $request->input($key.'_new_key', []);
                    $newValues = $request->input($key.'_new_value', []);
                    foreach ($newKeys as $i => $newKey) {
                        $newKey = trim($newKey);
                        if ($newKey !== '' && isset($newValues[$i])) {
                            $existing[$newKey] = trim($newValues[$i]);
                        }
                    }

                    $substance->$key = json_encode($existing);
                } else {
                    $substance->$key = $request->input($key);
                }
            } else {
                // Handle new metadata entries when no existing keys were submitted
                if (substr($key, 0, 8) == 'metadata') {
                    $newKeys = $request->input($key.'_new_key', []);
                    $newValues = $request->input($key.'_new_value', []);
                    $newData = [];
                    foreach ($newKeys as $i => $newKey) {
                        $newKey = trim($newKey);
                        if ($newKey !== '' && isset($newValues[$i])) {
                            $newData[$newKey] = trim($newValues[$i]);
                        }
                    }
                    if (! empty($newData)) {
                        $substance->$key = json_encode($newData);
                    }
                }
            }
        }

        try {
            $s = $substance->save();
            session()->flash('success', 'Substance updated successfully');

            return redirect()->route('substances.show', ['substance' => $id]);
        } catch (Exception $e) {
            session()->flash('failure', 'An error occurred while updating the substance. Please contact the administrator.'.$e->getMessage());

            return redirect()
                ->route('substances.edit', ['substance' => $id])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * The three-level use-category tree replaces the flat susdat_categories
     * list, but its tables are created by a migration that can only run after
     * this code is already deployed. Until the migration and its seeders have
     * run, everything keeps reading the legacy flat tables.
     */
    private function useCategoryTreeAvailable(): bool
    {
        return Cache::remember('use_category_tree_available', 60, function (): bool {
            return Schema::hasTable('susdat_use_categories') && UseCategory::query()->exists();
        });
    }

    /**
     * Legacy flat categories shaped like the tree the filter view expects.
     */
    private function legacyCategoriesAsFlatTree(): EloquentCollection
    {
        return Category::orderBy('name', 'asc')->get()->each(
            fn (Category $category) => $category->setRelation('children', new EloquentCollection)
        );
    }

    /**
     * Show the filter form
     */
    public function filter(Request $request)
    {
        $categories = $this->useCategoryTreeAvailable()
            ? UseCategory::with('children.children')->roots()->get()
            : $this->legacyCategoriesAsFlatTree();
        $sources = SuspectListExchangeSource::where('show', 1)->whereNotNull('order')->orderBy('order', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->sanitized_name;
        }
        // dd($sources->pluck('code'));

        return view('susdat.filter', [
            'request' => $request,
            'categories' => $categories,
            'sources' => $sources,
            'sourceList' => $sourceList,
        ]);
    }

    /**
     * Process the search and display results - OPTIMIZED VERSION
     */
    public function search(Request $request)
    {
        // Cache the total count for the request lifecycle
        $substancesCount = Cache::remember('substances_total_count', 300, function () {
            return Substance::count();
        });

        // Parse input parameters
        $categoriesSearch = is_array($request->input('categoriesSearch')) ? $request->input('categoriesSearch') : json_decode($request->input('categoriesSearch')) ?? [];

        $sourcesSearch = is_array($request->input('sourcesSearch')) ? $request->input('sourcesSearch') : json_decode($request->input('sourcesSearch')) ?? [];

        $substancesSearch = is_array($request->input('substancesSearch')) ? $request->input('substancesSearch') : json_decode($request->input('substancesSearch')) ?? [];

        // Filter to keep only valid positive integers
        $categoriesSearch = array_filter(array_map('intval', $categoriesSearch), fn ($id) => $id > 0);
        $sourcesSearch = array_filter(array_map('intval', $sourcesSearch), fn ($id) => $id > 0);
        $substancesSearch = array_filter(array_map('intval', $substancesSearch), fn ($id) => $id > 0);

        // Get all categories and sources (cached)
        $usesCategoryTree = $this->useCategoryTreeAvailable();

        $allCategories = Cache::remember($usesCategoryTree ? 'all_use_category_ids' : 'all_category_ids', 300, function () use ($usesCategoryTree) {
            return $usesCategoryTree ? UseCategory::pluck('id')->toArray() : Category::pluck('id')->toArray();
        });

        $allSources = Cache::remember('all_source_ids', 300, function () {
            return SuspectListExchangeSource::pluck('id')->toArray();
        });

        // Build the optimized query using Eloquent ORM
        $substances = Substance::query()->select(['id', 'code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'dtxid', 'pubchem_cid', 'molecular_formula', 'mass_iso', 'deleted_at']);

        // Apply search filters efficiently
        if ($request->input('searchCategory') == 1 && ! empty($categoriesSearch)) {
            $substances->whereHas($usesCategoryTree ? 'useCategories' : 'categories', function ($query) use ($categoriesSearch, $usesCategoryTree) {
                $query->whereIn($usesCategoryTree ? 'susdat_use_categories.id' : 'susdat_categories.id', $categoriesSearch);
            });
        } elseif ($request->input('searchSource') == 1 && ! empty($sourcesSearch)) {
            $substances->whereHas('sources', function ($query) use ($sourcesSearch) {
                $query->whereIn('source_id', $sourcesSearch);
            });
        } elseif ($request->input('searchSubstance') == 1 && ! empty($substancesSearch)) {
            $substances->whereIn('id', $substancesSearch);
            $categoriesSearch = $allCategories;
            $sourcesSearch = $allSources;
        }

        // Apply ordering
        $orderColumn = 'code';
        $orderDirection = 'asc';

        if (! is_null($request->input('order_by_column')) && ! is_null($request->input('order_by_direction'))) {
            $viewColumns = $this->getViewColumns();
            $columnIndex = $request->input('order_by_column');
            $columnName = $viewColumns[$columnIndex] ?? 'NORMAN SusDat ID';

            $columnMapping = [
                '' => 'id',
                'NORMAN SusDat ID' => 'code',
                'Name' => 'name',
                'CAS_RN' => 'cas_number',
                'StdInChIKey' => 'stdinchikey',
                'DTXSID' => 'dtxid',
                'PubChem_CID' => 'pubchem_cid',
                'Molecular Formula' => 'molecular_formula',
                'SMILES' => 'smiles',
                'Monoisotopic Mass' => 'mass_iso',
            ];

            $orderColumn = $columnMapping[$columnName] ?? 'code';
            $orderDirection = $this->orderByList((int) $request->input('order_by_direction')) ?? 'asc';
        }

        $substances->orderBy($orderColumn, $orderDirection);

        // Log query if not paginating
        if (! $request->has('page')) {
            $this->logQuery($substances, $request, $substancesCount, $categoriesSearch, $sourcesSearch, $substancesSearch);
        }

        // Paginate FIRST, then load relationships
        $substances = $substances->paginate(100)->withQueryString();

        // Get substance IDs from paginated results only
        $substanceIds = $substances->pluck('id')->toArray();

        // Batch load category and source associations for paginated items only
        $categoryAssociations = [];
        $sourceAssociations = [];

        if (! empty($substanceIds)) {
            // Load category associations efficiently
            $categoryAssociations = DB::table('susdat_category_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select('substance_id', 'category_id')
                ->get()
                ->groupBy('substance_id')
                ->map(function ($items) {
                    return $items->pluck('category_id')->sort()->implode('|');
                })
                ->toArray();

            // Load source associations efficiently
            $sourceAssociations = DB::table('susdat_source_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select('substance_id', 'source_id')
                ->get()
                ->groupBy('substance_id')
                ->map(function ($items) {
                    return $items->pluck('source_id')->sort()->implode('|');
                })
                ->toArray();
        }

        // Attach the associations to substances
        foreach ($substances as $substance) {
            $substance->category_ids = $categoryAssociations[$substance->id] ?? null;
            $substance->source_ids = $sourceAssociations[$substance->id] ?? null;
        }

        // Transform sourceAssociations for view compatibility
        $sourceIds = [];
        foreach ($sourceAssociations as $substanceId => $sourceIdString) {
            $sourceIds[$substanceId] = (object) ['source_ids' => $sourceIdString];
        }

        // Load categories and sources for display (cached)
        $categories = Cache::remember('categories_display', 300, function () {
            return Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        });

        $sources = Cache::remember('sources_display', 300, function () {
            return SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        });

        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->sanitized_name;
        }

        $categoriesList = $categories->pluck('name', 'id')->toArray();

        // Build search parameters for display
        $searchParameters = $this->buildSearchParameters($categoriesSearch, $sourcesSearch, $substancesSearch, $categories, $sources);

        // Prepare filter parameters
        $filter = [
            'order_by_direction' => (int) ($request->input('order_by_direction') ?? 0),
            'order_by_column' => $request->input('order_by_column') ?? 1,
        ];

        return view('susdat.index', [
            'columns' => $this->getViewColumns(),
            'substances' => $substances,
            'substancesCount' => $substancesCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->value('id'), // More efficient than first()->id
            'request' => $request,
            'sourceIds' => $sourceIds,
            'activeCategoryids' => $categoriesSearch,
            'activeSourceids' => $sourcesSearch,
            'sources' => $sources,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'categoriesList' => $categoriesList,
            'orderByDirection' => $this->orderByList(),
            'filter' => $filter,
            'searchParameters' => $searchParameters,
        ]);
    }

    /**
     * Log query for monitoring
     */
    private function logQuery($substances, $request, $substancesCount, $categoriesSearch, $sourcesSearch, $substancesSearch)
    {
        $database_key = 'susdat';
        $main_request = [
            'categoriesSearch' => $categoriesSearch,
            'sourcesSearch' => $sourcesSearch,
            'substancesSearch' => $substancesSearch,
            'searchCategory' => $request->input('searchCategory'),
            'searchSource' => $request->input('searchSource'),
            'searchSubstance' => $request->input('searchSubstance'),
            'order_by_column' => $request->input('order_by_column'),
            'order_by_direction' => $request->input('order_by_direction'),
        ];

        $now = now();
        $sql = $substances->toSql();
        $bindings = $substances->getBindings();
        $fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
        $queryHash = hash('sha256', $fullSql);

        // Check for existing count
        $actual_count = QueryLog::where('query_hash', $queryHash)->where('total_count', $substancesCount)->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                'query' => $fullSql,
                'user_id' => Auth::check() ? Auth::id() : null,
                'total_count' => $substancesCount,
                'actual_count' => $actual_count,
                'database_key' => $database_key,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while processing your request.');
        }
    }

    /**
     * Log query for index (list all) view
     */
    private function logIndexQuery($substancesCount): ?int
    {
        $database_key = 'susdat';
        $main_request = [
            'listAll' => true,
            'order_by_column' => 1,
            'order_by_direction' => 0,
        ];

        $sql = 'SELECT * FROM susdat_substances ORDER BY code ASC';
        $queryHash = hash('sha256', $sql);
        $now = now();

        // Check for existing count
        $actual_count = QueryLog::where('query_hash', $queryHash)->where('total_count', $substancesCount)->value('actual_count');

        try {
            $queryLog = QueryLog::create([
                'content' => json_encode(['request' => $main_request, 'bindings' => []]),
                'query' => $sql,
                'user_id' => Auth::check() ? Auth::id() : null,
                'total_count' => $substancesCount,
                'actual_count' => $actual_count ?? $substancesCount,
                'database_key' => $database_key,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $queryLog->id;
        } catch (\Exception $e) {
            Log::error('Failed to log index query: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Build search parameters for display
     */
    private function buildSearchParameters($categoriesSearch, $sourcesSearch, $substancesSearch, $categories, $sources)
    {
        $searchParameters = [];

        if (! empty($categoriesSearch)) {
            $searchParameters['Categories'] = collect($categoriesSearch)
                ->map(function ($id) use ($categories) {
                    return $categories[$id]->name ?? 'Unknown Category';
                })
                ->toArray();
        }

        if (! empty($sourcesSearch)) {
            $searchParameters['Sources'] = collect($sourcesSearch)
                ->map(function ($id) use ($sources) {
                    return ($sources[$id]->code ?? '').' - '.($sources[$id]->name ?? 'Unknown Source');
                })
                ->toArray();
        }

        if (! empty($substancesSearch)) {
            // Fetch substance names for display instead of showing IDs
            $substanceNames = Substance::whereIn('id', $substancesSearch)
                ->select('id', 'code', 'name')
                ->get()
                ->map(function ($substance) {
                    return $substance->prefixed_code.' - '.($substance->name ?? 'Unknown Substance');
                })
                ->toArray();

            $searchParameters['Substances'] = $substanceNames;
        }

        return $searchParameters;
    }

    /**
     * Show substances with code but no name, with option to fetch from NORMAN API.
     */
    public function missingNames()
    {
        $substances = Substance::whereNotNull('code')
            ->where(function ($query) {
                $query->whereNull('name')->orWhere('name', '');
            })
            ->select(['id', 'code', 'name', 'cas_number', 'stdinchikey', 'molecular_formula'])
            ->orderBy('code')
            ->get();

        return view('susdat.missing-names', [
            'substances' => $substances,
        ]);
    }

    /**
     * Fetch missing substance data from the NORMAN SusDat API and update local records.
     */
    public function fetchMissingNames(Request $request)
    {
        $substances = Substance::whereNotNull('code')
            ->where(function ($query) {
                $query->whereNull('name')->orWhere('name', '');
            })
            ->orderBy('code')
            ->get();

        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($substances as $substance) {
            try {
                $nsid = $substance->code;
                $response = Http::timeout(10)->get("https://www.norman-network.com/nds/api/susdat/nsid/{$nsid}/JSON");

                if ($response->successful()) {
                    $data = $response->json();

                    if (! empty($data['Compound name'])) {
                        $substance->name = $data['Compound name'];
                    }
                    if (! empty($data['CAS RN']) && empty($substance->cas_number)) {
                        $substance->cas_number = $data['CAS RN'];
                    }
                    if (! empty($data['SMILES']) && empty($substance->smiles)) {
                        $substance->smiles = $data['SMILES'];
                    }
                    if (! empty($data['InChI string']) && empty($substance->stdinchi)) {
                        $substance->stdinchi = $data['InChI string'];
                    }
                    if (! empty($data['InChIKey']) && empty($substance->stdinchikey)) {
                        $substance->stdinchikey = $data['InChIKey'];
                    }
                    if (! empty($data['PubChem CID']) && empty($substance->pubchem_cid)) {
                        $substance->pubchem_cid = $data['PubChem CID'];
                    }
                    if (! empty($data['DSSTox Substance ID']) && empty($substance->dtxid)) {
                        $substance->dtxid = $data['DSSTox Substance ID'];
                    }
                    if (! empty($data['Molecular formula']) && empty($substance->molecular_formula)) {
                        $substance->molecular_formula = $data['Molecular formula'];
                    }
                    if (! empty($data['Monoisotopic Mass [g/mol]']) && empty($substance->mass_iso)) {
                        $substance->mass_iso = $data['Monoisotopic Mass [g/mol]'];
                    }
                    if (! empty($data['Average Mass [g/mol]']) && empty($substance->average_mass)) {
                        $substance->average_mass = $data['Average Mass [g/mol]'];
                    }

                    $substance->save();
                    $updated++;
                } else {
                    $failed++;
                    $errors[] = "NS{$nsid}: HTTP {$response->status()}";
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "NS{$substance->code}: {$e->getMessage()}";
            }

            // Be respectful to the API
            usleep(200000); // 200ms delay
        }

        session()->flash('success', "Fetched data for {$updated} substances. Failed: {$failed}.");
        if (! empty($errors)) {
            session()->flash('fetch_errors', $errors);
        }

        return redirect()->route('substances.missing-names');
    }

    protected function getEditableColumns()
    {
        return ['code', 'name', 'name_dashboard', 'name_chemspider', 'name_iupac', 'cas_number', 'smiles', 'smiles_dashboard', 'stdinchi', 'stdinchikey', 'pubchem_cid', 'chemspider_id', 'dtxid', 'molecular_formula', 'mass_iso', 'metadata_synonyms', 'metadata_cas', 'metadata_ms_ready', 'metadata_general'];
    }

    private function getSelectColumns()
    {
        return ['id', 'code', 'name', 'cas_number', 'stdinchikey', 'dtxid', 'pubchem_cid', 'molecular_formula', 'smiles', 'mass_iso'];
    }

    private function getViewColumns()
    {
        return [
            '', // Empty header for icons column
            'NORMAN SusDat ID',
            'Name',
            'CAS_RN',
            'StdInChIKey',
            'DTXSID',
            'PubChem_CID',
            'Molecular Formula',
            'SMILES',
            'Monoisotopic Mass',
        ];
    }

    /**
     * Start direct CSV download for SUSDAT data (no queue needed due to manageable dataset size)
     */
    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        // Disable debugbar and query log to prevent memory issues
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        try {
            // Get the query log record
            $queryLog = QueryLog::findOrFail($query_log_id);

            // Generate filename
            $filename = 'susdat_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

            // Get request information for logging
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            // Create an export download record for tracking
            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'database_key' => 'susdat',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly (no queue needed for manageable dataset)
            $startTime = microtime(true);
            $directory = 'exports/susdat';

            // Make sure the directory exists
            Storage::makeDirectory($directory);

            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');

            if (! $handle) {
                throw new \Exception("Unable to open file for writing: {$path}");
            }

            // Write CSV headers (same names and order as the legacy SusDat export)
            $exportColumns = $this->susdatExportColumns();
            fputcsv($handle, array_keys($exportColumns));

            // Parse query log content
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Build WHERE clause for the query
            $whereClause = 'deleted_at IS NULL';

            // Apply filters if not listAll
            if (! ($requestData['listAll'] ?? false)) {
                $categoriesSearch = is_array($requestData['categoriesSearch'] ?? null)
                    ? $requestData['categoriesSearch']
                    : json_decode($requestData['categoriesSearch'] ?? '[]', true);

                $sourcesSearch = is_array($requestData['sourcesSearch'] ?? null)
                    ? $requestData['sourcesSearch']
                    : json_decode($requestData['sourcesSearch'] ?? '[]', true);

                $substancesSearch = is_array($requestData['substancesSearch'] ?? null)
                    ? $requestData['substancesSearch']
                    : json_decode($requestData['substancesSearch'] ?? '[]', true);

                $categoriesSearch = array_filter(array_map('intval', $categoriesSearch ?? []), fn ($id) => $id > 0);
                $sourcesSearch = array_filter(array_map('intval', $sourcesSearch ?? []), fn ($id) => $id > 0);
                $substancesSearch = array_filter(array_map('intval', $substancesSearch ?? []), fn ($id) => $id > 0);

                if (($requestData['searchCategory'] ?? null) == 1 && ! empty($categoriesSearch)) {
                    $ids = implode(',', $categoriesSearch);
                    $whereClause .= " AND id IN (SELECT substance_id FROM susdat_category_substance WHERE category_id IN ({$ids}))";
                } elseif (($requestData['searchSource'] ?? null) == 1 && ! empty($sourcesSearch)) {
                    $ids = implode(',', $sourcesSearch);
                    $whereClause .= " AND id IN (SELECT substance_id FROM susdat_source_substance WHERE source_id IN ({$ids}))";
                } elseif (($requestData['searchSubstance'] ?? null) == 1 && ! empty($substancesSearch)) {
                    $ids = implode(',', $substancesSearch);
                    $whereClause .= " AND id IN ({$ids})";
                }
            }

            // Apply ordering
            $orderColumn = 'code';
            $orderDirection = 'ASC';

            if (! is_null($requestData['order_by_column'] ?? null) && ! is_null($requestData['order_by_direction'] ?? null)) {
                $viewColumns = $this->getViewColumns();
                $columnIndex = $requestData['order_by_column'];
                $columnName = $viewColumns[$columnIndex] ?? 'NORMAN SusDat ID';

                $columnMapping = [
                    '' => 'id',
                    'NORMAN SusDat ID' => 'code',
                    'Name' => 'name',
                    'CAS_RN' => 'cas_number',
                    'StdInChIKey' => 'stdinchikey',
                    'DTXSID' => 'dtxid',
                    'PubChem_CID' => 'pubchem_cid',
                    'Molecular Formula' => 'molecular_formula',
                    'SMILES' => 'smiles',
                    'Monoisotopic Mass' => 'mass_iso',
                ];

                $orderColumn = $columnMapping[$columnName] ?? 'code';
                $orderDirection = strtoupper($this->orderByList((int) $requestData['order_by_direction']) ?? 'asc');
            }

            // Use raw PDO with unbuffered query for memory efficiency
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

            $selectList = implode(",\n                        ", array_values($exportColumns));

            $sql = "SELECT
                        {$selectList}
                    FROM susdat_substances
                    LEFT JOIN (
                        SELECT source_substance.substance_id, string_agg(sources.code, '; ' ORDER BY sources.code) AS source_codes
                        FROM susdat_source_substance AS source_substance
                        JOIN suspect_list_exchange_sources AS sources ON sources.id = source_substance.source_id
                        GROUP BY source_substance.substance_id
                    ) AS source_list ON source_list.substance_id = susdat_substances.id
                    LEFT JOIN (
                        SELECT DISTINCT ON (usepa.substance_id)
                            usepa.substance_id,
                            usepa.\"usepa_Koc_min_experimental\" AS koc_min_experimental,
                            usepa.\"usepa_Koc_max_experimental\" AS koc_max_experimental,
                            usepa.\"usepa_Koc_min_predicted\" AS koc_min_predicted,
                            usepa.\"usepa_Koc_max_predicted\" AS koc_max_predicted
                        FROM susdat_usepa AS usepa
                        ORDER BY usepa.substance_id, usepa.id
                    ) AS koc ON koc.substance_id = susdat_substances.id
                    WHERE {$whereClause}
                    ORDER BY {$orderColumn} {$orderDirection}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $totalExported = 0;
            $codeIndex = array_search('Norman_SusDat_ID', array_keys($exportColumns), true);
            $monoisoMassIndex = array_search('Monoiso_Mass', array_keys($exportColumns), true);
            $averageMassIndex = array_search('average_mass', array_keys($exportColumns), true);

            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $row[$codeIndex] = 'NS'.$row[$codeIndex];
                $row[$monoisoMassIndex] = $row[$monoisoMassIndex] !== null ? number_format((float) $row[$monoisoMassIndex], 6, '.', '') : '';
                $row[$averageMassIndex] = $row[$averageMassIndex] !== null ? rtrim(rtrim((string) $row[$averageMassIndex], '0'), '.') : '';

                fputcsv($handle, $row);
                $totalExported++;
            }

            $stmt->closeCursor();
            fclose($handle);

            // Get file size and processing time
            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            // Update the export download record with completion metrics
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

            Log::info("SUSDAT export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('susdat.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('SUSDAT export failed: '.$e->getMessage());

            // Update export download record if it exists
            if (isset($exportDownload)) {
                $exportDownload->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'completed_at' => Carbon::now(),
                ]);
            }

            session()->flash('error', 'Export failed: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Download the generated CSV file
     */
    public function downloadCsv($filename)
    {
        $directory = 'exports/susdat';
        $path = Storage::path("{$directory}/{$filename}");

        // Debug logging for file availability
        Log::info("Download request for: {$filename}", [
            'path' => $path,
            'exists' => file_exists($path),
            'directory_contents' => Storage::files($directory),
        ]);

        if (! file_exists($path)) {
            // Try to find similar files for debugging
            $similarFiles = collect(Storage::files($directory))
                ->filter(function ($file) use ($filename) {
                    $fileBasename = basename($file);
                    $requestBasename = basename($filename);

                    // Check if the filename pattern matches (same user and similar timestamp)
                    return str_contains($fileBasename, explode('_', $requestBasename)[3] ?? '') ||
                           str_contains($fileBasename, explode('_', $requestBasename)[2] ?? '');
                })
                ->values();

            Log::warning("File not found: {$filename}", [
                'path' => $path,
                'similar_files' => $similarFiles->toArray(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist. It may have expired or failed to generate.',
                'similar_files' => $similarFiles->map(function ($file) {
                    return basename($file);
                }),
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Columns of the SusDat CSV export: legacy export header => SQL expression.
     *
     * Names and order follow the legacy SusDat export; Validation_Level is left out
     * because it was never imported. Values kept in the JSON metadata columns are read
     * under the keys the legacy import stored them with.
     *
     * @return array<string, string>
     */
    private function susdatExportColumns(): array
    {
        $json = fn (string $column, string $key): string => "{$column}->>'".str_replace("'", "''", $key)."'";

        $general = [
            'M+H+' => '[M+H]+',
            'M-H-' => '[M-H]-',
            'Pred_RTI_Positive_ESI' => 'Pred_RTI_Positive_ESI',
            'Uncertainty_RTI_pos' => 'Uncertainty_RTI_pos',
            'Pred_RTI_Negative_ESI' => 'Pred_RTI_Negative_ESI',
            'Uncertainty_RTI_neg' => 'Uncertainty_RTI_neg',
            'Tetrahymena_pyriformis_toxicity' => 'Tetrahymena_pyriformis_toxicity',
            'IGC50_48_hr_ug/L' => 'IGC50_48_hr_ug/L',
            'Uncertainty_Tetrahymena_pyriformis_toxicity' => 'Uncertainty_Tetrahymena_pyriformis_toxicity',
            'Daphnia_toxicity' => 'Daphnia_toxicity',
            'LC50_48_hr_ug/L' => 'LC50_48_hr_ug/L',
            'Uncertainty_Daphnia_toxicity' => 'Uncertainty_Daphnia_toxicity',
            'Algae_toxicity' => 'Algae_toxicity',
            'EC50_72_hr_ug/L' => 'EC50_72_hr_ug/L',
            'Uncertainty_Algae_toxicity' => 'Uncertainty_Algae_toxicity',
            'Pimephales_promelas_toxicity' => 'Pimephales_promelas_toxicity',
            'LC50_96_hr_ug/L' => 'LC50_96_hr_ug/L',
            'Uncertainty_Pimephales_promelas_toxicity' => 'Uncertainty_Pimephales_promelas_toxicity',
            'logKow_EPISuite' => 'logKow_EPISuite',
            'Exp_logKow_EPISuite' => 'Exp_logKow_EPISuite',
            'alogp_ChemSpider' => 'alogp_ChemSpider',
            'xlogp_ChemSpider' => 'xlogp_ChemSpider',
            'Species' => 'Species',
            'Uncertainty' => 'Uncertainty',
            'ExposureScore_Water_KEMI' => 'ExposureScore_Water_KEMI',
            'HazScore_EcoChronic_KEMI' => 'HazScore_EcoChronic_KEMI',
            'ValidationLevel_KEMI' => 'ValidationLevel_KEMI',
            'Prob. of GC' => 'Prob_of_GC',
            'Prob. RPLC' => 'Prob_RPLC',
            'Pred. Chromatography' => 'Pred_Chromatography',
            'Prob. of both Ionization Source' => 'Prob_of_both_Ionization_Source',
            'Prob. EI' => 'Prob_EI',
            'Prob. ESI' => 'Prob_ESI',
            'Pred. Ionization source' => 'Pred_Ionization_source',
            'Prob. both ESI mode' => 'Prob_both_ESI_mode',
            'Prob. +ESI' => 'Prob_plusESI',
            'Prob. -ESI' => 'Prob_minusESI',
            'Pred. ESI mode' => 'Pred_ESI_mode',
            'Preferable Platform by decision Tree' => 'Preferable_Platform_by_decision_Tree',
        ];

        $columns = [
            'Norman_SusDat_ID' => 'code',
            'Name' => 'name',
            'Name_Dashboard' => 'name_dashboard',
            'Name_ChemSpider' => 'name_chemspider',
            'Name_IUPAC' => 'name_iupac',
            'Synonyms_ChemSpider' => $json('metadata_synonyms', 'Synonyms ChemSpider'),
            'Reliability_of_Synonyms_ChemSpider' => $json('metadata_synonyms', 'Reliability of Synonyms ChemSpider'),
            'CAS_RN' => 'cas_number',
            'CAS_RN_Dashboard' => $json('metadata_cas', 'CAS_RN Dashboard'),
            'CAS_RN_PubChem' => $json('metadata_cas', 'CAS_RN PubChem'),
            'CAS_RN_Cactus' => $json('metadata_cas', 'CAS_RN Cactus'),
            'CAS_RN_ChemSpider' => $json('metadata_cas', 'CAS_RN ChemSpider'),
            'Reliability_of_CAS_ChemSpider' => $json('metadata_cas', 'Reliability of CAS_ChemSpider'),
            'SMILES' => 'smiles',
            'SMILES_Dashboard' => 'smiles_dashboard',
            'StdInChI' => 'stdinchi',
            'StdInChIKey' => 'stdinchikey',
            'MS_Ready_SMILES' => $json('metadata_ms_ready', 'MS_Ready_SMILES'),
            'MS_Ready_StdInChI' => $json('metadata_ms_ready', 'MS_Ready_StdInChI'),
            'MS_Ready_StdInChIKey' => $json('metadata_ms_ready', 'MS_Ready_StdInChIKey'),
            'Source' => 'source_list.source_codes',
            'PubChem_CID' => 'pubchem_cid',
            'ChemSpiderID' => 'chemspider_id',
            'DTXSID' => 'dtxid',
            'Molecular_Formula' => 'molecular_formula',
            'Monoiso_Mass' => 'mass_iso',
            'average_mass' => 'average_mass',
        ];

        foreach ($general as $header => $key) {
            $columns[$header] = $json('metadata_general', $key);
        }

        return $columns + [
            'Koc_min_experimental (L/kg)' => 'koc.koc_min_experimental',
            'Koc_max_experimental (L/kg)' => 'koc.koc_max_experimental',
            'Koc_min_predicted (L/kg)' => 'koc.koc_min_predicted',
            'Koc_max_predicted (L/kg)' => 'koc.koc_max_predicted',
        ];
    }

    /**
     * Format bytes to human-readable file size
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
