<div wire:init="init">
    {{-- Close your eyes. Count to one. That is how long forever feels. --}}
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
                @foreach ($columns as $c)
                <td class="p-1">
                    @if(is_null($r[$c]))
                    No data available
                    @else
                    {{$r[$c]}}
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    loading
    @endif
</div>
