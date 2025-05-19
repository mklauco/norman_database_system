<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
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
  public function index(Request $request)
  {
    $perPage = $request->input('per_page', 50);
    
    $users = User::with(['roles', 'permissions', 'projects'])
    ->orderBy('last_name', 'desc')
    ->paginate($perPage);
    
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
  
  // In UserController.php
  // In UserController.php
  public function getUserData(Request $request)
  {
    $perPage = $request->input('per_page', 25);
    $search = $request->input('search', '');
    $sort = $request->input('sort', 'id');
    $direction = $request->input('direction', 'asc');
    $role = $request->input('role', '');
    
    \Log::debug('User data search parameters', [
      'search' => $search,
      'sort' => $sort,
      'direction' => $direction,
      'role' => $role
    ]);
    
    $query = User::with(['roles', 'projects'])
    ->withCount('tokens')->orderby('last_name', 'asc');
    
    // Apply search
    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('first_name', 'like', "%{$search}%")
        ->orWhere('last_name', 'like', "%{$search}%")
        ->orWhere('email', 'like', "%{$search}%");
      });
      
      \Log::debug('Applied search filter', ['sql' => $query->toSql()]);
    }
    
    // Apply sorting
    if ($sort === 'roles') {
      // Handle special case for role sorting
      $query->leftJoin('model_has_roles', function($join) {
        $join->on('users.id', '=', 'model_has_roles.model_id')
        ->where('model_has_roles.model_type', '=', User::class);
      })
      ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
      ->select('users.*')
      ->orderBy('roles.name', $direction)
      ->groupBy('users.id');
    } else {
      // Handle all other columns
      if (in_array($sort, ['id', 'first_name', 'last_name', 'email'])) {
        $query->orderBy($sort, $direction);
      }
    }
    
    $results = $query->paginate($perPage);
    
    \Log::debug('User data results', [
      'total' => $results->total(), 
      'current_page' => $results->currentPage(),
      'per_page' => $results->perPage()
    ]);
    
    return response()->json($results);
  }
  
  /**
  * Show the form for creating a new resource.
  */
  public function create()
  {
    //
    return view('dashboard.users.upsert', [
      'edit' => false,
      'roles' => \Spatie\Permission\Models\Role::all(),
      'projects' => Project::all(),
    ]);
  }
  
  /**
  * Store a newly created resource in storage.
  */
  public function store(Request $request)
  {
    //
    //
    $validation_array = [
      'first_name'    => 'required',
      'last_name'     => 'required',
      'email'         => 'required',
      'roles'         => 'required',
      'projects'      => 'required',
    ];
    $request->validate($validation_array);
    $temporary_password = Str::random(12);
    $user = New User();
    $user->first_name = $request['first_name'];
    $user->last_name = $request['last_name'];
    $user->email = $request['email'];
    $user->password = bcrypt($temporary_password);
    $user->syncRoles($request['roles']);
    try {
      $user->save();
      // other operations
      return redirect()->route('users.index')->with('success', 'User updated successfully');
    } catch (\Illuminate\Database\QueryException $e) {
      if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry error code
        return redirect()->back()->with('error', 'A user with this email already exists.')
        ->withInput();
      }
      return redirect()->back()->with('error', 'Database error: ' . $e->getMessage())
      ->withInput();
    } catch (\Exception $e) {
      return redirect()->back()->with('error', 'An unexpected error occurred: ' . $e->getMessage())
      ->withInput();
    }
  }
  
  /**
  * Display the specified resource.
  */
  public function show(string $id)
  {
    // Find the user with their relationships loaded
    $user = User::with([
      'roles',
      'projects',
      'organisation',
      // 'country',
      'tokens'
      ])->findOrFail($id);
      
      // Check if user has permission to view this user
      if (!auth()->user()->hasRole('super_admin') && 
      !auth()->user()->hasRole('admin') && 
      auth()->id() != $user->id) {
        abort(403, 'Unauthorized action.');
      }
      
      // You could also load recent activity if you have an activity logging system
      // For example:
      // $recentActivity = Activity::where('causer_id', $user->id)
      //                          ->latest()
      //                          ->take(5)
      //                          ->get();
      
      return view('dashboard.users.show', [
        'user' => $user,
        // 'recentActivity' => $recentActivity ?? [] 
      ]);
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
      try {
        $user->save();
        // other operations
        return redirect()->route('users.index')->with('success', 'User updated successfully');
      } catch (\Illuminate\Database\QueryException $e) {
        if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry error code
          return redirect()->back()->with('error', 'A user with this email already exists.')
          ->withInput();
        }
        return redirect()->back()->with('error', 'Database error: ' . $e->getMessage())
        ->withInput();
      } catch (\Exception $e) {
        return redirect()->back()->with('error', 'An unexpected error occurred: ' . $e->getMessage())
        ->withInput();
      }
    }
    
    /**
    * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
      //
      // the ability to delete user is not permitted
    }
    
    public function toggleStatus(string $id)
    {
      $user = User::findOrFail($id);
      $user->active = !$user->active;
      $user->save();
      
      $status = $user->active ? 'activated' : 'deactivated';
      return redirect()->back()->with('success', "User {$status} successfully.");
    }
    
    public function resetPassword(string $id)
    {
      $user = User::findOrFail($id);
      $temporary_password = Str::random(12);
      $user->password = bcrypt($temporary_password);
      $user->save();
      
      // Consider sending an email with the new password
      
      return redirect()->back()->with('success', 'Password reset successfully. Temporary password: ' . $temporary_password);
    }
    
    public function getApiTokens(string $id)
    {
      $user = User::findOrFail($id);
      $tokens = $user->tokens;
      
      return view('dashboard.users.tokens', [
        'user' => $user,
        'tokens' => $tokens,
      ]);
    }
    
    public function createApiToken(Request $request, string $id)
    {
      $request->validate([
        'token_name' => 'required|string|max:255',
      ]);
      
      $user = User::findOrFail($id);
      $token = $user->createToken($request->token_name);
      
      return redirect()->back()->with('success', 'Token created successfully: ' . $token->plainTextToken);
    }
    
    public function revokeApiToken(string $tokenId)
    {
      $token = \Laravel\Sanctum\PersonalAccessToken::findOrFail($tokenId);
      
      // Ensure the user has permission to delete this token
      if (auth()->user()->hasRole('super_admin') || $token->tokenable_id == auth()->id()) {
        $token->delete();
        return redirect()->back()->with('success', 'Token revoked successfully.');
      }
      
      return redirect()->back()->with('error', 'You do not have permission to revoke this token.');
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
  