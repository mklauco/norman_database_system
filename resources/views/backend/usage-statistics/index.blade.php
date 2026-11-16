<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          @include('backend.usage-statistics.partials.nav', ['navActive' => 'overview'])
          @include('backend.usage-statistics.partials.chart_scripts')
          @include('backend.usage-statistics.partials.month_module', ['title' => 'Searches by month', 'data' => $searches, 'table' => 'searches', 'chartId' => 'chart-searches'])
          @include('backend.usage-statistics.partials.month_module', ['title' => 'Downloads by month', 'data' => $downloads, 'table' => 'downloads', 'chartId' => 'chart-downloads'])
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
