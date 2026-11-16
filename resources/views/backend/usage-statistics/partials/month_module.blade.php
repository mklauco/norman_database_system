@php
  $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
  $datasets = [];
  $i = 0;
  foreach ($data['modules'] as $key => $label) {
      $datasets[] = [
          'label' => $label,
          'data' => array_map(fn ($m) => (int) ($data['months'][$m][$key] ?? 0), array_keys($months)),
          'backgroundColor' => '',
      ];
      $i++;
  }
@endphp
<section class="mb-10">
  <div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    <div class="flex items-center gap-2">
      @include('backend.usage-statistics.partials.png_button', ['chartId' => $chartId])
      @include('backend.usage-statistics.partials.export_button', ['table' => $table, 'year' => $year, 'params' => $params ?? []])
    </div>
  </div>
  <div class="relative h-72 mb-4">
    <canvas id="{{ $chartId }}"></canvas>
  </div>
  <div class="overflow-x-auto">
    <table class="table-standard">
      <thead>
        <tr class="bg-gray-600 text-white">
          <th class="py-1 px-2 text-left">Month</th>
          @foreach ($data['modules'] as $label)
            <th class="py-1 px-2 text-right">{{ $label }}</th>
          @endforeach
          <th class="py-1 px-2 text-right whitespace-nowrap">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($months as $m => $name)
          <tr class="bg-gray-100">
            <td class="py-1 px-2">{{ $name }}</td>
            @foreach ($data['modules'] as $key => $label)
              <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($data['months'][$m][$key] ?? 0), 0, '.', ' ') }}</td>
            @endforeach
            <td class="py-1 px-2 text-right whitespace-nowrap font-semibold">{{ number_format((int) ($data['monthTotals'][$m] ?? 0), 0, '.', ' ') }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="bg-gray-200 font-semibold">
          <td class="py-1 px-2">Total</td>
          @foreach ($data['modules'] as $key => $label)
            <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($data['moduleTotals'][$key] ?? 0), 0, '.', ' ') }}</td>
          @endforeach
          <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) $data['total'], 0, '.', ' ') }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</section>
@push('scripts')
  <script>
    (function () {
      var datasets = {{ Js::from($datasets) }};
      datasets.forEach(function (d, i) { d.backgroundColor = window.usagePalette[i % window.usagePalette.length]; });
      window.usageAddChart(@json($chartId), {
        type: 'bar',
        data: { labels: {{ Js::from(array_values($months)) }}, datasets: datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
          plugins: { legend: { position: 'bottom' } }
        }
      });
    })();
  </script>
@endpush
