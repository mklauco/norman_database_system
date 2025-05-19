<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Project Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">{{ $project->name }}</h2>
            <div class="flex space-x-2">
              <a href="{{ route('projects.edit', $project->id) }}" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                Edit Project
              </a>
              <a href="{{ route('projects.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                Back to Projects
              </a>
            </div>
          </div>
          
          <!-- Project Details -->
          <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-500">Abbreviation</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $project->abbreviation }}</p>
              </div>
              
              <div>
                <h3 class="text-sm font-medium text-gray-500">Created</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $project->created_at->format('F j, Y') }}</p>
              </div>
            </div>
            
            <div class="mt-4">
              <h3 class="text-sm font-medium text-gray-500">Description</h3>
              <div class="mt-1 text-gray-900 prose max-w-none">
                {{ $project->description ?? 'No description provided.' }}
              </div>
            </div>
          </div>
          
          <!-- Project Team Members -->
          <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Team Members</h3>
            
            @if($project->users->count() > 0)
              <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                  @foreach($project->users as $user)
                    <li class="px-4 py-4 sm:px-6">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center">
                            <span class="text-lg font-medium text-gray-600">
                              {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </span>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                              {{ $user->first_name }} {{ $user->last_name }}
                            </div>
                            <div class="text-sm text-gray-500">
                              {{ $user->email }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </li>
                  @endforeach
                </ul>
              </div>
            @else
              <div class="bg-gray-100 p-4 rounded text-center">
                <p>No team members assigned to this project yet.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>