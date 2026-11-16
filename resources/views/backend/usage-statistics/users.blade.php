@php
  $top = collect($usage['rows'])->sortByDesc('searches_total')->take(20)->values();
  $chartLabels = $top->pluck('name')->all();
  $chartValues = $top->pluck('searches_total')->map(fn ($v) => (int) $v)->all();
  $chartGuest = $top->pluck('is_guest')->all();
@endphp
<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          @include('backend.usage-statistics.partials.nav', ['navActive' => 'users'])
          @include('backend.usage-statistics.partials.chart_scripts')

          <section>
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">Top 20 users by searches</h2>
              <div class="flex items-center gap-2">
                @include('backend.usage-statistics.partials.png_button', ['chartId' => 'chart-users'])
                @include('backend.usage-statistics.partials.export_button', ['table' => 'users', 'year' => $year])
              </div>
            </div>
            <div class="relative mb-6" style="height: {{ max(200, count($chartLabels) * 26 + 60) }}px">
              <canvas id="chart-users"></canvas>
            </div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left">Name</th>
                    <th class="py-1 px-2 text-left">Email</th>
                    <th class="py-1 px-2 text-left">Organisation</th>
                    <th class="py-1 px-2 text-left">Country</th>
                    @foreach ($usage['modules'] as $label)
                      <th class="py-1 px-2 text-right">{{ $label }}</th>
                    @endforeach
                    <th class="py-1 px-2 text-right whitespace-nowrap">Searches total</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Downloads</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Logins</th>
                    <th class="py-1 px-2 text-left">Last login</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($usage['rows'] as $row)
                    <tr class="{{ $row['is_guest'] ? 'bg-lime-100 italic' : 'bg-gray-100' }}">
                      <td class="py-1 px-2">
                        @if ($row['is_guest'])
                          <a href="{{ route('usage-statistics.guest', ['year' => $year]) }}" class="link-lime-text font-semibold">{{ $row['name'] }}</a>
                        @else
                          <a href="{{ route('usage-statistics.users.show', ['user' => $row['user_id'], 'year' => $year]) }}" class="link-lime-text">{{ $row['name'] }}</a>
                        @endif
                      </td>
                      <td class="py-1 px-2">{{ $row['email'] }}</td>
                      <td class="py-1 px-2">{{ $row['organisation'] }}</td>
                      <td class="py-1 px-2">{{ $row['country'] }}</td>
                      @foreach ($usage['modules'] as $key => $label)
                        <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($row['searches'][$key] ?? 0), 0, '.', ' ') }}</td>
                      @endforeach
                      <td class="py-1 px-2 text-right whitespace-nowrap font-semibold">{{ number_format((int) $row['searches_total'], 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['downloads_total'], 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['logins'], 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 whitespace-nowrap">{{ $row['last_login'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        var isGuest = {{ Js::from($chartGuest) }};
        window.usageAddChart('chart-users', {
          type: 'bar',
          data: {
            labels: {{ Js::from($chartLabels) }},
            datasets: [{
              label: 'Searches',
              data: {{ Js::from($chartValues) }},
              backgroundColor: isGuest.map(function (g) { return g ? '#f59e0b' : '#84cc16'; })
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
          }
        });
      })();
    </script>
  @endpush
</x-app-layout>
