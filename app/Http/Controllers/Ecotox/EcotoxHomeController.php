<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\EcotoxFinal;
use App\Models\Ecotox\EcotoxSubstanceDistinct;
use App\Models\Ecotox\EcotoxSubstanceDistinctPnec3;
use App\Models\Ecotox\LowestPNEC;
use Illuminate\Http\Request;

class EcotoxHomeController extends Controller
{
    public function index()
    {
        //
        return view('ecotox.home.index');
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

    public function syncNewSubstances()
    {

        try {
            // Call the static method from the model
            $newCount = EcotoxSubstanceDistinct::syncNewSubstances();

            // Return success response
            session()->flash('success', 'Database counts updated successfully');

            return redirect()->back();
        } catch (\Exception $e) {
            // Return error response
            session()->flash('failure', 'Query logging error: '.$e->getMessage());

            return redirect()->back();
        }

    }

    public function syncNewSubstancesPnec3()
    {
        try {
            // Call the static method from the PNEC3 model
            $newCount = EcotoxSubstanceDistinctPnec3::syncNewSubstances();

            // Return success response
            session()->flash('success', 'PNEC3 distinct substances synced successfully. '.$newCount.' new substances added.');

            return redirect()->back();
        } catch (\Exception $e) {
            // Return error response
            session()->flash('failure', 'PNEC3 sync error: '.$e->getMessage());

            return redirect()->back();
        }
    }

    public function countAll()
    {
        DatabaseEntity::where('code', 'ecotox.ecotox')->update([
            'last_update' => EcotoxFinal::max('updated_at'),
            'number_of_records' => EcotoxFinal::count(),
        ]);

        DatabaseEntity::where('code', 'ecotox.pnec')->update([
            'last_update' => LowestPNEC::max('updated_at'),
            'number_of_records' => LowestPNEC::count(),
        ]);

        DatabaseEntity::where('code', 'ecotox')->update([
            'last_update' => LowestPNEC::max('updated_at'),
            'number_of_records' => LowestPNEC::count() + EcotoxFinal::count(),
        ]);

        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
