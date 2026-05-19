<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\List\AnalyticalMethod;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\Country;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\Matrix;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\TypeDataSource;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use Illuminate\Http\Request;

class QueryLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //

        // obtain individual list to display search parameters:
        // $countries = Country::all()->pluck('name', 'id')->keyBy('id');
        $countries = Country::all()->pluck('name', 'id');
        $matrices = Matrix::all()->pluck('name', 'id');
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code.' - '.$s->name;
        }
        $sources = $sourceList;

        $categories = Category::all()->pluck('name', 'id');
        $typeDataSources = TypeDataSource::all()->pluck('name', 'id');
        $concentrationIndicators = ConcentrationIndicator::all()->pluck('name', 'id');
        $dataSourceOrganisations = DataSourceOrganisation::all()->pluck('name', 'id');
        $dataSourceLaboratories = DataSourceLaboratory::all()->pluck('name', 'id');
        $analyticalMethods = AnalyticalMethod::all()->pluck('name', 'id');
        $qualityAnalyticalMethods = QualityEmpodatAnalyticalMethods::all()->pluck('name', 'id');

        // Get all database entities for module filtering
        $databaseEntities = DatabaseEntity::where('show_in_dashboard', true)
            ->orderBy('name')
            ->get();

        // Build modules array for dropdown
        $modules = ['' => 'All'];
        foreach ($databaseEntities as $entity) {
            $modules[$entity->code] = $entity->name;
        }

        // $queries = QueryLog::with('users')->orderBy('id', 'desc')->paginate(20);
        // get max id from query log
        $maxId = QueryLog::max('id');

        $activeModule = $request->query('module');

        $query = QueryLog::with('users')
            ->where('id', '>=', max($maxId - 100, 0));

        if (! empty($activeModule)) {
            if ($activeModule === 'arbg') {
                $query->where('database_key', 'like', 'arbg.%');
            } else {
                $query->where('database_key', 'like', $activeModule.'%');
            }
        }

        $queries = $query->orderBy('id', 'desc')->paginate(20);

        // dd($queries);
        return view('backend.querylog.index', [
            'queries' => $queries,
            'countries' => $countries,
            'matrices' => $matrices,
            'sources' => $sources,
            'categories' => $categories,
            'typeDataSources' => $typeDataSources,
            'concentrationIndicators' => $concentrationIndicators,
            'dataSourceOrganisations' => $dataSourceOrganisations,
            'dataSourceLaboratories' => $dataSourceLaboratories,
            'analyticalMethods' => $analyticalMethods,
            'qualityAnalyticalMethods' => $qualityAnalyticalMethods,
            'activeModule' => $activeModule,
            'modules' => $modules,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
