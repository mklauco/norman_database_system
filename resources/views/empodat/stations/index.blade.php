<x-app-layout>
    <x-slot name="header">
        @include('empodat.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Success Message -->
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-sm relative">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Header with Create Button -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">EMPODAT Stations</h3>
                        <a href="{{ route('backend.empodat.stations.create') }}" class="btn-submit inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Create New Station
                        </a>
                    </div>

                    <!-- Stations Table -->
                    @if($stations->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country Other</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">National Name</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Short Sample Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EC WISE Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EC Other Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other Code</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specific Locations</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latitude</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Longitude</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($stations as $station)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-gray-900">
                                                {{ $station->id }}
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                                @if($station->name === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->name }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->countryRelation)
                                                    {{ $station->countryRelation->name }}
                                                @elseif($station->country === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->country }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->countryOtherRelation)
                                                    {{ $station->countryOtherRelation->name }}
                                                @elseif($station->country_other === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->country_other }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->national_name === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->national_name }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->short_sample_code === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->short_sample_code }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->sample_code === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->sample_code }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->provider_code === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->provider_code }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->code_ec_wise === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->code_ec_wise }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->code_ec_other === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->code_ec_other }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->code_other === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->code_other }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->specific_locations === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ $station->specific_locations }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->latitude === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ number_format($station->latitude, 6) }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500">
                                                @if($station->longitude === null)
                                                    <span class="italic text-gray-400 font-mono">null</span>
                                                @else
                                                    {{ number_format($station->longitude, 6) }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-xs font-medium">
                                                <div class="flex space-x-1">
                                                    <a href="{{ route('backend.empodat.stations.show', $station) }}" class="link-lime-text">
                                                        View
                                                    </a>
                                                    <a href="{{ route('backend.empodat.stations.edit', $station) }}" class="link-lime-text">
                                                        Edit
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($stations->hasPages())
                            {{ $stations->links('pagination::tailwind') }}
                        @endif
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">No stations found.</p>
                            <a href="{{ route('backend.empodat.stations.create') }}" class="mt-4 btn-submit inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Create First Station
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
