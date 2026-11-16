@php
  $fmt = fn (int $n): string => number_format($n, 0, '.', ' ');
  $exportQuery = array_merge(['table' => 'activity-timeline', 'date' => $date], $filters);
@endphp
<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          @include('backend.usage-statistics.partials.nav', ['navActive' => 'activity', 'years' => []])

          @if ($errors->any())
            <div class="mb-4 p-3 text-sm text-red-700 bg-red-50 border border-red-300 rounded-sm">
              @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
              @endforeach
            </div>
          @endif

          <form method="GET" action="{{ route('usage-statistics.activity') }}" class="flex flex-wrap items-end gap-4 mb-6">
            <div>
              <label for="activity-date" class="block text-sm font-medium mb-1">Date</label>
              <div class="flex items-center gap-3">
                <a href="{{ route('usage-statistics.activity', array_merge($filters, ['date' => $previousDate])) }}" class="link-lime-text text-sm whitespace-nowrap">&larr; previous day</a>
                <input type="date" id="activity-date" name="date" value="{{ $date }}" max="{{ $today }}" class="border rounded-sm px-2 py-1 text-sm">
                @if ($nextDate !== null)
                  <a href="{{ route('usage-statistics.activity', array_merge($filters, ['date' => $nextDate])) }}" class="link-lime-text text-sm whitespace-nowrap">next day &rarr;</a>
                @endif
              </div>
            </div>
            <div>
              <label for="activity-user" class="block text-sm font-medium mb-1">User</label>
              <select id="activity-user" name="user" class="border rounded-sm pl-2 pr-8 py-1 text-sm min-w-48">
                <option value="">All users</option>
                @foreach ($options['users'] as $id => $label)
                  <option value="{{ $id }}" @selected((int) ($filters['user'] ?? 0) === $id)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="activity-area" class="block text-sm font-medium mb-1">Area</label>
              <select id="activity-area" name="area" class="border rounded-sm pl-2 pr-8 py-1 text-sm min-w-40">
                <option value="">All areas</option>
                @foreach ($options['areas'] as $label)
                  <option value="{{ $label }}" @selected(($filters['area'] ?? null) === $label)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="activity-action" class="block text-sm font-medium mb-1">Action</label>
              <select id="activity-action" name="action" class="border rounded-sm pl-2 pr-8 py-1 text-sm min-w-36">
                <option value="">All actions</option>
                @foreach ($options['actions'] as $key => $label)
                  <option value="{{ $key }}" @selected(($filters['action'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="px-3 py-1 bg-lime-600 text-white rounded-sm text-sm hover:bg-lime-500 transition duration-300">Show</button>
          </form>

          <section>
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">{{ $fmt($timeline->total()) }} requests on {{ $date }}</h2>
              <a href="{{ route('usage-statistics.activity.export', $exportQuery) }}"
                 class="inline-flex items-center px-2 py-1 text-xs border border-lime-600 text-lime-700 rounded-sm hover:bg-lime-500 hover:text-white transition duration-300">
                Download XLSX
              </a>
            </div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left whitespace-nowrap">Time</th>
                    <th class="py-1 px-2 text-left">User</th>
                    <th class="py-1 px-2 text-left">Country</th>
                    <th class="py-1 px-2 text-left">Area</th>
                    <th class="py-1 px-2 text-left">What</th>
                    <th class="py-1 px-2 text-left">Action</th>
                    <th class="py-1 px-2 text-left text-xs">Page</th>
                    <th class="py-1 px-2 text-left">Method</th>
                    <th class="py-1 px-2 text-left">Status</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Duration ms</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($timeline as $row)
                    @php
                      $country = $countries[$row->user_id] ?? null;
                      $ok = $row->status === null || ($row->status >= 200 && $row->status < 400);
                    @endphp
                    <tr class="bg-gray-100">
                      <td class="py-1 px-2 whitespace-nowrap font-mono">{{ $row->occurred_at->copy()->setTimezone('Europe/Bratislava')->format('H:i:s') }}</td>
                      <td class="py-1 px-2"><a href="{{ route('usage-statistics.activity', array_merge($filters, ['date' => $date, 'user' => $row->user_id])) }}" class="link-lime-text">{{ trim($row->first_name.' '.$row->last_name) }}</a></td>
                      <td class="py-1 px-2">{{ $country['country'] ?? '' }}@if (($country['source'] ?? null) === 'ip') <span class="text-xs text-gray-500" title="Country from the IP address of the last login (profile country not set)">(IP)</span>@endif</td>
                      <td class="py-1 px-2">{{ $row->area }}</td>
                      <td class="py-1 px-2">
                        <div>{{ $row->description }}</div>
                        @if ($row->details)
                          <div class="text-xs text-gray-500 break-words">{{ $row->details }}</div>
                        @endif
                      </td>
                      <td class="py-1 px-2 whitespace-nowrap">{{ $actionLabels[$row->action] ?? $row->action }}</td>
                      <td class="py-1 px-2 text-xs">
                        <div>{{ $row->route_name }}</div>
                        <div class="text-xs text-gray-500 break-all">{{ $row->path }}</div>
                      </td>
                      <td class="py-1 px-2">{{ $row->method }}</td>
                      <td class="py-1 px-2 font-mono {{ $ok ? '' : 'text-red-600' }}">{{ $row->status }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ $row->duration_ms === null ? '' : $fmt((int) $row->duration_ms) }}</td>
                    </tr>
                  @empty
                    <tr class="bg-gray-100"><td class="py-1 px-2 text-gray-500" colspan="10">No activity logged for this selection.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="mt-4">{{ $timeline->links() }}</div>
          </section>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
