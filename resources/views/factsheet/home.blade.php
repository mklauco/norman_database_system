<x-app-layout>
    <x-slot name="header">
        @include('factsheet.header')
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Header Section -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">Substance Factsheets</h1>
                        <p class="text-lg text-gray-600 mb-4">
                            Comprehensive information on individual substances from all NORMAN Database System modules
                        </p>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                Last updated: {{ $databaseEntity->last_update ? $databaseEntity->last_update->format('M d, Y') : 'N/A' }}
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ number_format($totalSubstances, 0, ',', ' ') }} substances with data
                            </span>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mb-8">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="{{ route('factsheets.search.filter') }}" class="btn-submit flex-1 text-center py-3">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search Factsheets
                            </a>
                            <a href="{{ route('factsheets.index') }}" class="btn-create flex-1 text-center py-3">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Browse All Factsheets
                            </a>
                        </div>
                    </div>

                    <!-- Module Statistics -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Data Coverage by Module</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($moduleStats as $module => $stats)
                            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $stats['name'] }}</h3>
                                    <span class="text-2xl font-bold text-slate-600">{{ number_format($stats['count'], 0, ',', ' ') }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mb-4">substances with data</p>
                                <a href="{{ route($stats['route']) }}" class="link-lime-text text-sm font-medium hover:underline">
                                    View {{ $stats['name'] }} data →
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">About Substance Factsheets</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold text-slate-700 mb-2">What are Substance Factsheets?</h3>
                                <p class="text-slate-600 text-sm mb-4">
                                    Substance factsheets provide comprehensive information about individual chemicals across all NORMAN Database System modules, 
                                    giving researchers and regulators a complete picture of environmental occurrence, toxicity, and monitoring data.
                                </p>
                                <h3 class="font-semibold text-slate-700 mb-2">Data Sources</h3>
                                <ul class="text-slate-600 text-sm space-y-1">
                                    <li>• Chemical occurrence in environmental matrices</li>
                                    <li>• Ecotoxicity studies and quality standards</li>
                                    <li>• Indoor environment monitoring</li>
                                    <li>• Passive sampling data</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-700 mb-2">How to Use</h3>
                                <ol class="text-slate-600 text-sm space-y-2">
                                    <li>1. <strong>Search:</strong> Use the search function to find specific substances</li>
                                    <li>2. <strong>Browse:</strong> Explore all available factsheets</li>
                                    <li>3. <strong>Filter:</strong> Narrow down results by substance criteria</li>
                                    <li>4. <strong>Export:</strong> Download data for further analysis</li>
                                </ol>
                                <div class="mt-4 p-3 bg-lime-50 border border-lime-200 rounded-sm">
                                    <p class="text-sm text-lime-800">
                                        <strong>Tip:</strong> Use CAS numbers for the most precise substance identification
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Updates -->
                    <div class="mt-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Updates</h2>
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <p class="text-gray-600 text-sm">
                                The Substance Factsheets module is continuously updated with new data from all NORMAN Database System modules. 
                                Check back regularly for the latest information on emerging substances and updated monitoring data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
