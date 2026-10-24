<x-app-layout>
    <x-slot name="header">
        @include('factsheet.header')
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Substance Header -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h1 class="text-3xl font-bold text-gray-900">{{ $substance->name }}</h1>
                            <a href="{{ route('factsheets.search.filter') }}" class="btn-submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Search
                            </a>
                        </div>

                        <div class="flex flex-col md:flex-row gap-6 mb-6">
                            @if($substance->structure_image_url)
                            <div class="shrink-0" x-data="{ open: false }">
                                <img src="{{ $substance->structure_image_url }}"
                                     alt="Structure of {{ $substance->name }}"
                                     class="w-40 h-40 object-contain bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:border-gray-400 transition-colors"
                                     @click="open = true">
                                <!-- Modal overlay -->
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     @click="open = false"
                                     @keydown.escape.window="open = false"
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                                     style="display: none;">
                                    <img src="{{ $substance->structure_image_url }}"
                                         alt="Structure of {{ $substance->name }}"
                                         class="max-w-full max-h-[90vh] object-contain bg-white rounded-lg p-4 shadow-2xl"
                                         @click.stop>
                                </div>
                            </div>
                            @endif
                            <div class="grow grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-slate-700 mb-2">CAS Number</h3>
                                <p class="text-slate-900 font-mono">{{ $substance->cas_number ?: 'Not available' }}</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-slate-700 mb-2">StdInChIKey</h3>
                                <p class="text-slate-900 font-mono text-sm">{{ $substance->stdinchikey ?: 'Not available' }}</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-slate-700 mb-2">Total Records</h3>
                                <p class="text-slate-900 text-2xl font-bold">{{ array_sum($dataCounts) }}</p>
                            </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Coverage by Module -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Data Coverage by Module</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @php
                                $moduleInfo = [
                                    'empodat' => ['name' => 'Chemical Occurrence Data', 'route' => 'codsearch.filter', 'color' => 'bg-slate-100 text-slate-800'],
                                    'ecotox' => ['name' => 'Ecotoxicology', 'route' => 'ecotox.data.search.filter', 'color' => 'bg-slate-100 text-slate-800'],
                                    'indoor' => ['name' => 'Indoor Environment', 'route' => 'indoor.search.filter', 'color' => 'bg-slate-100 text-slate-800'],
                                    'passive' => ['name' => 'Passive Sampling', 'route' => 'passive.search.filter', 'color' => 'bg-slate-100 text-slate-800']
                                ];
                            @endphp
                            
                            @foreach($moduleInfo as $module => $info)
                                @if($dataCounts[$module] > 0)
                                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $info['name'] }}</h3>
                                        <span class="text-2xl font-bold text-slate-600">{{ number_format($dataCounts[$module], 0, ',', ' ') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4">records available</p>
                                    <a href="{{ route($info['route']) }}?substances[]={{ $substance->id }}" class="link-lime-text text-sm font-medium hover:underline">
                                        View {{ $info['name'] }} data →
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        
                        @if(array_sum($dataCounts) == 0)
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-gray-500">No data available for this substance in any module.</p>
                        </div>
                        @endif
                    </div>

                    <!-- Substance Information -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Substance Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold text-slate-700 mb-2">Chemical Properties</h3>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-slate-600">Name:</dt>
                                        <dd class="text-slate-900 font-medium">{{ $substance->name }}</dd>
                                    </div>
                                    @if($substance->cas_number)
                                    <div class="flex justify-between">
                                        <dt class="text-slate-600">CAS Number:</dt>
                                        <dd class="text-slate-900 font-mono">{{ $substance->cas_number }}</dd>
                                    </div>
                                    @endif
                                    @if($substance->stdinchikey)
                                    <div class="flex justify-between">
                                        <dt class="text-slate-600">StdInChIKey:</dt>
                                        <dd class="text-slate-900 font-mono text-xs">{{ $substance->stdinchikey }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-700 mb-2">Data Summary</h3>
                                <p class="text-slate-600 text-sm mb-4">
                                    This substance has data available across {{ count(array_filter($dataCounts)) }} different modules 
                                    in the NORMAN Database System.
                                </p>
                                <div class="text-sm text-slate-600">
                                    <p><strong>Total records:</strong> {{ number_format(array_sum($dataCounts), 0, ',', ' ') }}</p>
                                    <p><strong>Modules with data:</strong> {{ count(array_filter($dataCounts)) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('factsheets.search.filter') }}?substances[]={{ $substance->id }}" class="btn-submit flex-1 text-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search for More Data
                        </a>
                        <a href="{{ route('factsheets.home.index') }}" class="btn-create flex-1 text-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Back to Factsheets Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
