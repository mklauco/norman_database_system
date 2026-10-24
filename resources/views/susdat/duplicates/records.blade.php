<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Header Section --}}
          <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-bold text-gray-900">
                  Duplicate Resolution
                </h1>
                <div class="flex items-center space-x-2 px-3 py-1 bg-amber-100 border border-amber-300 rounded-sm">
                  <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                  </svg>
                  <span class="text-sm font-medium text-amber-800">
                    {{ ucfirst(str_replace('_', ' ', $pivot)) }}: <span class="font-semibold">{{ $pivot_value }}</span> ({{ $substancesCount }} records)
                  </span>
                </div>
              </div>
              <a href="{{ route('duplicates.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Duplicates
              </a>
            </div>
            

          </div>

          {{-- Duplicate Resolution Form --}}
          <form action="{{route('duplicates.handleDuplicates')}}" method="POST" class="mb-6">
            @csrf
            

            {{-- Active Selection Section --}}
            <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-sm">
              <h4 class="text-md font-medium text-gray-900 mb-2">Select Active Substance</h4>
              <p class="text-sm text-gray-700">
                Choose which substance should remain active and visible. This substance will be the main record that users see.
              </p>
            </div>

            {{-- Reason Section --}}
            <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-sm">
              <h4 class="text-md font-medium text-gray-900 mb-2">Reason for Deprecating</h4>
              <p class="text-sm text-gray-700 mb-2">
                Provide a reason for deprecating these duplicates. This will be logged for audit purposes.
              </p>
              <textarea name="merge_reason" 
                        rows="2" 
                        class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-1 focus:ring-blue-500"
                        placeholder="e.g., Duplicate records with same code, keeping the most complete data"
                        required></textarea>
            </div>

            {{-- Substances Table with Selection --}}
            <div class="mb-4">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2">Code</th>
                    <th class="py-1 px-2">Name</th>
                    <th class="py-1 px-2">CAS Number</th>
                    <th class="py-1 px-2">SMILES</th>
                    <th class="py-1 px-2">InChI Key</th>
                    <th class="py-1 px-2">PubChem CID</th>
                    <th class="py-1 px-2">DTX ID</th>
                    <th class="py-1 px-2">Molecular Formula</th>
                    <th class="py-1 px-2">Mass</th>
                    <th class="py-1 px-2">Active</th>
                    <th class="py-1 px-2">Deprecated</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($substances as $substance)
                    @if($substance->trashed())
                      <tr class="bg-zinc-100 text-zinc-400">
                    @else
                      <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    @endif
                      <td class="py-1 px-2">
                        <div class="font-medium">
                          <a href="{{ route('substances.edit', $substance->id) }}" 
                             class="link-lime" target="_blank">
                            {{ $substance->prefixed_code }}
                          </a>
                        </div>
                        <div class="text-xs pl-2">Internal ID: {{ $substance->id }}</div>
                      </td>
                      <td class="py-1 px-2">{{ $substance->name ?: '-' }}</td>
                      <td class="py-1 px-2">{{ $substance->cas_number ?: '-' }}</td>
                      <td class="py-1 px-2 max-w-xs truncate" title="{{ $substance->smiles }}">
                        {{ $substance->smiles ?: '-' }}
                      </td>
                      <td class="py-1 px-2 max-w-xs truncate" title="{{ $substance->stdinchikey }}">
                        {{ $substance->stdinchikey ?: '-' }}
                      </td>
                      <td class="py-1 px-2">{{ $substance->pubchem_cid ?: '-' }}</td>
                      <td class="py-1 px-2">{{ $substance->dtxid ?: '-' }}</td>
                      <td class="py-1 px-2">{{ $substance->molecular_formula ?: '-' }}</td>
                      <td class="py-1 px-2">{{ $substance->mass_iso ?: '-' }}</td>
                      <td class="py-1 px-2">
                        <input type="radio" 
                               id="active_{{ $substance->id }}" 
                               name="canonical_id" 
                               value="{{ $substance->id }}"
                               class="text-blue-600 focus:ring-blue-500"
                               {{ $loop->first ? 'checked' : '' }}
                               required>
                      </td>
                      <td class="py-1 px-2">
                        <input type="checkbox" 
                               id="deprecated_{{ $substance->id }}" 
                               name="duplicateChoice[]" 
                               value="{{ $substance->id }}"
                               class="text-blue-600 focus:ring-blue-500"
                               {{ $loop->first ? 'disabled' : '' }}>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
              <div class="text-sm text-gray-700">
                Review your selections above and click submit to apply changes.
              </div>
              <button type="submit" class="btn-submit flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Apply Changes</span>
              </button>
            </div>
          </form>

          <script>
            // Handle active/deprecated selection logic
            document.addEventListener('DOMContentLoaded', function() {
              const activeRadios = document.querySelectorAll('input[name="canonical_id"]');
              const deprecatedCheckboxes = document.querySelectorAll('input[name="duplicateChoice[]"]');
              
              function updateSelections() {
                const selectedActive = document.querySelector('input[name="canonical_id"]:checked').value;
                
                // Update deprecated checkboxes
                deprecatedCheckboxes.forEach(checkbox => {
                  if (checkbox.value === selectedActive) {
                    // Disable and uncheck the active substance's deprecated checkbox
                    checkbox.disabled = true;
                    checkbox.checked = false;
                    // Add visual indication
                    checkbox.closest('td').style.opacity = '0.5';
                  } else {
                    // Enable other checkboxes
                    checkbox.disabled = false;
                    checkbox.closest('td').style.opacity = '1';
                  }
                });
              }
              
              // Update when active selection changes
              activeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                  // Uncheck any deprecated checkbox for the newly selected active substance
                  const newActiveId = this.value;
                  const deprecatedCheckbox = document.querySelector(`input[name="duplicateChoice[]"][value="${newActiveId}"]`);
                  if (deprecatedCheckbox) {
                    deprecatedCheckbox.checked = false;
                  }
                  updateSelections();
                });
              });
              
              // Update when deprecated selection changes
              deprecatedCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                  if (this.checked) {
                    // If checking a deprecated checkbox, ensure it's not the active one
                    const selectedActive = document.querySelector('input[name="canonical_id"]:checked').value;
                    if (this.value === selectedActive) {
                      this.checked = false;
                      alert('Cannot mark the active substance as deprecated.');
                      return;
                    }
                  }
                });
              });
              
              // Initialize on page load
              updateSelections();
            });
          </script>

          {{-- External Data Sources Section --}}
          <div class="space-y-6">
            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <span>External Database Information</span>
              </h3>
              <p class="text-sm text-gray-600 mb-4">
                Additional information from external sources to help identify the correct record to keep.
              </p>
            </div>

            {{-- Comptox Database Section --}}
            <div class="bg-sky-50 border border-sky-200 rounded-lg overflow-hidden">
              <div class="bg-sky-100 px-4 py-3 border-b border-sky-200">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-sky-600 rounded-full"></div>
                  <span class="font-semibold text-sky-900">CompTox Dashboard</span>
                  <span class="text-xs text-sky-700 bg-sky-200 px-2 py-1 rounded-sm">External Source</span>
                </div>
              </div>
              <div class="p-4">
                @livewire('susdat.duplicate-load-comptox', ['dtxsid' => $dtxsIds])
              </div>
            </div>

            {{-- PubChem Database Section --}}
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg overflow-hidden">
              <div class="bg-emerald-100 px-4 py-3 border-b border-emerald-200">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-emerald-600 rounded-full"></div>
                  <span class="font-semibold text-emerald-900">PubChem Database</span>
                  <span class="text-xs text-emerald-700 bg-emerald-200 px-2 py-1 rounded-sm">External Source</span>
                </div>
              </div>
              <div class="p-4">
                @livewire('susdat.duplicate-load-pubchem', ['pubchemIds' => $pubchemIds])
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>