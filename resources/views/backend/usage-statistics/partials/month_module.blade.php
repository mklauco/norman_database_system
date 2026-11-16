@php
  $valueAxis = $table === 'downloads' ? 'Number of downloads' : 'Number of searches';
  $datasets = [];
  $i = 0;
  foreach ($data['modules'] as $key => $label) {
      $datasets[] = [
          'label' => $label,
          'data' => array_map(fn ($m) => (int) ($data['periods'][$m][$key] ?? 0), array_keys($data['periodLabels'])),
          'backgroundColor' => '',
      ];
      $i++;
  }
  $fullTitle = $title.' by '.strtolower($data['periodName']).' – '.$yearLabel;
  $chartTitle = isset($subjectName) ? $fullTitle.' – '.$subjectName : $fullTitle;
@endphp
<section class="mb-10">
  <div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-semibold">{{ $fullTitle }}</h2>
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
          <th class="py-1 px-2 text-left">{{ $data['periodName'] }}</th>
          @foreach ($data['modules'] as $label)
            <th class="py-1 px-2 text-right">{{ $label }}</th>
          @endforeach
          <th class="py-1 px-2 text-right whitespace-nowrap">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($data['periodLabels'] as $m => $name)
          <tr class="bg-gray-100">
            <td class="py-1 px-2">{{ $name }}</td>
            @foreach ($data['modules'] as $key => $label)
              <td class="py-1 px-2 text-right whitespace-nowrap">{{ number_format((int) ($data['periods'][$m][$key] ?? 0), 0, '.', ' ') }}</td>
            @endforeach
            <td class="py-1 px-2 text-right whitespace-nowrap font-semibold">{{ number_format((int) ($data['periodTotals'][$m] ?? 0), 0, '.', ' ') }}</td>
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
        data: { labels: {{ Js::from(array_values($data['periodLabels'])) }}, datasets: datasets },
        options: window.usageOptions({
          title: @json($chartTitle),
          xTitle: @json($data['periodName']),
          yTitle: @json($valueAxis),
          stacked: true
        })
      });
    })();
  </script>
@endpush
