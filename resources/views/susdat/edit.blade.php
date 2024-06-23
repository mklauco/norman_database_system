<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('substances.update', $substance->id)}}" method="POST">
            @csrf
            @method('PUT')
            
            <table class="table-auto w-full border-separate border-spacing-1 text-xs">
              @foreach ($editables as $value)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                <td class="p-1 font-bold">{{$value}}</td>
                <td>
                  <input type="text" name="{{$value}}" value="{{$substance->$value}}" class="form-text">
                  {{-- <input type="text" name="{{$value}}" value="{{$substance->$value}}" class="w-full px-4 py-2 border border-lime-500 rounded-md bg-lime-100 text-lime-900 focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent transition duration-200 ease-in-out"> --}}
                  
                </td>              
              </tr>
              @endforeach
            </table>
            
            <div class="flex justify-end m-2">
              <button type="submit" class="btn-submit"> Update Substance 
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>