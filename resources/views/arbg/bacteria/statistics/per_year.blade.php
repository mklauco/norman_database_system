@extends('arbg.bacteria.statistics.layout')

@section('page-title', 'Records by Year')
@section('page-subtitle', 'Number of bacteria records per sampling year')

@section('main-content')
  @if(isset($generatedAt))
    <div class="mb-4 text-sm text-gray-600">
      Data generated: {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i:s') }}
    </div>
  @endif

  @if(isset($message))
    <!-- No Data Message -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="text-yellow-800">{{ $message }}</div>
      <a href="{{ route('arbg.bacteria.statistics.index') }}" class="text-sky-600 hover:text-sky-800 underline text-sm">
        Go back to statistics overview
      </a>
    </div>
  @elseif(empty($data))
    <!-- Empty Data -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600 text-lg mb-2">No data available.</div>
      <div class="text-sm text-gray-500">Please generate statistics first.</div>
    </div>
  @else
    <!-- Summary Card -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <div class="text-sm text-gray-600">Total Years</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalYears, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Total Records (sum)</div>
          <div class="text-2xl font-bold text-gray-900">
            {{ number_format(collect($data)->sum(), 0, '.', ' ') }}
          </div>
        </div>
        @if(isset($yearRange))
        <div>
          <div class="text-sm text-gray-600">First Year</div>
          <div class="text-2xl font-bold text-gray-900">{{ $yearRange['min_year'] }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Last Year</div>
          <div class="text-2xl font-bold text-gray-900">{{ $yearRange['max_year'] }}</div>
        </div>
        @endif
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Year
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Number of Records
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Percentage
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @php $totalSum = collect($data)->sum(); @endphp
            @foreach(collect($data)->sortKeysDesc() as $year => $count)
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $year }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ number_format($count, 0, '.', ' ') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ $totalSum > 0 ? number_format(($count / $totalSum) * 100, 1) : 0 }}%
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
      <a href="{{ route('arbg.bacteria.statistics.index') }}" class="text-sky-600 hover:text-sky-800 underline text-sm">
        &larr; Back to statistics overview
      </a>
    </div>
  @endif
@endsection
