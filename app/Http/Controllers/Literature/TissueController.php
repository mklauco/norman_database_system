<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\Tissue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TissueController extends Controller
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
        $tissues = Tissue::with('subcategories')->orderBy('id')->paginate(25);

        return view('literature.tissues.index', compact('tissues'));
    }

    public function downloadCategories()
    {
        $tissues = Tissue::orderBy('id')->get();

        $filename = 'tissue_categories_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tissues) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($tissues as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSubcategories()
    {
        $tissues = Tissue::with('subcategories')->orderBy('id')->get();

        $filename = 'tissue_subcategories_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tissues) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'main_category_name', 'category_id', 'subcategory_name']);

            // Collect all rows for sorting
            $rows = [];
            foreach ($tissues as $item) {
                if ($item->subcategories && $item->subcategories->count() > 0) {
                    foreach ($item->subcategories as $subcategory) {
                        $rows[] = [
                            'id' => $subcategory->id,
                            'main_category_name' => $item->name, // tissue name from list_tissues
                            'category_id' => $item->id,
                            'subcategory_name' => $subcategory->name,
                        ];
                    }
                }
            }

            // Sort by category_id first, then by subcategory_name alphabetically
            usort($rows, function ($a, $b) {
                if ($a['category_id'] === $b['category_id']) {
                    return strcasecmp($a['subcategory_name'], $b['subcategory_name']);
                }

                return $a['category_id'] - $b['category_id'];
            });

            // Write sorted rows to CSV
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['id'],
                    $row['main_category_name'],
                    $row['category_id'],
                    $row['subcategory_name'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $tissue = new Tissue;
        $isCreate = true;

        return view('literature.tissues.upsert', compact('tissue', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_tissues,name',
        ]);

        Tissue::create($validated);

        return redirect()->route('literature.tissues.index')
            ->with('success', 'Tissue created successfully.');
    }

    public function edit(Tissue $tissue)
    {
        $isCreate = false;

        return view('literature.tissues.upsert', compact('tissue', 'isCreate'));
    }

    public function update(Request $request, Tissue $tissue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_tissues,name,'.$tissue->id,
        ]);

        $tissue->update($validated);

        return redirect()->route('literature.tissues.index')
            ->with('success', 'Tissue updated successfully.');
    }
}
