<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- User Details Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">User Details</h2>
            <div class="flex space-x-2">
              @role('super_admin|admin')
              <a href="{{ route('users.edit', $user->id) }}" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                <i class="fas fa-edit mr-2"></i>Edit User
              </a>
              @endrole
              <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
              </a>
            </div>
          </div>

          <!-- User Profile Information -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Personal Information -->
            <div class="bg-gray-50 rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Personal Information</h3>
              
              <div class="space-y-4">
                <!-- User Status -->
                <div class="flex justify-between items-center mb-4">
                  <span class="font-medium">Status:</span>
                  @if($user->active)
                    <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">Active</span>
                  @else
                    <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800 font-medium">Inactive</span>
                  @endif
                </div>
                
                <!-- Name -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Full Name</span>
                  <span class="block mt-1 text-gray-900">{{ $user->formatted_name }}</span>
                </div>
                
                <!-- Email -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Email</span>
                  <span class="block mt-1 text-gray-900">{{ $user->email }}</span>
                </div>
                
                <!-- Username -->
                @if($user->username)
                <div>
                  <span class="block text-sm font-medium text-gray-500">Username</span>
                  <span class="block mt-1 text-gray-900">{{ $user->username }}</span>
                </div>
                @endif
                
                <!-- Roles -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Roles</span>
                  <div class="flex flex-wrap gap-1 mt-1">
                    @forelse($user->roles as $role)
                      <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 font-medium">
                        {{ $role->name }}
                      </span>
                    @empty
                      <span class="text-gray-500 text-sm">No roles assigned</span>
                    @endforelse
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Organization Information -->
            <div class="bg-gray-50 rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Organization Information</h3>
              
              <div class="space-y-4">
                <!-- Organization -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Organization</span>
                  <span class="block mt-1 text-gray-900">
                    @if($user->organisation_id && $user->organisation)
                      {{ $user->organisation->name }}
                    @elseif($user->organisation_other)
                      {{ $user->organisation_other }} (Other)
                    @elseif($user->organisation)
                      {{ $user->organisation }}
                    @else
                      Not specified
                    @endif
                  </span>
                </div>
                
                <!-- Country -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Country</span>
                  <span class="block mt-1 text-gray-900">
                    @if($user->country_id && $user->country)
                      {{ $user->country->name }}
                    @elseif($user->country)
                      {{ $user->country }}
                    @else
                      Not specified
                    @endif
                  </span>
                </div>
              </div>
            </div>
            
            <!-- API & System Information -->
            <div class="bg-gray-50 rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">System Information</h3>
              
              <div class="space-y-4">
                <!-- API Tokens -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">API Tokens</span>
                  <div class="mt-1">
                    <span class="text-gray-900">{{ $user->tokens->count() }} active token(s)</span>
                    @if($user->id === auth()->id())
                      <a href="{{ route('apiresources.index') }}" class="block mt-2 text-sm text-indigo-600 hover:text-indigo-500">Manage your API tokens</a>
                    @endif
                  </div>
                </div>
                
                <!-- Account Created -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Account Created</span>
                  <span class="block mt-1 text-gray-900">{{ $user->created_at->format('F j, Y, g:i a') }}</span>
                </div>
                
                <!-- Last Updated -->
                <div>
                  <span class="block text-sm font-medium text-gray-500">Last Updated</span>
                  <span class="block mt-1 text-gray-900">{{ $user->updated_at->format('F j, Y, g:i a') }}</span>
                </div>
                
                <!-- Email Verified -->
                @if($user->email_verified_at)
                <div>
                  <span class="block text-sm font-medium text-gray-500">Email Verified</span>
                  <span class="block mt-1 text-gray-900">{{ $user->email_verified_at->format('F j, Y, g:i a') }}</span>
                </div>
                @endif
              </div>
            </div>
          </div>
          
          <!-- Projects Section -->
          <div class="mt-8 bg-gray-50 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
              Assigned Projects
            </h3>
            
            @if($user->projects->count() > 0)
              <div class="overflow-x-auto">
                <table class="table-standard w-full">
                  <thead>
                    <tr class="bg-gray-600 text-white">
                      <th class="py-2 px-4 text-left">Project Name</th>
                      <th class="py-2 px-4 text-left">Abbreviation</th>
                      <th class="py-2 px-4 text-left">Description</th>
                      <th class="py-2 px-4 text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($user->projects as $project)
                      <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition">
                        <td class="py-2 px-4">{{ $project->name }}</td>
                        <td class="py-2 px-4">{{ $project->abbreviation }}</td>
                        <td class="py-2 px-4">{{ Str::limit($project->description, 100) }}</td>
                        <td class="py-2 px-4 text-center">
                          <a href="{{ route('projects.show', $project->id) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="bg-white border border-gray-200 rounded-md p-4 text-center text-gray-500">
                This user is not assigned to any projects.
              </div>
            @endif
          </div>
          
          <!-- Recent Activity Section (Optional) -->
          @if(isset($recentActivity) && count($recentActivity) > 0)
          <div class="mt-8 bg-gray-50 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
              Recent Activity
            </h3>
            
            <div class="space-y-4">
              @foreach($recentActivity as $activity)
                <div class="flex items-start border-b border-gray-100 pb-4">
                  <div class="bg-gray-200 p-2 rounded-full mr-3">
                    <i class="fas fa-history text-gray-600"></i>
                  </div>
                  <div>
                    <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>