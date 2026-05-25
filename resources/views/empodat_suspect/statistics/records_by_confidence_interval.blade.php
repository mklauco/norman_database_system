@extends('empodat_suspect.statistics.layout')

@section('page-title', 'Records by Confidence Interval')
@section('page-subtitle', 'Number of records per IP_max confidence level')

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
          <div class="text-sm text-gray-600">Records with IP_max</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalWithIpMax, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Records without IP_max</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalWithoutIpMax, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Total Records</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalWithIpMax + $totalWithoutIpMax, 0, '.', ' ') }}</div>
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
                Confidence Level
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                IP_max Range
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Number of Records
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Percentage
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @php
              $total = $totalWithIpMax + $totalWithoutIpMax;
            @endphp
            @foreach($data as $label => $info)
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  @if($info['level'] === 'null')
                    <span class="text-gray-500">-</span>
                  @else
                    Level {{ $info['level'] }}
                  @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ $label }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right font-mono">
                  {{ number_format($info['count'], 0, '.', ' ') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right font-mono">
                  {{ $total > 0 ? number_format(($info['count'] / $total) * 100, 2) : 0 }}%
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="bg-gray-100">
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900" colspan="2">
                Total
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right font-mono">
                {{ number_format($total, 0, '.', ' ') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right font-mono">
                100%
              </td>
            </tr>
          </tfoot>
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
