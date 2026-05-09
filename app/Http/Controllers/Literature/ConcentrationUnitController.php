<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\ConcentrationUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConcentrationUnitController extends Controller
{
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'literature')->first();
        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }
        if ($databaseEntity->is_public === true) {
            return;
        }
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }
        $user = Auth::user();
        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('literature')) {
            return;
        }
        abort(403, 'You do not have permission to access this module.');
    }

    public function index()
    {
        $concentrationUnits = ConcentrationUnit::orderBy('id')->paginate(25);

        return view('literature.concentration_units.index', compact('concentrationUnits'));
    }

    public function download()
    {
        $concentrationUnits = ConcentrationUnit::orderBy('id')->get();

        $filename = 'concentration_units_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($concentrationUnits) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($concentrationUnits as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $concentrationUnit = new ConcentrationUnit;
        $isCreate = true;

        return view('literature.concentration_units.upsert', compact('concentrationUnit', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_concentration_units,name',
        ]);

        ConcentrationUnit::create($validated);

        return redirect()->route('literature.concentration_units.index')
            ->with('success', 'Concentration unit created successfully.');
    }

    public function edit(ConcentrationUnit $concentrationUnit)
    {
        $isCreate = false;

        return view('literature.concentration_units.upsert', compact('concentrationUnit', 'isCreate'));
    }

    public function update(Request $request, ConcentrationUnit $concentrationUnit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_concentration_units,name,'.$concentrationUnit->id,
        ]);

        $concentrationUnit->update($validated);

        return redirect()->route('literature.concentration_units.index')
            ->with('success', 'Concentration unit updated successfully.');
    }
}
