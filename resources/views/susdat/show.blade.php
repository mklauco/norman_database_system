<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

      {{-- @include('_t.errors') --}}

      <div class="bg-white shadow-lg sm:rounded-lg">

        <div class="p-6 text-gray-900">

          {{-- Substance Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Substance Information at Glance</h2>
            <div class="flex flex-col lg:flex-row gap-6">
              @if($substance->structure_image_url)
              <div class="flex-shrink-0" x-data="{ open: false }">
                <img src="{{ $substance->structure_image_url }}"
                     alt="Structure of {{ $substance->name }}"
                     class="w-48 h-48 object-contain bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:border-gray-400 transition-colors"
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
              <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">NORMAN SusDat ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->prefixed_code ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">CAS Number</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->cas_number ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">StdInChIKey</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->stdinchikey ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">DTXSID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->dtxid ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">PubChem CID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->pubchem_cid ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Molecular Formula</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->molecular_formula ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">SMILES</h3>
                <p class="text-sm text-teal-800 font-mono break-all">{{ $substance->smiles ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Monoisotopic Mass</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->mass_iso ? number_format((float)$substance->mass_iso, 4, '.', ' ') : 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Average Mass</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->average_mass ? number_format((float)$substance->average_mass, 4, '.', ' ') : 'N/A' }}</p>
              </div>
              </div>
            </div>
          </div>

          <div class="w-full">
            <div class="flex justify-between items-center">
              <span class="text-xl font-bold">Complete Substance Details</span>
              @auth
                @role('super_admin|admin|susdat')
                  <a class="link-edit" href="{{ route('substances.edit', $substance->id) }}">
                    Edit
                  </a>
                @endrole
              @endauth
            </div>

            <table class="table-fixed w-full border-separate border-spacing-1 text-xs">
              @foreach ($substance->toArray() as $key => $value)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold w-40 align-top">{{ config('substance_field_labels.'.$key, $key) }}</td>
                  @if (substr($key, 0, 8) == 'metadata')
                    @php
                      // Handle both string and array values for metadata
                      if (is_string($value)) {
                          $decodedJson = json_decode($value, true);
                      } else {
                          $decodedJson = $value;
                      }

                      // Ensure we have an array to work with
                      if (is_array($decodedJson)) {
                          $prettyJson = json_encode($decodedJson, JSON_PRETTY_PRINT);
                          $escapedJson = htmlspecialchars($prettyJson, ENT_QUOTES, 'UTF-8');
                      } else {
                          $decodedJson = [];
                      }
                    @endphp
                    <td class="p-1 break-words">
                      @if (is_array($decodedJson) && !empty($decodedJson))
                        @foreach ($decodedJson as $keyInner => $valueInner)
                          <span class="block py-1">
                            <span class="font-bold">{{ $keyInner }}:</span>
                            @if (is_array($valueInner))
                              @if (!empty($valueInner))
                                @foreach ($valueInner as $subItem)
                                  <div class="ml-2">{{ is_array($subItem) ? json_encode($subItem) : $subItem }}</div>
                                @endforeach
                              @else
                                <span class="text-gray-500">Empty</span>
                              @endif
                            @else
                              {{ $valueInner }}
                            @endif
                          </span>
                        @endforeach
                      @else
                        <span class="text-gray-500">No metadata available</span>
                      @endif
                    </td>
                  @else
                    <td class="p-1 break-words">
                      @if (is_array($value))
                        @if (!empty($value))
                          @if ($key === 'categories')
                            @foreach ($value as $category)
                              <span class="inline-block bg-teal-100 text-teal-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                {{ is_object($category) ? $category->name : (is_array($category) ? $category['name'] ?? 'Unknown' : $category) }}
                              </span>
                            @endforeach
                          @elseif ($key === 'sources')
                            @foreach ($value as $source)
                              <span
                                class="inline-block bg-slate-100 text-slate-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                @if (is_object($source))
                                  {{ $source->code }} - {{ $source->name }}
                                @elseif (is_array($source))
                                  {{ $source['code'] ?? 'Unknown' }} - {{ $source['name'] ?? 'Unknown' }}
                                @else
                                  {{ $source }}
                                @endif
                              </span>
                            @endforeach
                          @else
                            {{-- For other arrays (like metadata arrays from casts) --}}
                            @foreach ($value as $item)
                              <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                            @endforeach
                          @endif
                        @else
                          <span class="text-gray-500">No data available</span>
                        @endif
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  @endif

                </tr>
              @endforeach
            </table>


          </div>
        </div>
      </div>
    </div>
</x-app-layout>
