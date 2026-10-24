<div>
  <div wire:init="init">
    @if ($error)
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-sm relative" role="alert">
        <span class="block sm:inline">{{ $error }}</span>
      </div>
    @elseif ($isLoading)
      <span class="text-slate-600 pl-5">Loading data from Comptox...</span>
    @elseif ($response)
      <table class="table-standard">
        <thead>
          <tr class="bg-gray-600 text-white">
            @foreach ($columns as $c)
            <th class="py-1 px-2">{{$c}}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach ($response as $r)
          <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
            @if(sizeof($r) > 1)
            @foreach ($columns as $c)
            <td class="p-1">
              @if(is_null($r[$c]))
              <span class="text-slate-600 pl-5">No data available</span>
              @else
              {{$r[$c]}}
              @endif
            </td>
            @endforeach
            @else
            <td colspan="8">No data available at the Comptox database</td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <span class="text-slate-600 pl-5">No data available</span>
    @endif
  </div>
</div>
