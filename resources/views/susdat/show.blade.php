<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <table class="table-auto w-full border-separate border-spacing-1 text-xs">
            @foreach ($substance as $key => $value)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
              <td class="p-1 font-bold">{{$key}}</td>
              @if (substr($key, 0, 8) == 'metadata')
              @php
              // Convert JSON to pretty-printed format and escape HTML characters
              $decodedJson = json_decode($value, true);
              $prettyJson = json_encode($decodedJson, JSON_PRETTY_PRINT);
              $escapedJson = htmlspecialchars($prettyJson, ENT_QUOTES, 'UTF-8');
              @endphp
              <td class="p-1">
                @foreach ($decodedJson as $keyInner => $valueInner)
                  <span class="block py-1"><span class="font-bold">{{$keyInner}}:</span> {{$valueInner}}</span>
                @endforeach
              </td>
              @else
              <td>{{$value}}</td>
              @endif
              
            </tr>
            @endforeach
          </table>
          
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>