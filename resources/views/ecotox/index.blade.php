<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}
          
          <a href="{{ route('ecotox.search.filter', [
            'substances' => $request->input('substances'),
            'query_log_id' => $query_log_id
          ]) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>
          
          <div class="text-gray-600 flex border-l-2 border-white">
            @if(isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              @livewire('backend.query-counter', [
                'queryId' => $query_log_id ?? null, 
                'resultsCount' => $resultsObjectsCount, 
                'count_again' => request()->has('page') ? false : true
              ])
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center mr-4">
                  <span class="text-gray-700">Number of matched records:</span>
                  <span class="font-bold text-lg ml-2 text-indigo-700">
                    {{ number_format($resultsObjects->total(), 0, ".", " ") }}
                  </span>
                </div>
                
                <div class="flex items-center">
                  <span class="text-gray-700">of</span>
                  <span class="font-medium ml-2 text-gray-800">
                    {{ number_format($resultsObjectsCount, 0, ".", " ") }}
                  </span>
                  
                  @if(is_numeric($resultsObjects->total()) && $resultsObjectsCount > 0)
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                      @if($resultsObjects->total()/$resultsObjectsCount*100 < 0.01)
                        &le; 0.01% of total
                      @else
                        {{ number_format($resultsObjects->total()/$resultsObjectsCount*100, 2, ".", " ") }}% of total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif
            
            @auth
              {{-- <div class="py-2 px-2"><a href="{{ route('ecotox.search.download', ['query_log_id' => $query_log_id]) }}" class="btn-download">Download</a></div> --}}
            @else
              <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
            @endauth
          </div>
          
          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                {{-- if value is array|collection then use for each, othervise display value --}}
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  {{-- If $value is an array or collection, loop over each element --}}
                  @foreach ($value as $item)
                    {{ $item }}@if(!$loop->last), @endif
                  @endforeach
                @else
                  {{-- Otherwise, just display the single value --}}
                  {{ $value }}
                @endif @if(!$loop->last); @endif
              @endforeach
            </span>
          </div>
          
          {{-- Tabs for different matrix-habitat/acute-chronic combinations --}}
          <div class="mt-4">
            <div class="border-b border-gray-200">
              <nav class="-mb-px flex space-x-8">
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600" data-tab="all">
                  All Results <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">{{ $resultsObjects->total() }}</span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="freshwater-acute">
                  Freshwater - Acute <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">{{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'acute')->count() }}</span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="freshwater-chronic">
                  Freshwater - Chronic <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">{{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'chronic')->count() }}</span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="marine-acute">
                  Marine Water - Acute <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">{{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'acute')->count() }}</span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="marine-chronic">
                  Marine Water - Chronic <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">{{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'chronic')->count() }}</span>
                </button>
              </nav>
            </div>
          </div>
          
          {{-- Tab Content --}}
          <div>
            {{-- All Results Tab Content --}}
            <div id="all" class="tab-content">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>ID</th>
                    <th>Substance</th>
                    <th>Matrix</th>
                    <th>Type</th>
                    <th>Taxonomic Group</th>
                    <th>Scientific Name</th>
                    <th>Endpoint</th>
                    <th>Concentration</th>
                    <th>Duration</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($resultsObjects as $e)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-1 text-center">{{ $e->ecotox_id }}</td>
                      <td class="p-1">
                        @if($e->substance)
                          <div>{{ $e->substance->name }}</div>
                          <div class="text-xs text-gray-500">{{ $e->cas_number }}</div>
                        @else
                          <span class="text-gray-400">{{ $e->substance_name ?? 'N/A' }}</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        @if($e->matrix_habitat == 'freshwater')
                          <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Freshwater</span>
                        @elseif($e->matrix_habitat == 'marine water')
                          <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">Marine</span>
                        @else
                          {{ $e->matrix_habitat }}
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        @if($e->acute_or_chronic == 'acute')
                          <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs">Acute</span>
                        @elseif($e->acute_or_chronic == 'chronic')
                          <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Chronic</span>
                        @else
                          {{ $e->acute_or_chronic }}
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->taxonomic_group }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $e->scientific_name }}</div>
                        @if($e->common_name)
                          <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        <div>{{ $e->endpoint }}</div>
                        <div class="text-xs text-gray-500">{{ $e->effect_measurement }}</div>
                      </td>
                      <td class="p-1 text-center">
                        @if($e->concentration_value)
                          <div>{{ $e->concentration_qualifier }} {{ number_format($e->concentration_value, 4) }}</div>
                          @if($e->unit_concentration)
                            <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->duration }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            {{-- Freshwater Acute Tab Content --}}
            <div id="freshwater-acute" class="tab-content hidden">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>ID</th>
                    <th>Substance</th>
                    <th>Taxonomic Group</th>
                    <th>Scientific Name</th>
                    <th>Endpoint</th>
                    <th>Concentration</th>
                    <th>Duration</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($resultsObjects->filter(function($item) { return $item->matrix_habitat === 'freshwater' && $item->acute_or_chronic === 'acute'; }) as $e)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-1 text-center">{{ $e->ecotox_id }}</td>
                      <td class="p-1">
                        @if($e->substance)
                          <div>{{ $e->substance->name }}</div>
                          <div class="text-xs text-gray-500">{{ $e->cas_number }}</div>
                        @else
                          <span class="text-gray-400">{{ $e->substance_name ?? 'N/A' }}</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->taxonomic_group }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $e->scientific_name }}</div>
                        @if($e->common_name)
                          <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        <div>{{ $e->endpoint }}</div>
                        <div class="text-xs text-gray-500">{{ $e->effect_measurement }}</div>
                      </td>
                      <td class="p-1 text-center">
                        @if($e->concentration_value)
                          <div>{{ $e->concentration_qualifier }} {{ number_format($e->concentration_value, 4) }}</div>
                          @if($e->unit_concentration)
                            <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->duration }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            {{-- Freshwater Chronic Tab Content --}}
            <div id="freshwater-chronic" class="tab-content hidden">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>ID</th>
                    <th>Substance</th>
                    <th>Taxonomic Group</th>
                    <th>Scientific Name</th>
                    <th>Endpoint</th>
                    <th>Concentration</th>
                    <th>Duration</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($resultsObjects->filter(function($item) { return $item->matrix_habitat === 'freshwater' && $item->acute_or_chronic === 'chronic'; }) as $e)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-1 text-center">{{ $e->ecotox_id }}</td>
                      <td class="p-1">
                        @if($e->substance)
                          <div>{{ $e->substance->name }}</div>
                          <div class="text-xs text-gray-500">{{ $e->cas_number }}</div>
                        @else
                          <span class="text-gray-400">{{ $e->substance_name ?? 'N/A' }}</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->taxonomic_group }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $e->scientific_name }}</div>
                        @if($e->common_name)
                          <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        <div>{{ $e->endpoint }}</div>
                        <div class="text-xs text-gray-500">{{ $e->effect_measurement }}</div>
                      </td>
                      <td class="p-1 text-center">
                        @if($e->concentration_value)
                          <div>{{ $e->concentration_qualifier }} {{ number_format($e->concentration_value, 4) }}</div>
                          @if($e->unit_concentration)
                            <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->duration }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            {{-- Marine Acute Tab Content --}}
            <div id="marine-acute" class="tab-content hidden">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>ID</th>
                    <th>Substance</th>
                    <th>Taxonomic Group</th>
                    <th>Scientific Name</th>
                    <th>Endpoint</th>
                    <th>Concentration</th>
                    <th>Duration</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($resultsObjects->filter(function($item) { return $item->matrix_habitat === 'marine water' && $item->acute_or_chronic === 'acute'; }) as $e)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-1 text-center">{{ $e->ecotox_id }}</td>
                      <td class="p-1">
                        @if($e->substance)
                          <div>{{ $e->substance->name }}</div>
                          <div class="text-xs text-gray-500">{{ $e->cas_number }}</div>
                        @else
                          <span class="text-gray-400">{{ $e->substance_name ?? 'N/A' }}</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->taxonomic_group }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $e->scientific_name }}</div>
                        @if($e->common_name)
                          <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        <div>{{ $e->endpoint }}</div>
                        <div class="text-xs text-gray-500">{{ $e->effect_measurement }}</div>
                      </td>
                      <td class="p-1 text-center">
                        @if($e->concentration_value)
                          <div>{{ $e->concentration_qualifier }} {{ number_format($e->concentration_value, 4) }}</div>
                          @if($e->unit_concentration)
                            <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->duration }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            {{-- Marine Chronic Tab Content --}}
            <div id="marine-chronic" class="tab-content hidden">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>ID</th>
                    <th>Substance</th>
                    <th>Taxonomic Group</th>
                    <th>Scientific Name</th>
                    <th>Endpoint</th>
                    <th>Concentration</th>
                    <th>Duration</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($resultsObjects->filter(function($item) { return $item->matrix_habitat === 'marine water' && $item->acute_or_chronic === 'chronic'; }) as $e)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-1 text-center">{{ $e->ecotox_id }}</td>
                      <td class="p-1">
                        @if($e->substance)
                          <div>{{ $e->substance->name }}</div>
                          <div class="text-xs text-gray-500">{{ $e->cas_number }}</div>
                        @else
                          <span class="text-gray-400">{{ $e->substance_name ?? 'N/A' }}</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->taxonomic_group }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $e->scientific_name }}</div>
                        @if($e->common_name)
                          <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                        @endif
                      </td>
                      <td class="p-1 text-center">
                        <div>{{ $e->endpoint }}</div>
                        <div class="text-xs text-gray-500">{{ $e->effect_measurement }}</div>
                      </td>
                      <td class="p-1 text-center">
                        @if($e->concentration_value)
                          <div>{{ $e->concentration_qualifier }} {{ number_format($e->concentration_value, 4) }}</div>
                          @if($e->unit_concentration)
                            <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $e->duration }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          
          @if(isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($resultsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $resultsObjects->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif
              
              @if ($resultsObjects->hasMorePages())
                <a href="{{ $resultsObjects->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Next
                </a>
              @else
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Next
                </span>
              @endif
            </div>
          @else
            {{-- use advanced output --}}
            {{$resultsObjects->links('pagination::tailwind')}}
          @endif
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Tab functionality
      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');
      
      // Function to activate tab
      function activateTab(tabId) {
        // Hide all tab contents
        tabContents.forEach(content => {
          content.classList.add('hidden');
        });
        
        // Remove active class from all buttons
        tabButtons.forEach(button => {
          button.classList.remove('border-indigo-500', 'text-indigo-600');
          button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById(tabId).classList.remove('hidden');
        
        // Add active class to clicked button
        document.querySelector(`[data-tab="${tabId}"]`).classList.remove('border-transparent', 'text-gray-500');
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('border-indigo-500', 'text-indigo-600');
        
        // Save active tab to localStorage
        localStorage.setItem('activeEcotoxTab', tabId);
      }
      
      // Add click event to tab buttons
      tabButtons.forEach(button => {
        button.addEventListener('click', function() {
          activateTab(this.dataset.tab);
        });
      });
      
      // Check for saved tab in localStorage
      const savedTab = localStorage.getItem('activeEcotoxTab');
      if (savedTab && document.getElementById(savedTab)) {
        activateTab(savedTab);
      }
    });
  </script>
  @endpush
</x-app-layout>