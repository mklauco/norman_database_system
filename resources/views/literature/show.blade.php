<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Literature Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Literature Record Information at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->id ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Norman SUS ID</h3>
                <p class="text-sm text-teal-800 font-mono">
                  @if ($record->substance && $record->substance->code)
                    <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                      NS{{ $record->substance->code }}
                    </a>
                  @else
                    N/A
                  @endif
                </p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Chemical Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name ?? $record->chemical_name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->species->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species (Latin)</h3>
                <p class="text-sm text-teal-800 font-mono font-italic">{{ $record->species->name_latin ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species Class</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->species->class ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->country->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Concentration (ng/g ww)</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->ww_conc_ng !== null ? number_format($record->ww_conc_ng, 4) : 'N/A' }}</p>
              </div>
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full">
            {{-- <div class="flex justify-between items-center">
              <span class="text-xl font-bold">Complete Literature Record Details</span>
              @auth
                @role('super_admin|admin|literature')
                  <a class="link-edit" href="{{ route('literature.search.edit', $record->id) }}">
                    Edit
                  </a>
                @endrole
              @endauth
            </div> --}}

            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4">
              @foreach ($record->toArray() as $key => $value)
                {{-- Skip substance - already shown in "at Glance" section --}}
                @if ($key === 'substance')
                  @continue
                @endif

                {{-- Skip all ID fields - we'll show the relationship names instead --}}
                @if (str_ends_with($key, '_id'))
                  @continue
                @endif

                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold">{{ $key }}</td>
                  <td class="p-1">
                    @if (is_array($value))
                      @if (!empty($value))
                        {{-- Check if this is a relationship array (has 'name' key) --}}
                        @if (isset($value['name']))
                          {{ $value['name'] }}
                        @else
                          {{-- For other arrays, show each item --}}
                          @foreach ($value as $item)
                            <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                          @endforeach
                        @endif
                      @else
                        <span class="text-gray-500">No data available</span>
                      @endif
                    @else
                      {{ $value ?? '' }}
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>

          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
