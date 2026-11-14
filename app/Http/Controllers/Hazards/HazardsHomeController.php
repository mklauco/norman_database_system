<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Hazards\ComptoxSubstanceData;
use Illuminate\Http\Request;

class HazardsHomeController extends Controller
{
    public function index()
    {
        return view('hazards.home.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function countAll()
    {
        DatabaseEntity::where('code', 'hazards')->update([
            'last_update' => ComptoxSubstanceData::max('updated_at'),
            'number_of_records' => ComptoxSubstanceData::count(),
        ]);

        session()->flash('success', 'Hazards database counts updated successfully');
        return redirect()->back();
    }
}
