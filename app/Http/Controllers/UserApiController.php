<?php
// app/Http/Controllers/UserApiController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');
        $role = $request->input('role', '');
        
        $query = User::with(['roles', 'projects'])
            ->withCount('tokens');
        
        // Apply search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('roles', function ($roleQuery) use ($search) {
                      $roleQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply role filter
        if (!empty($role)) {
            $query->whereHas('roles', function ($roleQuery) use ($role) {
                $roleQuery->where('name', $role);
            });
        }
        
        // Apply sorting
        if ($sort === 'roles') {
            // Custom sorting for role names via a subquery
            $query->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                  ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                  ->select('users.*')
                  ->orderBy('roles.name', $direction)
                  ->groupBy('users.id');
        } else {
            $query->orderBy($sort, $direction);
        }
        
        return $query->paginate($perPage);
    }
}