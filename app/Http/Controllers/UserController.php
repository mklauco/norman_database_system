<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index()
  {
    //
    $users = User::with('roles', 'permissions')->orderBy('last_name', 'desc')->get();
    $usersWithTokens = [];
    foreach ($users as $user) {
      $tokensCount = $user->tokens()->count();
      $usersWithTokens[$user->id] = $tokensCount;
    }
    return view('dashboard.users.index', [
      'users' => $users,
      'columns' => $this->getVisibleColumns(),
      'usersWithTokens' => $usersWithTokens,
    ]);
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

  public function getVisibleColumns()
  {
    return [
      'id',
      'first_name',
      'last_name',
      'email',
      'roles',
      'number_of_api_tokens',
    ];
  }
}
