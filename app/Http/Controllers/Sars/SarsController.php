<?php

namespace App\Http\Controllers\Sars;

use Illuminate\Http\Request;
use App\Models\Sars\SarsMain;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Http\Controllers\Controller;

class SarsController extends Controller
{
  //
  public function filter(Request $request)
  {
    // extract distinct values of the country column from SarsMain model
    $countryList = SarsMain::distinct()
    ->orderBy('name_of_country')
    ->get(['name_of_country'])
    ->pluck('name_of_country')
    ->mapWithKeys(function ($item) {
      return [$item => $item];
    });
    $matrixList = SarsMain::distinct()
    ->orderBy('sample_matrix')
    ->get(['sample_matrix'])
    ->pluck('sample_matrix')
    ->mapWithKeys(function ($item) {
      return [$item => $item];
    });
    
    $laboratoryList = SarsMain::distinct()
    ->orderBy('data_provider')
    ->get(['data_provider'])
    ->pluck('data_provider')
    ->mapWithKeys(function ($item) {
      return [$item => $item];
    });
    
    return view('sars.filter', [
      'request' => $request,
      'countryList' => $countryList,
      'matrixList' => $matrixList,
      'laboratoryList' => $laboratoryList,
    ]);
  }
  
  public function search(Request $request){
    
    if(is_array($request->input('countrySearch'))){
      $countrySearch = $request->input('countrySearch');
    } else{
      $countrySearch = json_decode($request->input('countrySearch'));
    }
    
    if(is_array($request->input('matrixSearch'))){
      $matrixSearch = $request->input('matrixSearch');
    } else{
      $matrixSearch = json_decode($request->input('matrixSearch'));
    }
    
    $sarsObjects = SarsMain::query();

    $searchParameters = [];
    if (!empty($countrySearch)) {
      $sarsObjects = $sarsObjects->whereIn('name_of_country', $countrySearch);
      $searchParameters['countrySearch'] = $countrySearch;
    }
    
    if (!empty($matrixSearch)) {
      $sarsObjects = $sarsObjects->whereIn('sample_matrix', $matrixSearch);
      $searchParameters['matrixSearch'] = $matrixSearch;
    }
    
    $database_key = 'sars';
    $sarsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records;

    $main_request = [
      'countrySearch'                   => $countrySearch,
      'matrixSearch'                    => $matrixSearch,
      'displayOption'                   => $request->input('displayOption'),
      'year_from'                       => $request->input('year_from'),
      'year_to'                         => $request->input('year_to'),
    ];

    if(!$request->has('page')){
      $now = now();
      $bindings = $sarsObjects->getBindings();
      $sql = vsprintf(str_replace('?', "'%s'", $sarsObjects->toSql()), $bindings);
      // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
      $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $sarsObjectsCount)->value('actual_count');
      
      try {
        QueryLog::insert([
          'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
          'query' => $sql,
          'user_id' => auth()->check() ? auth()->id() : null,
          'total_count' => $sarsObjectsCount,
          'actual_count' => is_null($actual_count) ? null : $actual_count,
          'database_key' => $database_key,
          'query_hash' => hash('sha256', $sql),
          'created_at' => $now,
          'updated_at' => $now,
        ]);
      } catch (\Exception $e) {
        // dd($e, hash('sha256', $sql));
        session()->flash('error', 'An error occurred while processing your request.');
      }
    }

    if ($request->displayOption == 1) {
      // use simple pagination
      $sarsObjects = $sarsObjects->orderBy('id', 'asc')
      ->simplePaginate(200)
      ->withQueryString();
    } else {
      // use cursor pagination
      $sarsObjects = $sarsObjects->orderBy('id', 'asc')
      ->paginate(200)
      ->withQueryString();
    }
    
    return view('sars.index', [
      'sarsObjects' => $sarsObjects,
      'sarsObjectsCount' => $sarsObjectsCount,
      'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
      'request' => $request,
      'searchParameters' => $searchParameters,
    ], $main_request);
  }
  
}
