<div>
  <div  wire:init="init">
    @if ($response)
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
    <span class="text-slate-600 pl-5">Loading data from Comptox...</span>
    @endif
  </div>
</div>
