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
          <div class="mb-6">
            <a href="{{ route('usage-statistics.users', ['year' => $year]) }}" class="link-lime-text text-sm">&larr; Back to Users</a>
            <h1 class="text-xl font-semibold mt-2">{{ $subjectName }}</h1>
            @if ($subjectEmail)
              <div class="text-sm text-gray-500">{{ $subjectEmail }}</div>
            @endif
          </div>
          @include('backend.usage-statistics.partials.month_module', ['title' => 'Searches', 'data' => $searches, 'table' => 'searches', 'params' => $exportParams, 'chartId' => 'chart-searches', 'subjectName' => $subjectName])
          @include('backend.usage-statistics.partials.month_module', ['title' => 'Downloads', 'data' => $downloads, 'table' => 'downloads', 'params' => $exportParams, 'chartId' => 'chart-downloads', 'subjectName' => $subjectName])
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
