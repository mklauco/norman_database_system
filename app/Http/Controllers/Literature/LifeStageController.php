<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\LifeStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LifeStageController extends Controller
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
        $lifeStages = LifeStage::orderBy('id')->paginate(25);

        return view('literature.life_stages.index', compact('lifeStages'));
    }

    public function download()
    {
        $lifeStages = LifeStage::orderBy('id')->get();

        $filename = 'life_stages_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($lifeStages) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($lifeStages as $item) {
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
        $lifeStage = new LifeStage;
        $isCreate = true;

        return view('literature.life_stages.upsert', compact('lifeStage', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_life_stages,name',
        ]);

        LifeStage::create($validated);

        return redirect()->route('literature.life_stages.index')
            ->with('success', 'Life stage created successfully.');
    }

    public function edit(LifeStage $lifeStage)
    {
        $isCreate = false;

        return view('literature.life_stages.upsert', compact('lifeStage', 'isCreate'));
    }

    public function update(Request $request, LifeStage $lifeStage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_life_stages,name,'.$lifeStage->id,
        ]);

        $lifeStage->update($validated);

        return redirect()->route('literature.life_stages.index')
            ->with('success', 'Life stage updated successfully.');
    }
}
