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

                    <!-- Header with Edit Button -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            Station Details: {{ $station->name ?: 'Unnamed Station' }}
                        </h3>
                        <a href="{{ route('backend.empodat.stations.edit', $station) }}" class="btn-submit inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Edit Station
                        </a>
                    </div>

                    <!-- Station Information -->
                    <div class="space-y-8">
                        <!-- Basic Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Basic Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Station Name</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->name === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->name }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">National Name</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->national_name === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->national_name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Country Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Country Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Primary Country</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->countryRelation)
                                            {{ $station->countryRelation->name }} ({{ $station->countryRelation->code }})
                                        @else
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Country (Text)</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->country === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->country }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Other Country</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->countryOtherRelation)
                                            {{ $station->countryOtherRelation->name }} ({{ $station->countryOtherRelation->code }})
                                        @elseif($station->country_other === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->country_other }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Codes -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Sample Codes</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Short Sample Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->short_sample_code === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->short_sample_code }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Sample Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->sample_code === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->sample_code }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Provider Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->provider_code === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->provider_code }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- EC Codes -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">EC Codes</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">EC WISE Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->code_ec_wise === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->code_ec_wise }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">EC Other Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->code_ec_other === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->code_ec_other }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Other Code</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->code_other === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->code_other }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Location Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Specific Locations</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->specific_locations === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ $station->specific_locations }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Latitude</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->latitude === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ number_format($station->latitude, 6) }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Longitude</label>
                                    <p class="text-sm text-gray-900">
                                        @if($station->longitude === null)
                                            <span class="italic text-gray-400 font-mono">null</span>
                                        @else
                                            {{ number_format($station->longitude, 6) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">Metadata</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Created At</label>
                                    <p class="text-sm text-gray-900">{{ $station->created_at?->format('Y-m-d H:i:s') ?: 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Last Updated</label>
                                    <p class="text-sm text-gray-900">{{ $station->updated_at?->format('Y-m-d H:i:s') ?: 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex items-center justify-end space-x-4">
                        <a href="{{ route('backend.empodat.stations.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                            Back to List
                        </a>
                        <a href="{{ route('backend.empodat.stations.edit', $station) }}" 
                           class="btn-submit px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Edit Station
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>