<?php

namespace App\Http\Controllers\Factsheet;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class FactsheetHomeController extends Controller
{
    public function index()
    {
        // here we will only redirect to the filter page, where the user will choose substance via livewire factsheet/SubstanceSearch component
        return redirect()->route('factsheets.search.filter');
    }

    public function countAll(): RedirectResponse
    {
        app(RefreshDatabaseEntityCountsAction::class)->handle(['factsheets']);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
