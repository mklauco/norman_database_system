@php
  use App\Services\Susdat\SusdatDisplay;

  $canSeeMerger = auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'susdat']);
  $canonical = $substance->status === 'merged' && $substance->canonical_id
      ? $substance->canonical()->first()
      : null;
  $mergedByUser = $canSeeMerger && $substance->merged_by ? $substance->mergedBy()->first() : null;
  $addedByUser = $canSeeMerger && $substance->added_by ? $substance->addedBy()->first() : null;

  $monoKeys = ['id', 'code', 'prefixed_code', 'cas_number', 'smiles', 'stdinchi', 'stdinchikey', 'pubchem_cid',
      'chemspider_id', 'dtxid', 'molecular_formula', 'canonical_id'];
  $numberKeys = ['mass_iso', 'average_mass'];
  $dateKeys = ['created_at', 'updated_at', 'deleted_at', 'merged_at'];
  $identifierKeyPattern = '/ID|Id|CAS|SMILES|InChI|cas|smiles|inchi/';

  $glance = [
      ['NORMAN SusDat ID', $substance->prefixed_code, 'font-mono'],
      ['Name', $substance->name, 'text'],
      ['CAS RN', $substance->cas_number, 'font-mono'],
      ['StdInChIKey', $substance->stdinchikey, 'font-mono'],
      ['DTXSID', $substance->dtxid, 'font-mono'],
      ['PubChem CID', $substance->pubchem_cid, 'font-mono'],
      ['Molecular Formula', $substance->molecular_formula, 'font-mono'],
      ['SMILES', $substance->smiles, 'font-mono break-all'],
      ['Monoisotopic Mass', $substance->mass_iso, 'number'],
      ['Average Mass', $substance->average_mass, 'number'],
  ];

  $rows = [];
  foreach ($substance->toArray() as $key => $value) {
      if (in_array($key, ['merged_by', 'added_by'], true) && ! $canSeeMerger) {
          continue;
      }
      $rows[$key] = $value;
  }
@endphp
<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

      <div class="bg-white shadow-lg sm:rounded-lg">

        <div class="p-6 text-gray-900">

          @if ($canonical)
            <div class="mb-6 p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded-md text-sm" role="status">
              This substance was merged into
              <a class="link-lime-text break-words" href="{{ route('substances.show', $canonical->id) }}">
                <span class="font-mono">{{ $canonical->prefixed_code }}</span>
                {{ $canonical->name ?? SusdatDisplay::EMPTY }}
              </a>
              @if ($substance->merged_at)
                on {{ SusdatDisplay::date($substance->merged_at) }}
              @endif
            </div>
          @endif

          {{-- Substance Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Substance Information at Glance</h3>
            <div class="flex flex-col lg:flex-row gap-6">
              @if($substance->structure_image_url)
              <div class="shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button type="button"
                        class="block rounded-lg focus:outline-hidden focus-visible:ring-2 focus-visible:ring-lime-500"
                        aria-label="Enlarge the structure of {{ $substance->name }}"
                        aria-haspopup="dialog"
                        @click="open = true">
                  <img src="{{ $substance->structure_image_url }}"
                       alt="Structure of {{ $substance->name }}"
                       class="w-48 h-48 object-contain bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:border-gray-400 transition-colors">
                </button>
                <!-- Modal overlay -->
                <div x-show="open"
                     x-cloak
                     role="dialog"
                     aria-modal="true"
                     aria-label="Structure of {{ $substance->name }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="open = false"
                     x-effect="if (open) $nextTick(() => $refs.close.focus())"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                  <div class="relative" @click.stop>
                    <button type="button" x-ref="close"
                            class="absolute top-2 right-2 rounded-full bg-white p-1 text-gray-800 shadow-sm hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-lime-500"
                            aria-label="Close the structure image"
                            @click="open = false">
                      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                    <img src="{{ $substance->structure_image_url }}"
                         alt="Structure of {{ $substance->name }}"
                         class="max-w-full max-h-[90vh] object-contain bg-white rounded-lg p-4 shadow-2xl">
                  </div>
                </div>
              </div>
              @endif
              <div class="grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($glance as [$label, $glanceValue, $kind])
                  <div>
                    <h4 class="text-sm font-medium text-gray-800 mb-1">{{ $label }}</h4>
                    @if (SusdatDisplay::isEmpty($glanceValue))
                      <p class="text-sm text-gray-500">{{ SusdatDisplay::EMPTY }}</p>
                    @elseif ($kind === 'number')
                      <p class="text-sm text-teal-800 font-mono">{{ SusdatDisplay::number($glanceValue) }}</p>
                    @elseif ($kind === 'text')
                      <p class="text-sm text-teal-800 break-words">{{ $glanceValue }}</p>
                    @else
                      <p class="text-sm text-teal-800 {{ $kind }}">{{ $glanceValue }}</p>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <div class="w-full">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-800">Complete Substance Details</h3>
              @auth
                @role('super_admin|admin|susdat')
                  <a class="link-edit" href="{{ route('substances.edit', $substance->id) }}">
                    Edit
                  </a>
                @endrole
              @endauth
            </div>

            <table class="table-fixed w-full border-separate border-spacing-1 text-xs">
              @foreach ($rows as $key => $value)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold w-40 align-top">{{ config('substance_field_labels.'.$key, $key) }}</td>
                  @if (substr($key, 0, 8) == 'metadata')
                    @php
                      $decodedJson = is_string($value) ? json_decode($value, true) : $value;
                      $decodedJson = is_array($decodedJson) ? $decodedJson : [];
                      $formatNumbers = in_array($key, ['metadata_general', 'metadata_ms_ready'], true);
                    @endphp
                    <td class="p-1 break-words">
                      @if (! empty($decodedJson))
                        @foreach ($decodedJson as $keyInner => $valueInner)
                          <span class="block py-1">
                            <span class="font-bold">{{ $keyInner }}:</span>
                            @if (is_array($valueInner))
                              @if (! empty($valueInner))
                                @foreach ($valueInner as $subItem)
                                  <div class="ml-2">{{ is_array($subItem) ? json_encode($subItem) : $subItem }}</div>
                                @endforeach
                              @else
                                <span class="text-gray-500">{{ SusdatDisplay::EMPTY_LIST }}</span>
                              @endif
                            @elseif (SusdatDisplay::isEmpty($valueInner))
                              <span class="text-gray-500">{{ SusdatDisplay::EMPTY }}</span>
                            @elseif ($formatNumbers && is_string($keyInner) && ! preg_match($identifierKeyPattern, $keyInner) && is_numeric($valueInner))
                              {{ SusdatDisplay::number($valueInner) }}
                            @else
                              {{ $valueInner }}
                            @endif
                          </span>
                        @endforeach
                      @else
                        <span class="text-gray-500">{{ SusdatDisplay::EMPTY_LIST }}</span>
                      @endif
                    </td>
                  @else
                    <td class="p-1 break-words {{ in_array($key, $monoKeys, true) ? 'font-mono' : '' }}">
                      @if ($key === 'canonical_id' && $canonical)
                        <a class="link-lime-text" href="{{ route('substances.show', $canonical->id) }}">{{ $canonical->prefixed_code }}</a>
                      @elseif (in_array($key, ['merged_by', 'added_by'], true))
                        @php($user = $key === 'merged_by' ? $mergedByUser : $addedByUser)
                        @if ($user)
                          <span class="font-sans">{{ $user->formatted_name }}</span>
                        @else
                          <span class="font-sans text-gray-500">{{ SusdatDisplay::EMPTY }}</span>
                        @endif
                      @elseif (is_array($value))
                        @if (! empty($value))
                          @if ($key === 'categories')
                            @foreach ($value as $category)
                              <span class="inline-block bg-teal-100 text-teal-800 text-xs px-2 py-1 rounded-sm mr-1 mb-1">
                                {{ is_object($category) ? $category->name : (is_array($category) ? $category['name'] ?? 'Unknown' : $category) }}
                              </span>
                            @endforeach
                          @elseif ($key === 'sources')
                            @foreach ($value as $source)
                              <span
                                class="inline-block bg-slate-100 text-slate-800 text-xs px-2 py-1 rounded-sm mr-1 mb-1">
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
                            @foreach ($value as $item)
                              <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                            @endforeach
                          @endif
                        @else
                          <span class="font-sans text-gray-500">{{ SusdatDisplay::EMPTY_LIST }}</span>
                        @endif
                      @elseif (SusdatDisplay::isEmpty($value))
                        <span class="font-sans text-gray-500">{{ SusdatDisplay::EMPTY }}</span>
                      @elseif (in_array($key, $numberKeys, true))
                        <span class="font-mono">{{ SusdatDisplay::number($value) }}</span>
                      @elseif (in_array($key, $dateKeys, true))
                        {{ SusdatDisplay::date($value) }}
                      @elseif (is_bool($value))
                        {{ $value ? 'Yes' : 'No' }}
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
  </div>
</x-app-layout>
