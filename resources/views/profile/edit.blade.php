<x-app-layout>
  <x-slot name="header">
    <div class="px-4 sm:px-6 lg:px-8">
      <span class="mr-12 font-bold text-lime-700">
        Profile Management
      </span>
      
      <x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        Dashboard
      </x-nav-link-header>
      
      <x-nav-link-header :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
        Profile Settings
      </x-nav-link-header>
    </div>
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Page Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Profile Settings</h2>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-600 text-white rounded-sm hover:bg-gray-700 transition">
              <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
          </div>
          
          <!-- Success Message -->
          @if(session('status') === 'profile-updated')
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-sm relative" role="alert">
              <span class="block sm:inline">Profile updated successfully!</span>
            </div>
          @endif

          @if(session('status') === 'password-updated')
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-sm relative" role="alert">
              <span class="block sm:inline">Password updated successfully!</span>
            </div>
          @endif
          
          <div class="grid md:grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Profile Information Section -->
            <div class="bg-gray-50 rounded-lg shadow-sm p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Profile Information</h3>
              
              <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')
                
                <!-- Personal Information -->
                <div class="grid md:grid-cols-2 gap-4">
                  <!-- Salutation -->
                  <div>
                    <label for="salutation" class="block text-sm font-medium text-gray-700">Salutation</label>
                    <select name="salutation" 
                            id="salutation" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                      <option value="">None</option>
                      <option value="Dr." {{ old('salutation', $user->salutation ?? '') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                      <option value="Prof." {{ old('salutation', $user->salutation ?? '') == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                      <option value="Mr." {{ old('salutation', $user->salutation ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                      <option value="Mrs." {{ old('salutation', $user->salutation ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                      <option value="Ms." {{ old('salutation', $user->salutation ?? '') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                    </select>
                    @error('salutation')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                  
                  <!-- Username -->
                  <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username (Optional)</label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="{{ old('username', $user->username ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('username') border-red-500 @enderror">
                    @error('username')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
                
                <!-- First and Last Name -->
                <div class="grid md:grid-cols-2 gap-4">
                  <!-- First Name -->
                  <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" 
                           name="first_name" 
                           id="first_name" 
                           value="{{ old('first_name', $user->first_name ?? '') }}" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('first_name') border-red-500 @enderror">
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
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
                
                <!-- Email -->
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                  <input type="email" 
                         name="email" 
                         id="email" 
                         value="{{ old('email', $user->email ?? '') }}" 
                         required
                         class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('email') border-red-500 @enderror">
                  @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                  
                  @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2">
                      <p class="text-sm text-gray-800">
                        Your email address is unverified.
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                          Click here to re-send the verification email.
                        </button>
                      </p>
                      
                      @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                          A new verification link has been sent to your email address.
                        </p>
                      @endif
                    </div>
                  @endif
                </div>
                
                <!-- Organization and Country -->
                <div class="grid md:grid-cols-2 gap-4">
                  <!-- Organization -->
                  <div>
                    <label for="organisation_id" class="block text-sm font-medium text-gray-700">Organization</label>
                    <select name="organisation_id"
                            id="organisation_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('organisation_id') border-red-500 @enderror">
                      <option value="">-- Select Organization --</option>
                      @foreach($organisations as $organisation)
                        <option value="{{ $organisation->id }}"
                                {{ old('organisation_id', $user->organisation_id) == $organisation->id ? 'selected' : '' }}>
                          {{ $organisation->name }}@if($organisation->acronym) ({{ $organisation->acronym }})@endif
                        </option>
                      @endforeach
                    </select>
                    @error('organisation_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Country -->
                  <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
                    <select name="country_id"
                            id="country_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('country_id') border-red-500 @enderror">
                      <option value="">-- Select Country --</option>
                      @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                                {{ old('country_id', $user->country_id) == $country->id ? 'selected' : '' }}>
                          {{ $country->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('country_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex items-center gap-4 pt-4">
                  <button type="submit" class="btn-submit">
                    Update Profile
                  </button>
                </div>
              </form>
              
              <!-- Hidden form for email verification -->
              <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                @csrf
              </form>
            </div>
            
            <!-- Password Section -->
            <div class="bg-gray-50 rounded-lg shadow-sm p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Change Password</h3>
              
              <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')
                
                <!-- Current Password -->
                <div>
                  <label for="update_password_current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                  <input type="password" 
                         name="current_password" 
                         id="update_password_current_password" 
                         autocomplete="current-password"
                         class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('current_password', 'updatePassword') border-red-500 @enderror">
                  @error('current_password', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                
                <!-- New Password -->
                <div>
                  <label for="update_password_password" class="block text-sm font-medium text-gray-700">New Password</label>
                  <input type="password" 
                         name="password" 
                         id="update_password_password" 
                         autocomplete="new-password"
                         class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('password', 'updatePassword') border-red-500 @enderror">
                  @error('password', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                
                <!-- Confirm Password -->
                <div>
                  <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                  <input type="password" 
                         name="password_confirmation" 
                         id="update_password_password_confirmation" 
                         autocomplete="new-password"
                         class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('password_confirmation', 'updatePassword') border-red-500 @enderror">
                  @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                
                <!-- Form Actions -->
                <div class="flex items-center gap-4 pt-4">
                  <button type="submit" class="btn-submit">
                    Update Password
                  </button>
                </div>
              </form>
            </div>
            
          </div>

          <!-- Account Deletion Section -->
          <div class="mt-6 bg-red-50 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-red-900 mb-4 border-b border-red-200 pb-2">Danger Zone</h3>
            
            <div class="space-y-4">
              <p class="text-sm text-red-700">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
              </p>
              
              <button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="px-4 py-2 bg-red-600 text-white rounded-sm hover:bg-red-700 transition"
              >
                Delete Account
              </button>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Account Modal -->
  <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
      @csrf
      @method('delete')

      <h2 class="text-lg font-medium text-gray-900">
        Are you sure you want to delete your account?
      </h2>

      <p class="mt-1 text-sm text-gray-600">
        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
      </p>

      <div class="mt-6">
        <label for="password" class="sr-only">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          class="mt-1 block w-3/4 rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500"
          placeholder="Password"
        />
        @error('password', 'userDeletion')
          <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
      </div>

      <div class="mt-6 flex justify-end">
        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 bg-gray-600 text-white rounded-sm hover:bg-gray-700 transition mr-3">
          Cancel
        </button>

        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-sm hover:bg-red-700 transition">
          Delete Account
        </button>
      </div>
    </form>
  </x-modal>
</x-app-layout>