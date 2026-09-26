<x-app-layout>
  <x-slot name="header">
    @include('backend.system-settings.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

      <!-- Welcome Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Maintenance</h2>
        <p class="text-gray-600">Recount or rebuild data shown across the database modules</p>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
          <table class="table-standard">
            <thead>
              <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                <th class="px-4 py-3">Database</th>
                <th class="px-4 py-3">Operation</th>
                <th class="px-4 py-3">What it touches</th>
                <th class="px-4 py-3">Run</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach ($operationGroups as $group)
                @foreach ($group['operations'] as $index => $operation)
                  <tr>
                    @if ($index === 0)
                      <td class="px-4 py-3 align-top font-medium text-gray-800" rowspan="{{ count($group['operations']) }}">
                        {{ $group['database'] }}
                      </td>
                    @endif
                    <td class="px-4 py-3 align-top">
                      <div class="text-sm font-medium text-gray-800">{{ $operation['label'] }}</div>
                      <div class="text-xs text-gray-500 mt-0.5">{{ $operation['description'] }}</div>
                      @if ($operation['warning'])
                        <div class="text-xs text-amber-700 mt-1">
                          <i class="fas fa-exclamation-triangle mr-1"></i>{{ $operation['warning'] }}
                        </div>
                      @endif
                    </td>
                    <td class="px-4 py-3 align-top text-sm text-gray-600">{{ $operation['touches'] }}</td>
                    <td class="px-4 py-3 align-top">
                      @php
                        $confirmMessage = $operation['warning']
                          ? "Run \"{$operation['label']}\" now? {$operation['warning']}"
                          : "Run \"{$operation['label']}\" now?";
                      @endphp
                      @if ($operation['method'] === 'GET')
                        <a href="{{ route($operation['route']) }}"
                           onclick="return confirm({{ json_encode($confirmMessage) }});"
                           class="btn-submit text-xs">
                          Run
                        </a>
                      @else
                        <form action="{{ route($operation['route']) }}" method="POST"
                              onsubmit="return confirm({{ json_encode($confirmMessage) }});">
                          @csrf
                          <button type="submit" class="btn-submit text-xs">Run</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</x-app-layout>
