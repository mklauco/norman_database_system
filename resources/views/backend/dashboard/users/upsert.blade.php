<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Page Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">{{ $edit ? 'Edit User' : 'New User' }}</h2>
            <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
              <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
          </div>
          
          <!-- Form -->
          <form action="{{ $edit ? route('users.update', $user->id) : route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method($edit ? 'PUT' : 'POST')
            
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Personal Information -->
              <div class="bg-gray-50 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Personal Information</h3>
                
                <div class="space-y-4">
                  <!-- First Name -->
                  <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" 
                           name="first_name" 
                           id="first_name" 
                           value="{{ old('first_name', $user->first_name ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <!-- Last Name -->
                  <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" 
                           name="last_name" 
                           id="last_name" 
                           value="{{ old('last_name', $user->last_name ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <!-- Email -->
                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email', $user->email ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('email') border-red-500 @enderror">
                    @error('email')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <!-- Password (show only for new users) -->
                  @if(!$edit)
                  <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('password') border-red-500 @enderror">
                    @error('password')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  </div>
                  @endif
                  
                  <!-- Salutation -->
                  <div>
                    <label for="salutation" class="block text-sm font-medium text-gray-700">Salutation</label>
                    <select name="salutation" 
                            id="salutation" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                      <option value="">None</option>
                      <option value="Dr." {{ old('salutation', $user->salutation ?? '') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                      <option value="Prof." {{ old('salutation', $user->salutation ?? '') == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                      <option value="Mr." {{ old('salutation', $user->salutation ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                      <option value="Mrs." {{ old('salutation', $user->salutation ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                      <option value="Ms." {{ old('salutation', $user->salutation ?? '') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                    </select>
                  </div>
                  
                  <!-- Username (Optional) -->
                  <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username (Optional)</label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="{{ old('username', $user->username ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('username') border-red-500 @enderror">
                    @error('username')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <!-- Active Status -->
                  <div class="flex items-center">
                    <input type="checkbox" 
                           name="active" 
                           id="active" 
                           value="1" 
                           {{ old('active', $user->active ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="active" class="ml-2 block text-sm text-gray-700">Active Account</label>
                  </div>
                </div>
              </div>
              
              <!-- Organization & Roles -->
              <div class="bg-gray-50 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Organization & Roles</h3>

                <!-- Country -->
                <div class="mb-4">
                  <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
                  <select name="country_id"
                          id="country_id"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Select a country</option>
                    @foreach ($countries as $country)
                      <option value="{{ $country->id }}"
                              {{ old('country_id', $user->country_id ?? '') == $country->id ? 'selected' : '' }}>
                        {{ $country->name }} @if($country->code) - {{ $country->code }}@endif
                      </option>
                    @endforeach
                  </select>
                  @error('country_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>

                <!-- Organization -->
                <div class="mb-4">
                  <label for="organisation_id" class="block text-sm font-medium text-gray-700">Organization</label>
                  <select name="organisation_id"
                          id="organisation_id"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Select an organization</option>
                    @foreach ($organisations as $organisation)
                      <option value="{{ $organisation->id }}"
                              {{ old('organisation_id', $user->organisation_id ?? '') == $organisation->id ? 'selected' : '' }}>
                        {{ $organisation->name }}
                        @if($organisation->city || $organisation->country)
                          ({{ collect([$organisation->city, $organisation->country?->name])->filter()->implode(', ') }})
                        @endif
                      </option>
                    @endforeach
                  </select>
                  @error('organisation_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                
                <!-- Roles -->
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">User Roles</label>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($roles as $roleName)
                    <label class="inline-flex items-center space-x-2 p-2 border rounded {{ $roleName === 'user' ? 'bg-gray-100' : (($roleName === 'server_payment_admin' || $roleName === 'server_payment_viewer') ? 'bg-yellow-100' : 'hover:bg-gray-50') }}">
                      <input type="checkbox" 
                             name="roles[]" 
                             value="{{ $roleName }}" 
                             class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-gray-300 rounded"
                             @if($roleName === 'user') 
                               checked disabled 
                             @elseif(($roleName === 'server_payment_admin' || $roleName === 'server_payment_viewer') && !auth()->user()->hasRole('super_admin'))
                               disabled
                             @elseif(isset($user) && $user->hasRole($roleName)) 
                               checked 
                             @endif>
                      <span class="text-sm {{ $roleName === 'user' ? 'text-gray-600 font-medium' : '' }}">
                        {{ $roleName }}
                        @if($roleName === 'user')
                          <span class="text-xs text-gray-500">(required)</span>
                        @elseif($roleName === 'server_payment_admin' || $roleName === 'server_payment_viewer')
                          <span class="text-xs text-gray-500">(super_admin only)</span>
                        @endif
                      </span>
                      @if($roleName === 'user')
                        <!-- Hidden input to ensure 'user' role is always submitted -->
                        <input type="hidden" name="roles[]" value="user">
                      @endif
                    </label>
                    @endforeach
                  </div>
                  @error('roles')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>
            
            <!-- Projects Section -->
            <div class="mt-6 bg-gray-50 rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Project Assignments</h3>
              
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                @foreach ($projects as $project)
                <label class="inline-flex items-center p-2 border rounded hover:bg-gray-50">
                  <input type="checkbox" 
                         name="projects[]" 
                         value="{{ $project->id }}" 
                         class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                         @if(isset($user) && $user->projects->contains($project->id)) checked @endif>
                  <span class="pl-2 text-sm">{{ $project->name }}</span>
                </label>
                @endforeach
              </div>
              @error('projects')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>
            
            <!-- Form Actions -->
            <div class="mt-6 flex justify-end space-x-3">
              <a href="{{ route('users.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
              </a>
              <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ $edit ? 'Update User' : 'Create User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>