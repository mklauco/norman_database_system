<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::orderBy('created_at', 'desc')
            ->paginate(10); // Added pagination for better performance with large datasets
            
        return view('backend.projects.index', [
            'projects' => $projects
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $project = new Project(); // Create empty project for form
        $fillables = $project->getFillable();
        
        return view('backend.projects.upsert', [
            'project' => $project,
            'fillables' => $fillables,
            'isCreate' => true
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'abbreviation' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        $project = Project::create($validated);
        
        // Automatically assign current user to project
        if ($project) {
            $project->users()->attach(Auth::id());
        }
        
        return redirect()
            ->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with('users')->findOrFail($id);
        
        return view('backend.projects.show', [
            'project' => $project
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $project = Project::findOrFail($id);
        $fillables = $project->getFillable();
        
        return view('backend.projects.upsert', [
            'project' => $project,
            'fillables' => $fillables,
            'isCreate' => false
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'abbreviation' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        $project = Project::findOrFail($id);
        $project->update($validated);
        
        return redirect()
            ->route('projects.index')
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
public function destroy(string $id)
{
    try {
        $project = Project::findOrFail($id);
        
        // Delete project user relationships in pivot table
        $project->users()->detach();
        
        // Delete the project
        $project->delete();
        
        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    } catch (\Illuminate\Database\QueryException $e) {
        // Check if it's a foreign key constraint violation
        if ($e->getCode() == "23503") {
            return redirect()
                ->route('projects.index')
                ->with('error', 'Cannot delete this project because it is associated with one or more files. Please remove all file associations before deleting this project.');
        }
        
        // For other database errors
        return redirect()
            ->route('projects.index')
            ->with('error', 'An error occurred while trying to delete the project. Please try again later.');
    } catch (\Exception $e) {
        // For any other exceptions
        return redirect()
            ->route('projects.index')
            ->with('error', 'An error occurred while trying to delete the project. Please try again later.');
    }
}
}