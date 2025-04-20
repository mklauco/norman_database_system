<?php

namespace App\Http\Controllers\Indoor;

use App\Models\Indoor\IndoorMain;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndoorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function filter(Request $request){
        $countryList = IndoorMain::distinct('country')->orderBy('country')->pluck('country', 'country')->toArray();
        
        
        return view('indoor.filter', [
            'request' => $request,
            'countryList'      => $countryList,
        ]);
    }

    public function search(Request $request){

        $resultsObjects = IndoorMain::orderBy('id')->limit(100);


        if ($request->displayOption == 1) {
            // use simple pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->simplePaginate(200)
            ->withQueryString();
        } else {
            // use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->paginate(200)
            ->withQueryString();
        }

        $main_request = $request->all();
        $searchParameters = [];
        return view('indoor.index', [
            'resultsObjects'      => $resultsObjects,
            // 'resultsObjectsCount' => $resultsObjectsCount,
            // 'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);

    }
}
