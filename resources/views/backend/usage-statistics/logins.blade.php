@php
  $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
  $monthLogins = array_map(fn ($m) => (int) ($byMonth['months'][$m]['logins'] ?? 0), array_keys($months));
  $monthUsers = array_map(fn ($m) => (int) ($byMonth['months'][$m]['users'] ?? 0), array_keys($months));
  $geoTop = array_slice($byGeo, 0, 15);
  $profileTop = array_slice($byProfile, 0, 15);
@endphp
<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          @include('backend.usage-statistics.partials.nav', ['navActive' => 'logins'])
          @include('backend.usage-statistics.partials.chart_scripts')

          <section class="mb-10">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">Logins by month</h2>
              @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-month', 'year' => $year])
            </div>
            <div class="relative h-72 mb-4"><canvas id="chart-logins-month"></canvas></div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left">Month</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Logins</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Distinct users</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($months as $m => $name)
                    <tr class="bg-gray-100">
                      <td class="py-1 px-2">{{ $name }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($byMonth['months'][$m]['logins'] ?? 0), 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($byMonth['months'][$m]['users'] ?? 0), 0, '.', ' ') }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="bg-gray-200 font-semibold">
                    <td class="py-1 px-2">Total</td>
                    <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $byMonth['total_logins'], 0, '.', ' ') }}</td>
                    <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $byMonth['total_users'], 0, '.', ' ') }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </section>

          <section class="mb-10">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">Logins by IP country</h2>
              @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-geo', 'year' => $year])
            </div>
            <div class="relative h-72 mb-4"><canvas id="chart-logins-geo"></canvas></div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left">Code</th>
                    <th class="py-1 px-2 text-left">Country</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Logins</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Users</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($byGeo as $row)
                    <tr class="bg-gray-100">
                      <td class="py-1 px-2 font-mono">{{ $row['country_code'] }}</td>
                      <td class="py-1 px-2">{{ $row['country'] }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['logins'], 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['users'], 0, '.', ' ') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p class="mt-2 text-xs text-gray-500">
              The IP country is looked up at login time and can be missing. The profile country (below) is what users entered themselves.
            </p>
          </section>

          <section>
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">Users by profile country</h2>
              @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-profile', 'year' => $year])
            </div>
            <div class="relative h-72 mb-4"><canvas id="chart-logins-profile"></canvas></div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left">Country</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Users</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Logins</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($byProfile as $row)
                    <tr class="bg-gray-100">
                      <td class="py-1 px-2">{{ $row['country'] }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['users'], 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $row['logins'], 0, '.', ' ') }}</td>
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
        var p = window.usagePalette;
        var base = { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'bottom' } } };
        window.usageAddChart('chart-logins-month', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_values($months)) }},
            datasets: [
              { label: 'Logins', data: {{ Js::from($monthLogins) }}, backgroundColor: p[0] },
              { label: 'Distinct users', data: {{ Js::from($monthUsers) }}, backgroundColor: p[2] }
            ]
          },
          options: base
        });
        window.usageAddChart('chart-logins-geo', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_map(fn ($r) => $r['country'], $geoTop)) }},
            datasets: [{ label: 'Logins', data: {{ Js::from(array_map(fn ($r) => (int) $r['logins'], $geoTop)) }}, backgroundColor: p[1] }]
          },
          options: base
        });
        window.usageAddChart('chart-logins-profile', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_map(fn ($r) => $r['country'], $profileTop)) }},
            datasets: [{ label: 'Users', data: {{ Js::from(array_map(fn ($r) => (int) $r['users'], $profileTop)) }}, backgroundColor: p[4] }]
          },
          options: base
        });
      })();
    </script>
  @endpush
</x-app-layout>
