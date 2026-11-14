<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Hazards\ComptoxSubstanceData;
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
        $active = ComptoxSubstanceData::query()->active();

        DatabaseEntity::where('code', 'hazards')->update([
            'last_update' => (clone $active)->max('updated_at'),
            'number_of_records' => (clone $active)->count(),
        ]);

        session()->flash('success', 'Hazards database counts updated successfully');

        return redirect()->back();
    }
}
