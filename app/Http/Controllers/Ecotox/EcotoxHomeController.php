<?php

namespace App\Http\Controllers\Ecotox;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ecotox\EcotoxSubstanceDistinct;


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
        {
            try {
                // Call the static method from the model
                $newCount = EcotoxSubstanceDistinct::syncNewSubstances();
                
                // Return success response
                session()->flash('success', 'Database counts updated successfully');
                return redirect()->back();
            } catch (\Exception $e) {
                // Return error response
                session()->flash('failure', 'Query logging error: ' . $e->getMessage());
                return redirect()->back();
            }
        }
    }

}
