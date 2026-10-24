<x-app-layout>
  <div class="container mx-auto px-4 py-8" 
       x-data="{
         showChangesModal: false,
         selectedPnecId: null,
         selectedColumnName: '',
         changesData: null,
         
         openChangesModal(pnecId, columnName) {
           this.selectedPnecId = pnecId;
           this.selectedColumnName = columnName;
           this.showChangesModal = true;
           this.changesData = null;
           this.loadChangesData(pnecId, columnName);
         },
         
         closeChangesModal() {
           this.showChangesModal = false;
           this.selectedPnecId = null;
           this.selectedColumnName = '';
           this.changesData = null;
         },
         
         async loadChangesData(pnecId, columnName) {
           try {
             const url = `/ecotox/quality/changes/${pnecId}/${encodeURIComponent(columnName)}`;
             const response = await fetch(url);
             if (!response.ok) {
               throw new Error('Failed to fetch changes data');
             }
             this.changesData = await response.json();
           } catch (error) {
             console.error('Error loading changes data:', error);
             this.changesData = [];
           }
         }
       }">
    <!-- Breadcrumb Navigation -->
    <nav class="mb-6">
      <ol class="flex items-center space-x-2 text-sm text-gray-500">
        <li>
          @if ($returnUrl)
            <a href="{{ $returnUrl }}" class="link-lime-text hover:text-lime-700">
              ← Back to Search Results
            </a>
          @else
            <a href="{{ route('ecotox.quality.search.filter') }}" class="link-lime-text hover:text-lime-700">
              ECOTOX Quality
            </a>
          @endif
        </li>
        <li>
          <span class="mx-2">/</span>
        </li>
        <li class="text-gray-800 font-medium">
          @if ($recordId)
            PNEC Record Details for {{ $recordId }}
          @else
            PNEC Record Details
          @endif
        </li>
      </ol>
    </nav>

    <div>
      <!-- Primary information -->
      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
          @if (!empty(request()->get('returnUrl')))
            <div class="mb-4">
              <a href="{{ request()->get('returnUrl') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                ← Go Back to Search Results
              </a>
            </div>
          @endif
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              @if ($recordId)
                PNEC Record Details
              @else
                PNEC Record
              @endif
            </h1>
            @if ($recordId)
              <p class="text-gray-700">Viewing PNEC ID: {{ $recordId }}</p>
            @else
              <p class="text-gray-700">This is the detailed view of a PNEC record.</p>
            @endif
          </div>
        </div>
        <div class="col-span-2">
          @if ($record)
            <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Record Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">PNEC ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['norman_pnec_id'] }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['substance']->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">CAS Number</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['substance']->cas_number ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance Code</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['substance']->prefixed_code ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Matrix Habitat</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['matrix_habitat'] ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">PNEC Type</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record['pnec_type'] ?? 'N/A' }}</p>
                </div>
              </div>
            </div>
          @endif

        </div>
      </div>

      <!-- Table Content -->
      @if ($record && !empty($tableRows))
        <div class="bg-white border border-gray-200 rounded-lg shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 text-sm">
              <thead>
                <tr class="bg-gray-100">
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Parameter Name
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Original
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Finalised
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Edited
                  </th>
                </tr>
              </thead>
              <tbody>
                <!-- Substance Information (Static) -->
                <tr class="bg-gray-50">
                  <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">
                    Substance Information
                  </td>
                </tr>
                <tr>
                  <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Substance Name</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->name ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->name ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">-</td>
                </tr>
                <tr class="bg-gray-50">
                  <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">CAS Number</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->cas_number ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->cas_number ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">-</td>
                </tr>
                <tr>
                  <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Substance Code</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->prefixed_code ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">{{ $record['substance']->prefixed_code ?? 'N/A' }}</td>
                  <td class="border border-gray-300 px-3 py-2">-</td>
                </tr>
                
                <!-- Dynamic Sections -->
                @foreach ($tableRows as $row)
                  @if ($row['type'] === 'header')
                    <tr class="bg-gray-50">
                      <td colspan="4" 
                          class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">
                        {{ $row['title'] }}
                      </td>
                    </tr>
                  @elseif ($row['type'] === 'data')
                    <tr class="{{ $row['hasChanges'] ? 'bg-rose-300' : ($row['isOdd'] ? 'bg-gray-50' : '') }}">
                      <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">
                        @if($isSuperAdmin)
                          <div>
                            <div class="font-semibold">{{ $row['key'] }}</div>
                            <div class="text-xs text-gray-500">
                              ID: {{ $row['columnId'] ?? 'N/A' }}
                            </div>
                          </div>
                        @else
                          <span>{{ $row['key'] }}</span>
                        @endif
                      </td>
                      <td class="border border-gray-300 px-3 py-2">{{ $row['pnec2'] ?? 'N/A' }}</td>
                      <td class="border border-gray-300 px-3 py-2">{{ $row['pnec3'] ?? 'N/A' }}</td>
                      <td class="border border-gray-300 px-3 py-2">
                        @if ($row['isEditable'])
                          <div>
                            @if ($row['inputType'] === 'text')
                              <input type="text" 
                                     value="{{ $row['edited'] ?? '' }}"
                                     class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-slate-500"
                                     placeholder="Enter text">
                            @elseif ($row['inputType'] === 'numeric')
                              <input type="number" 
                                     value="{{ $row['edited'] ?? '' }}"
                                     class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-slate-500"
                                     placeholder="Enter number">
                            @elseif ($row['inputType'] === 'dropdown')
                              <select class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-slate-500">
                                <option value="">Select option</option>
                                @foreach($row['dropdownOptions'] as $option)
                                  <option value="{{ $option }}" {{ $row['edited'] == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                  </option>
                                @endforeach
                              </select>
                            @endif
                            @if($row['hasChanges'])
                              <button @click="openChangesModal('{{ $recordId }}', '{{ $row['columnName'] }}')"
                                      class="mt-1 text-xs text-rose-600 hover:text-rose-800 underline">
                                View Changes
                              </button>
                            @endif
                          </div>
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                    </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @else
        <div class="text-center py-8 text-gray-500">
          No data available for this record.
        </div>
      @endif
    </div>
    
    <!-- Go Back Button -->
    @if ($returnUrl)
      <div class="mt-6 flex justify-center">
        <a href="{{ $returnUrl }}" 
           class="inline-flex items-center px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
          ← Go Back to Search Results
        </a>
      </div>
    @endif

    <!-- Changes Modal -->
    <div x-show="showChangesModal" 
         x-cloak 
         @keydown.escape.window="closeChangesModal()"
         class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

      <div class="bg-white w-11/12 md:w-3/4 lg:w-3/4 xl:w-2/3 rounded-sm shadow-lg relative">

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-2 bg-lime-600 text-white">
          <div class="flex items-center space-x-4">
            <h3 class="text-lg font-semibold">
              Changes History
            </h3>
          </div>
          <button @click="closeChangesModal()" class="text-white hover:text-gray-200 text-xl">
            &times;
          </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[70vh] overflow-y-auto">
          <!-- Loading State -->
          <div x-show="!changesData && showChangesModal" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600 mx-auto"></div>
            <p class="mt-2 text-gray-600">Loading changes data...</p>
          </div>

          <!-- Changes Content -->
          <div x-show="changesData" x-transition>
            <div class="text-sm py-2 px-2">
              For column: <span x-text="selectedColumnName" class="font-mono"></span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full border border-gray-300 text-sm">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                      Date
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                      User
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                      Old Value
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                      New Value
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                      Change Type
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="change in changesData" :key="change.id">
                    <tr class="border-b border-gray-200">
                      <td class="border border-gray-300 px-3 py-2" x-text="change.change_date"></td>
                      <td class="border border-gray-300 px-3 py-2" x-text="change.user_name || 'Unknown'"></td>
                      <td class="border border-gray-300 px-3 py-2" x-text="change.change_old || 'N/A'"></td>
                      <td class="border border-gray-300 px-3 py-2" x-text="change.change_new || 'N/A'"></td>
                      <td class="border border-gray-300 px-3 py-2" x-text="change.change_type || 'N/A'"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between border-t px-4 py-2">
          <button @click="closeChangesModal()" 
                  class="px-4 py-2 bg-red-500 text-white rounded-sm hover:bg-red-600">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
