<x-app-layout>
    <x-slot name="header">
        @include('empodat.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Back Link -->
                    <div class="mb-6">
                        <a href="{{ route('backend.empodat.stations.index') }}" class="link-lime-text text-slate-600 hover:text-slate-900 inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Stations
                        </a>
                    </div>

                    <!-- Page Title -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ isset($station) ? 'Edit Station' : 'Create New Station' }}
                        </h3>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-sm relative">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mt-2 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ isset($station) ? route('backend.empodat.stations.update', $station) : route('backend.empodat.stations.store') }}">
                        @csrf
                        @if(isset($station))
                            @method('PUT')
                        @endif

                        <!-- Basic Information Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Basic Information</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Station Name
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           value="{{ old('name', $station->name ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('name') border-red-500 @enderror">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- National Name -->
                                <div>
                                    <label for="national_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        National Name
                                    </label>
                                    <input type="text" 
                                           name="national_name" 
                                           id="national_name" 
                                           value="{{ old('national_name', $station->national_name ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('national_name') border-red-500 @enderror">
                                    @error('national_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Country Information Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Country Information</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Primary Country -->
                                <div>
                                    <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Primary Country
                                    </label>
                                    <select name="country_id" 
                                            id="country_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('country_id') border-red-500 @enderror">
                                        <option value="">Select a country...</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" 
                                                    {{ old('country_id', $station->country_id ?? '') == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }} ({{ $country->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Other Country -->
                                <div>
                                    <label for="country_other_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Other Country
                                    </label>
                                    <select name="country_other_id" 
                                            id="country_other_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('country_other_id') border-red-500 @enderror">
                                        <option value="">Select a country...</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" 
                                                    {{ old('country_other_id', $station->country_other_id ?? '') == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }} ({{ $country->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_other_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Country Text -->
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country (Text)
                                    </label>
                                    <input type="text" 
                                           name="country" 
                                           id="country" 
                                           value="{{ old('country', $station->country ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('country') border-red-500 @enderror">
                                    @error('country')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Country Other Text -->
                                <div>
                                    <label for="country_other" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country Other (Text)
                                    </label>
                                    <input type="text" 
                                           name="country_other" 
                                           id="country_other" 
                                           value="{{ old('country_other', $station->country_other ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('country_other') border-red-500 @enderror">
                                    @error('country_other')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sample Codes Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Sample Codes</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Short Sample Code -->
                                <div>
                                    <label for="short_sample_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Short Sample Code
                                    </label>
                                    <input type="text" 
                                           name="short_sample_code" 
                                           id="short_sample_code" 
                                           value="{{ old('short_sample_code', $station->short_sample_code ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('short_sample_code') border-red-500 @enderror">
                                    @error('short_sample_code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Sample Code -->
                                <div>
                                    <label for="sample_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sample Code
                                    </label>
                                    <input type="text" 
                                           name="sample_code" 
                                           id="sample_code" 
                                           value="{{ old('sample_code', $station->sample_code ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('sample_code') border-red-500 @enderror">
                                    @error('sample_code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Provider Code -->
                                <div>
                                    <label for="provider_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Provider Code
                                    </label>
                                    <input type="text" 
                                           name="provider_code" 
                                           id="provider_code" 
                                           value="{{ old('provider_code', $station->provider_code ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('provider_code') border-red-500 @enderror">
                                    @error('provider_code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- EC Codes Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">EC Codes</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Code EC Wise -->
                                <div>
                                    <label for="code_ec_wise" class="block text-sm font-medium text-gray-700 mb-2">
                                        EC WISE Code
                                    </label>
                                    <input type="text" 
                                           name="code_ec_wise" 
                                           id="code_ec_wise" 
                                           value="{{ old('code_ec_wise', $station->code_ec_wise ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('code_ec_wise') border-red-500 @enderror">
                                    @error('code_ec_wise')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Code EC Other -->
                                <div>
                                    <label for="code_ec_other" class="block text-sm font-medium text-gray-700 mb-2">
                                        EC Other Code
                                    </label>
                                    <input type="text" 
                                           name="code_ec_other" 
                                           id="code_ec_other" 
                                           value="{{ old('code_ec_other', $station->code_ec_other ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('code_ec_other') border-red-500 @enderror">
                                    @error('code_ec_other')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Code Other -->
                                <div>
                                    <label for="code_other" class="block text-sm font-medium text-gray-700 mb-2">
                                        Other Code
                                    </label>
                                    <input type="text" 
                                           name="code_other" 
                                           id="code_other" 
                                           value="{{ old('code_other', $station->code_other ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('code_other') border-red-500 @enderror">
                                    @error('code_other')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Location Information Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Location Information</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Specific Locations -->
                                <div class="md:col-span-1">
                                    <label for="specific_locations" class="block text-sm font-medium text-gray-700 mb-2">
                                        Specific Locations
                                    </label>
                                    <input type="text" 
                                           name="specific_locations" 
                                           id="specific_locations" 
                                           value="{{ old('specific_locations', $station->specific_locations ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('specific_locations') border-red-500 @enderror">
                                    @error('specific_locations')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Latitude -->
                                <div>
                                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                                        Latitude
                                    </label>
                                    <input type="number" 
                                           step="any"
                                           name="latitude" 
                                           id="latitude" 
                                           value="{{ old('latitude', $station->latitude ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('latitude') border-red-500 @enderror"
                                           placeholder="-90 to 90">
                                    @error('latitude')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Longitude -->
                                <div>
                                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                                        Longitude
                                    </label>
                                    <input type="number" 
                                           step="any"
                                           name="longitude" 
                                           id="longitude" 
                                           value="{{ old('longitude', $station->longitude ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-xs focus:outline-hidden focus:ring-slate-500 focus:border-slate-500 @error('longitude') border-red-500 @enderror"
                                           placeholder="-180 to 180">
                                    @error('longitude')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('backend.empodat.stations.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="btn-submit px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ isset($station) ? 'Update Station' : 'Create Station' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>