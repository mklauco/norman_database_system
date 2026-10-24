<?php

namespace App\Http\Controllers;

use App\Models\Backend\Project;
use App\Models\User;
use App\Services\UserRoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(private readonly UserRoleAssignment $roleAssignment) {}

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

        // Get super_admins and admins for quick reference
        $superAdmins = User::role('super_admin')->orderBy('last_name')->get();
        $admins = User::role('admin')->orderBy('last_name')->get();

        // Get all distinct roles for filter dropdown
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('backend.dashboard.users.index', [
            'users' => $users,
            'columns' => $this->getVisibleColumns(),
            'usersWithTokens' => $usersWithTokens,
            'superAdmins' => $superAdmins,
            'admins' => $admins,
            'roles' => $roles,
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
            'role' => $role,
        ]);

        $query = User::with(['roles', 'projects'])
            ->withCount('tokens');

        // Apply search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });

            \Log::debug('Applied search filter', ['sql' => $query->toSql()]);
        }

        // Apply role filter
        if (! empty($role)) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });

            \Log::debug('Applied role filter', ['role' => $role]);
        }

        // Apply sorting
        if ($sort === 'roles') {
            // Handle special case for role sorting
            $query->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('users.*')
                ->groupBy('users.id')
                ->orderBy(\DB::raw('MIN(roles.name)'), $direction);
        } elseif ($sort === 'number_of_api_tokens') {
            // Handle special case for token count sorting
            $query->orderBy('tokens_count', $direction);
        } else {
            // Handle all other columns
            if (in_array($sort, ['id', 'first_name', 'last_name', 'email', 'created_at', 'updated_at'])) {
                $query->orderBy($sort, $direction);
            }
        }

        $results = $query->paginate($perPage);

        \Log::debug('User data results', [
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
        ]);

        return response()->json($results);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $countries = $this->getSortedCountries();
        $organisations = $this->getSortedOrganisations();

        return view('backend.dashboard.users.upsert', [
            'edit' => false,
            'roles' => $this->roleAssignment->assignableRoleNames(auth()->user()),
            'projects' => Project::all(),
            'countries' => $countries,
            'organisations' => $organisations,
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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'roles' => 'required|array',
            'roles.*' => 'string',
            'projects' => 'nullable',
        ];
        $request->validate($validation_array);

        $forbiddenRoles = $this->roleAssignment->forbiddenRequestedRoles(auth()->user(), $request['roles']);
        if ($forbiddenRoles !== []) {
            return redirect()->back()->with('error', 'You are not allowed to assign: '.implode(', ', $forbiddenRoles).'.')
                ->withInput();
        }
        $temporary_password = Str::random(12);
        $user = new User;
        $user->first_name = $request['first_name'];
        $user->last_name = $request['last_name'];
        $user->email = $request['email'];
        $user->password = bcrypt($temporary_password);
        $user->username = $request['username'];
        $user->salutation = $request['salutation'];
        $user->country_id = $request['country_id'];
        $user->organisation_id = $request['organisation_id'];
        $user->active = $request->has('active') ? true : false;
        try {
            $user->save();
            // Assign roles after user is saved
            $user->syncRoles($this->roleAssignment->rolesToSync(auth()->user(), $request['roles']));

            // other operations
            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry error code
                return redirect()->back()->with('error', 'A user with this email already exists.')
                    ->withInput();
            }

            return redirect()->back()->with('error', 'Database error: '.$e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred: '.$e->getMessage())
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
            'country',
            'tokens',
        ])->findOrFail($id);

        // Check if user has permission to view this user
        if (! auth()->user()->hasRole('super_admin') &&
        ! auth()->user()->hasRole('admin') &&
        auth()->id() != $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // You could also load recent activity if you have an activity logging system
        // For example:
        // $recentActivity = Activity::where('causer_id', $user->id)
        //                          ->latest()
        //                          ->take(5)
        //                          ->get();

        return view('backend.dashboard.users.show', [
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
        $countries = $this->getSortedCountries();
        $organisations = $this->getSortedOrganisations();

        $user = User::with('projects')->findOrFail($id);
        Gate::authorize('update', $user);

        return view('backend.dashboard.users.upsert', [
            'edit' => true,
            'user' => $user,
            'roles' => $this->roleAssignment->assignableRoleNames(auth()->user()),
            'projects' => Project::all(),
            'countries' => $countries,
            'organisations' => $organisations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $validation_array = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'roles' => 'required|array',
            'roles.*' => 'string',
            'projects' => 'nullable',
        ];
        $request->validate($validation_array);

        $user = User::findOrFail($id);
        Gate::authorize('update', $user);

        $forbiddenRoles = $this->roleAssignment->forbiddenRequestedRoles(auth()->user(), $request['roles']);
        if ($forbiddenRoles !== []) {
            return redirect()->back()->with('error', 'You are not allowed to assign: '.implode(', ', $forbiddenRoles).'.')
                ->withInput();
        }

        $user->first_name = $request['first_name'];
        $user->last_name = $request['last_name'];
        $user->email = $request['email'];
        $user->username = $request['username'];
        $user->salutation = $request['salutation'];
        $user->country_id = $request['country_id'];
        $user->organisation_id = $request['organisation_id'];
        $user->active = $request->has('active') ? true : false;
        // 'user' is always included
        $user->syncRoles($this->roleAssignment->rolesToSync(auth()->user(), $request['roles']));
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

            return redirect()->back()->with('error', 'Database error: '.$e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred: '.$e->getMessage())
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
        $user->active = ! $user->active;
        $user->save();

        $status = $user->active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User {$status} successfully.");
    }

    public function getApiTokens(string $id)
    {
        $user = User::findOrFail($id);
        $tokens = $user->tokens;

        return view('backend.dashboard.users.tokens', [
            'user' => $user,
            'tokens' => $tokens,
        ]);
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
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get countries sorted alphabetically with NR/Other at the bottom
     */
    private function getSortedCountries()
    {
        $countries = \App\Models\List\Country::all();

        return $countries->sort(function ($a, $b) {
            $aName = $a->name;
            $bName = $b->name;

            // Check if names contain 'nr' or 'other' as whole words
            $aIsSpecial = preg_match('/\b(nr|other)\b/i', $aName) || preg_match('/^(nr|other)$/i', trim($aName));
            $bIsSpecial = preg_match('/\b(nr|other)\b/i', $bName) || preg_match('/^(nr|other)$/i', trim($bName));

            // If one is special and the other isn't, special goes to bottom
            if ($aIsSpecial && ! $bIsSpecial) {
                return 1;
            }
            if (! $aIsSpecial && $bIsSpecial) {
                return -1;
            }

            // Otherwise, sort alphabetically (case-insensitive)
            return strcasecmp($aName, $bName);
        })->values();
    }

    /**
     * Get organisations sorted alphabetically with NR/Other at the bottom
     */
    private function getSortedOrganisations()
    {
        $organisations = \App\Models\List\DataSourceOrganisation::with('country')->get();

        return $organisations->sort(function ($a, $b) {
            $aName = $a->name;
            $bName = $b->name;

            // Check if names contain 'nr' or 'other' as whole words
            $aIsSpecial = preg_match('/\b(nr|other)\b/i', $aName) || preg_match('/^(nr|other)$/i', trim($aName));
            $bIsSpecial = preg_match('/\b(nr|other)\b/i', $bName) || preg_match('/^(nr|other)$/i', trim($bName));

            // If one is special and the other isn't, special goes to bottom
            if ($aIsSpecial && ! $bIsSpecial) {
                return 1;
            }
            if (! $aIsSpecial && $bIsSpecial) {
                return -1;
            }

            // Otherwise, sort alphabetically (case-insensitive)
            return strcasecmp($aName, $bName);
        })->values();
    }
}
