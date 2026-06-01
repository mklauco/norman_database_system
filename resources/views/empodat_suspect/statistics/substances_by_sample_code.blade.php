@extends('empodat_suspect.statistics.layout')

@section('page-title', 'Substances by Sample Code')
@section('page-subtitle', 'Number of distinct substances detected per sample code')

@section('main-content')
  @if(isset($generatedAt))
    <div class="mb-4 text-sm text-gray-600">
      Data generated: {{ \Carbon\Carbon::parse($generatedAt)->setTimezone('Europe/Prague')->format('Y-m-d H:i:s') }}
    </div>
  @endif

  @if(isset($message))
    <!-- No Data Message -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="text-yellow-800">{{ $message }}</div>
      <a href="{{ route('empodat_suspect.statistics.index') }}" class="text-blue-600 hover:text-blue-800 underline text-sm">
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-600">Total Sample Codes</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalSampleCodes, 0, '.', ' ') }}</div>
        </div>
        @php
          // Data shape per sample_code is ['total' => int, 'numeric' => int, 'non_numeric' => int].
          // Sum the 'total' (or the bare int for legacy stats predating the partitioning split).
          $totalSubstances = collect($data)->sum(fn ($v) => is_array($v) ? ($v['total'] ?? 0) : $v);
        @endphp
        <div>
          <div class="text-sm text-gray-600">Total Substances (sum)</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalSubstances, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Average Substances per Code</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalSubstances / max($totalSampleCodes, 1), 1, '.', ' ') }}</div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sample Code
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Number of Substances
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @foreach(collect($data)->sortByDesc(fn ($v) => is_array($v) ? ($v['total'] ?? 0) : $v) as $sampleCode => $info)
              @php
                $total       = is_array($info) ? ($info['total'] ?? 0)       : $info;
                $numeric     = is_array($info) ? ($info['numeric'] ?? 0)     : 0;
                $nonNumeric  = is_array($info) ? ($info['non_numeric'] ?? 0) : 0;
              @endphp
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $sampleCode }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ number_format($total, 0, '.', ' ') }}
                  @if(is_array($info))
                    <span class="text-xs text-gray-400 ml-2">
                      ({{ number_format($numeric, 0, '.', ' ') }} numeric / {{ number_format($nonNumeric, 0, '.', ' ') }} N/A)
                    </span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
      <a href="{{ route('empodat_suspect.statistics.index') }}" class="text-blue-600 hover:text-blue-800 underline text-sm">
        ← Back to statistics overview
      </a>
    </div>
  @endif
@endsection
