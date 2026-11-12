<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Audit History</h2>
            <p class="text-gray-600">
              Substance: <strong>{{ $substance->name }}</strong> (ID: <span class="font-mono">{{ $substance->id }}</span>)
            </p>
            <a href="{{ route('substances.show', $substance) }}" class="link-lime-text">
              ← Back to Substance
            </a>
          </div>

          @if($audits->count() > 0)
            <div class="overflow-x-auto">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-4 py-2">Date</th>
                  <th class="px-4 py-2">Event</th>
                  <th class="px-4 py-2">User</th>
                  <th class="px-4 py-2">Changes</th>
                </tr>
              </thead>
              <tbody>
                @foreach($audits as $audit)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="px-4 py-2">{{ \App\Services\Susdat\SusdatDisplay::date($audit->created_at) }}</td>
                  <td class="px-4 py-2">
                    <span class="inline-block rounded-sm px-2 py-0.5 text-xs font-medium
                      @if($audit->event == 'created') bg-green-100 text-green-800
                      @elseif($audit->event == 'updated') bg-amber-100 text-amber-800
                      @elseif($audit->event == 'deleted') bg-red-100 text-red-800
                      @else bg-gray-100 text-gray-800 @endif">
                      {{ ucfirst($audit->event) }}
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    {{ $audit->user->name ?? 'System' }}
                  </td>
                  <td class="px-4 py-2 text-sm">
                    @if($audit->event == 'updated' && $audit->old_values)
                      @foreach($audit->old_values as $field => $oldValue)
                        @php
                          $newValue = $audit->new_values[$field] ?? 'null';
                          $fmt = function ($v): string {
                            if (is_array($v)) {
                              return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }
                            $v = (string) $v;
                            if ($v !== '' && in_array($v[0], ['{', '['], true)) {
                              $d = json_decode($v, true);
                              if (is_array($d)) {
                                return json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                              }
                            }
                            return $v;
                          };
                        @endphp
                        <div class="mb-2 min-w-0">
                          <strong>{{ $field }}:</strong>
                          <pre class="whitespace-pre-wrap break-all font-mono text-xs text-red-600">{{ $fmt($oldValue) }}</pre>
                          <span aria-hidden="true">→</span>
                          <pre class="whitespace-pre-wrap break-all font-mono text-xs text-green-600">{{ $fmt($newValue) }}</pre>
                        </div>
                      @endforeach
                    @elseif($audit->event == 'created')
                      Record created
                    @elseif($audit->event == 'deleted')
                      Record deleted
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            </div>

            {{ $audits->links('pagination::tailwind') }}
          @else
            <p class="text-gray-500">No audit history found for this substance.</p>
          @endif
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>