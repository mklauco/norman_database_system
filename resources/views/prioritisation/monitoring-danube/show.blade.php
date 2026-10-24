<x-app-layout>
  <x-slot name="header">
    @include('prioritisation.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="shrink-0">
                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  <strong>Note:</strong> This record is displayed in a new browser tab.
                  To return to your search results, please switch to the previous tab instead of using navigation links. You may use keystroke <span class="font-mono">CTRL+SHIFT+TAB</span>.
                </p>
              </div>
            </div>
          </div>

          {{-- Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Prioritisation Monitoring Danube Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @if ($record->pri_no)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->pri_no }}</p>
                </div>
              @endif
              @if ($record->pri_substance)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->pri_substance }}</p>
                </div>
              @endif
              @if ($record->pri_cas_no)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">CAS No.</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->pri_cas_no }}</p>
                </div>
              @endif
              @if ($record->pri_category)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Category</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->pri_category }}</p>
                </div>
              @endif
              @if ($record->pri_final_score)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Final Score</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ number_format($record->pri_final_score, 2) }}</p>
                </div>
              @endif
              @if ($record->pri_pnec_type)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">PNEC Type</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->pri_pnec_type }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @php
                $rowIndex = 0;
                $excludedKeys = ['created_at', 'updated_at'];
              @endphp

              @foreach ($record->toArray() as $key => $value)
                {{-- Skip relationships and system fields --}}
                @if (in_array($key, $excludedKeys))
                  @continue
                @endif

                {{-- Skip null values and empty arrays --}}
                @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                  @continue
                @endif

                <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if (is_array($value))
                      {{ json_encode($value) }}
                    @else
                      {{ $value }}
                    @endif
                  </td>
                </tr>
                @php $rowIndex++; @endphp
              @endforeach

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
