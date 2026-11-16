<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hazards;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HazardsHomeController extends Controller
{
    public function index(): View
    {
        return view('hazards.home.index');
    }

    /**
     * Update the hazards row of database_entities from the active substance data rows.
     */
    public function countAll(): RedirectResponse
    {
        app(RefreshDatabaseEntityCountsAction::class)->handle(['hazards']);

        session()->flash('success', 'Hazards database counts updated successfully');

        return redirect()->back();
    }
}
