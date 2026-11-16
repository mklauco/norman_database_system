<?php

namespace App\Http\Controllers\Ecotox;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Http\Controllers\Controller;
use App\Models\Ecotox\EcotoxSubstanceDistinct;
use App\Models\Ecotox\EcotoxSubstanceDistinctPnec3;
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
        app(RefreshDatabaseEntityCountsAction::class)->handle(['ecotox', 'ecotox.ecotox', 'ecotox.pnec']);

        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
