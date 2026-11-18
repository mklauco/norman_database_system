@php
  $monthLogins = array_map(fn ($m) => (int) ($byMonth['periods'][$m]['logins'] ?? 0), array_keys($byMonth['periodLabels']));
  $monthUsers = array_map(fn ($m) => (int) ($byMonth['periods'][$m]['users'] ?? 0), array_keys($byMonth['periodLabels']));
  $monthIps = array_map(fn ($m) => (int) ($byMonth['periods'][$m]['ips'] ?? 0), array_keys($byMonth['periodLabels']));
  $periodLower = strtolower($byMonth['periodName']);
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
              <h2 class="text-lg font-semibold">Logins by {{ strtolower($byMonth['periodName']) }} – {{ $yearLabel }}</h2>
              <div class="flex items-center gap-2">
                @include('backend.usage-statistics.partials.png_button', ['chartId' => 'chart-logins-month'])
                @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-month', 'year' => $year ?? 'all'])
              </div>
            </div>
            <div class="relative h-72 mb-4"><canvas id="chart-logins-month"></canvas></div>
            <div class="overflow-x-auto">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2 text-left">{{ $byMonth['periodName'] }}</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Logins</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Distinct users</th>
                    <th class="py-1 px-2 text-right whitespace-nowrap">Distinct IPs</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($byMonth['periodLabels'] as $m => $name)
                    <tr class="bg-gray-100">
                      <td class="py-1 px-2">{{ $name }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($byMonth['periods'][$m]['logins'] ?? 0), 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($byMonth['periods'][$m]['users'] ?? 0), 0, '.', ' ') }}</td>
                      <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($byMonth['periods'][$m]['ips'] ?? 0), 0, '.', ' ') }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="bg-gray-200 font-semibold">
                    <td class="py-1 px-2">Total</td>
                    <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $byMonth['total_logins'], 0, '.', ' ') }}</td>
                    <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $byMonth['total_users'], 0, '.', ' ') }}</td>
                    <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $byMonth['total_ips'], 0, '.', ' ') }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </section>

          <section class="mb-10">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold">Logins by IP country – {{ $yearLabel }}</h2>
              <div class="flex items-center gap-2">
                @include('backend.usage-statistics.partials.png_button', ['chartId' => 'chart-logins-geo'])
                @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-geo', 'year' => $year ?? 'all'])
              </div>
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
              <h2 class="text-lg font-semibold">Users by profile country – {{ $yearLabel }}</h2>
              <div class="flex items-center gap-2">
                @include('backend.usage-statistics.partials.png_button', ['chartId' => 'chart-logins-profile'])
                @include('backend.usage-statistics.partials.export_button', ['table' => 'logins-profile', 'year' => $year ?? 'all'])
              </div>
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
        var yl = @json($yearLabel);
        window.usageAddChart('chart-logins-month', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_values($byMonth['periodLabels'])) }},
            datasets: [
              { label: 'Logins', data: {{ Js::from($monthLogins) }}, backgroundColor: p[0] },
              { label: 'Distinct users', data: {{ Js::from($monthUsers) }}, backgroundColor: p[2] },
              { label: 'Distinct IPs', data: {{ Js::from($monthIps) }}, backgroundColor: p[4] }
            ]
          },
          options: window.usageOptions({ title: 'Logins by {{ $periodLower }} – ' + yl, xTitle: @json($byMonth['periodName']), yTitle: 'Number of logins / users / IPs' })
        });
        window.usageAddChart('chart-logins-geo', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_map(fn ($r) => $r['country'], $geoTop)) }},
            datasets: [{ label: 'Logins', data: {{ Js::from(array_map(fn ($r) => (int) $r['logins'], $geoTop)) }}, backgroundColor: p[1] }]
          },
          options: window.usageOptions({ title: 'Logins by IP country – ' + yl, xTitle: 'Country', yTitle: 'Number of logins', legend: false })
        });
        window.usageAddChart('chart-logins-profile', {
          type: 'bar',
          data: {
            labels: {{ Js::from(array_map(fn ($r) => $r['country'], $profileTop)) }},
            datasets: [{ label: 'Users', data: {{ Js::from(array_map(fn ($r) => (int) $r['users'], $profileTop)) }}, backgroundColor: p[4] }]
          },
          options: window.usageOptions({ title: 'Users by profile country – ' + yl, xTitle: 'Country', yTitle: 'Number of users', legend: false })
        });
      })();
    </script>
  @endpush
</x-app-layout>
