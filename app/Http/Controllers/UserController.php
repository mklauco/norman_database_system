<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Backend\Project;
use Illuminate\Routing\Controllers\HasMiddleware;

class UserController extends Controller implements HasMiddleware
{

  public static function middleware(): array
  {
      return [
          // examples with aliases, pipe-separated names, guards, etc:
          'role_or_permission:super_admin|admin|user_manager',
          // new Middleware('role:author', only: ['index']),
          // new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('manager'), except:['show']),
          // new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('delete records,api'), only:['destroy']),
      ];
  }

  /**
  * Display a listing of the resource.
  */
  public function index()
  {
    //
    $users = User::with('roles', 'permissions', 'projects')->orderBy('last_name', 'desc')->get();
    $usersWithTokens = [];
    foreach ($users as $user) {
      $tokensCount = $user->tokens()->count();
      $usersWithTokens[$user->id] = $tokensCount;
    }
    // dd($users);
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
    return view('dashboard.users.upsert', [
      'edit' => false,
    ]);
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
    return view('dashboard.users.upsert', [
      'edit' => true,
      'user' => User::with('projects')->find($id),
      'roles' => \Spatie\Permission\Models\Role::all(),
      'projects' => Project::all(),
    ]);    
  }
  
  /**
  * Update the specified resource in storage.
  */
  public function update(Request $request, string $id)
  {
    //
    $validation_array = [
      'first_name'    => 'required',
      'last_name'     => 'required',
      'email'         => 'required',
      'roles'         => 'required',
      'projects'      => 'required',
    ];
    $request->validate($validation_array);

    $user = User::find($id);
    $user->first_name = $request['first_name'];
    $user->last_name = $request['last_name'];
    $user->email = $request['email'];
    $user->syncRoles($request['roles']);
    $user->projects()->sync($request['projects']);
    $user->save();
    return redirect()->route('users.index');
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
      'projects',
    ];
  }
}
