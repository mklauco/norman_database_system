<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\TypeOfNumericQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypeOfNumericQuantityController extends Controller
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
        $typeOfNumericQuantities = TypeOfNumericQuantity::orderBy('id')->paginate(25);

        return view('literature.type_of_numeric_quantities.index', compact('typeOfNumericQuantities'));
    }

    public function download()
    {
        $typeOfNumericQuantities = TypeOfNumericQuantity::orderBy('id')->get();

        $filename = 'type_of_numeric_quantities_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($typeOfNumericQuantities) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($typeOfNumericQuantities as $item) {
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
        $typeOfNumericQuantity = new TypeOfNumericQuantity;
        $isCreate = true;

        return view('literature.type_of_numeric_quantities.upsert', compact('typeOfNumericQuantity', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_type_of_numeric_quantities,name',
        ]);

        TypeOfNumericQuantity::create($validated);

        return redirect()->route('literature.type_of_numeric_quantities.index')
            ->with('success', 'Type of numeric quantity created successfully.');
    }

    public function edit(TypeOfNumericQuantity $typeOfNumericQuantity)
    {
        $isCreate = false;

        return view('literature.type_of_numeric_quantities.upsert', compact('typeOfNumericQuantity', 'isCreate'));
    }

    public function update(Request $request, TypeOfNumericQuantity $typeOfNumericQuantity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_type_of_numeric_quantities,name,'.$typeOfNumericQuantity->id,
        ]);

        $typeOfNumericQuantity->update($validated);

        return redirect()->route('literature.type_of_numeric_quantities.index')
            ->with('success', 'Type of numeric quantity updated successfully.');
    }
}
