<?php

namespace App\Http\Controllers\Empodat;

use App\Http\Controllers\Controller;
use App\Models\Empodat\EmpodatStation;
use App\Models\List\Country;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class StationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'role:super_admin',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);

        $stations = EmpodatStation::with(['countryRelation', 'countryOtherRelation'])
            ->orderBy('id')
            ->paginate($perPage);

        // Handle out-of-range page requests
        if ($request->has('page') && $stations->currentPage() > $stations->lastPage() && $stations->lastPage() > 0) {
            return redirect()->route('backend.empodat.stations.index', ['page' => $stations->lastPage(), 'per_page' => $perPage]);
        }

        return view('empodat.stations.index', [
            'stations' => $stations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('empodat.stations.upsert', [
            'station' => null,
            'countries' => $countries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:list_countries,id',
            'country_other_id' => 'nullable|exists:list_countries,id',
            'country' => 'nullable|string|max:255',
            'country_other' => 'nullable|string|max:255',
            'national_name' => 'nullable|string|max:255',
            'short_sample_code' => 'nullable|string|max:255',
            'sample_code' => 'nullable|string|max:255',
            'provider_code' => 'nullable|string|max:255',
            'code_ec_wise' => 'nullable|string|max:255',
            'code_ec_other' => 'nullable|string|max:255',
            'code_other' => 'nullable|string|max:255',
            'specific_locations' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        EmpodatStation::create($validated);

        return redirect()->route('empodat.stations.index')
            ->with('success', 'Station created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmpodatStation $station)
    {
        $station->load('countryRelation');

        return view('empodat.stations.show', [
            'station' => $station,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmpodatStation $station)
    {
        $countries = Country::orderBy('name')->get();

        return view('empodat.stations.upsert', [
            'station' => $station,
            'countries' => $countries,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmpodatStation $station)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:list_countries,id',
            'country_other_id' => 'nullable|exists:list_countries,id',
            'country' => 'nullable|string|max:255',
            'country_other' => 'nullable|string|max:255',
            'national_name' => 'nullable|string|max:255',
            'short_sample_code' => 'nullable|string|max:255',
            'sample_code' => 'nullable|string|max:255',
            'provider_code' => 'nullable|string|max:255',
            'code_ec_wise' => 'nullable|string|max:255',
            'code_ec_other' => 'nullable|string|max:255',
            'code_other' => 'nullable|string|max:255',
            'specific_locations' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $station->update($validated);

        return redirect()->route('empodat.stations.index')
            ->with('success', 'Station updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmpodatStation $station)
    {
        $station->delete();

        return redirect()->route('empodat.stations.index')
            ->with('success', 'Station deleted successfully.');
    }
}
