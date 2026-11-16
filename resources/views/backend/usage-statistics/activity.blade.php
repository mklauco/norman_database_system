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

          <form method="GET" action="{{ route('usage-statistics.activity') }}" id="activity-filters"
                x-data
                x-on:user-search-changed.window="if ($event.detail.field === 'user') { $el.querySelector('input[name=user]').value = $event.detail.userId ?? ''; $el.submit(); }"
                class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 md:items-end xl:grid-cols-[auto_minmax(16rem,22rem)_minmax(12rem,16rem)_minmax(9rem,11rem)_auto]">
            <div>
              <label for="activity-date" class="block text-sm font-medium mb-1">Date</label>
              <div class="flex items-center gap-1">
                <a href="{{ route('usage-statistics.activity', array_merge($filters, ['date' => $previousDate])) }}" title="Previous day"
                   class="inline-flex items-center justify-center h-9 w-9 border border-gray-300 rounded-sm text-lime-700 hover:bg-lime-50">&larr;</a>
                <input type="date" id="activity-date" name="date" value="{{ $date }}" max="{{ $today }}" onchange="this.form.submit()"
                       class="h-9 border border-gray-300 rounded-sm px-2 text-sm">
                @if ($nextDate !== null)
                  <a href="{{ route('usage-statistics.activity', array_merge($filters, ['date' => $nextDate])) }}" title="Next day"
                     class="inline-flex items-center justify-center h-9 w-9 border border-gray-300 rounded-sm text-lime-700 hover:bg-lime-50">&rarr;</a>
                @else
                  <span class="inline-flex items-center justify-center h-9 w-9 border border-gray-200 rounded-sm text-gray-300" title="Today is the latest day">&rarr;</span>
                @endif
              </div>
            </div>
            <div class="min-w-0">
              <span class="block text-sm font-medium mb-1">User <span class="text-gray-400 font-normal">(empty = all users)</span></span>
              @livewire('backend.user-search', ['selectedUserId' => $filters['user'] ?? null, 'fieldName' => 'user', 'compact' => true, 'submitOnChange' => true], key('activity-user-'.($filters['user'] ?? 'all')))
            </div>
            <div class="min-w-0">
              <label for="activity-area" class="block text-sm font-medium mb-1">Area</label>
              <select id="activity-area" name="area" onchange="this.form.submit()" class="w-full h-9 border border-gray-300 rounded-sm pl-2 pr-8 text-sm">
                <option value="">All areas</option>
                @foreach ($options['areas'] as $label)
                  <option value="{{ $label }}" @selected(($filters['area'] ?? null) === $label)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="min-w-0">
              <label for="activity-action" class="block text-sm font-medium mb-1">Action</label>
              <select id="activity-action" name="action" onchange="this.form.submit()" class="w-full h-9 border border-gray-300 rounded-sm pl-2 pr-8 text-sm">
                <option value="">All actions</option>
                @foreach ($options['actions'] as $key => $label)
                  <option value="{{ $key }}" @selected(($filters['action'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <a href="{{ route('usage-statistics.activity', ['date' => $date]) }}" class="inline-flex items-center h-9 px-3 text-sm border border-gray-300 rounded-sm text-gray-700 hover:bg-gray-50">Reset filters</a>
            </div>
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
