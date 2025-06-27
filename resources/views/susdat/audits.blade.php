<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h2 class="text-xl font-semibold">Audit History</h2>
            <p class="text-gray-600">
              Substance: <strong>{{ $substance->name }}</strong> (ID: {{ $substance->id }})
            </p>
            <a href="{{ route('substances.show', $substance) }}" class="text-blue-600 hover:text-blue-800">
              ← Back to Substance
            </a>
          </div>

          @if($audits->count() > 0)
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
                  <td class="px-4 py-2">{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                  <td class="px-4 py-2">
                    <span class="px-2 py-1 text-xs rounded
                      @if($audit->event == 'created') bg-green-100 text-green-800
                      @elseif($audit->event == 'updated') bg-blue-100 text-blue-800
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
                        <div class="mb-1">
                          <strong>{{ $field }}:</strong> 
                          <span class="text-red-600">{{ $oldValue }}</span> → 
                          <span class="text-green-600">{{ $audit->new_values[$field] ?? 'null' }}</span>
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
            
            {{ $audits->links() }}
          @else
            <p class="text-gray-500">No audit history found for this substance.</p>
          @endif
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>