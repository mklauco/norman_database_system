<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('dctitems.store_new_template', $dctitem->id)}}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST')
            
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2">Item</th>
                  <th class="py-1 px-2">Value</th>
                </tr>
              </thead>
              <tr class="bg-slate-100">
                <td class="py-1 px-2">Template</td>
                <td class="py-1 px-2">
                  {{$dctitem->name}}
                </td>
              </tr>
              <tr class="bg-slate-100">
                <td class="py-1 px-2">Description</td>
                <td class="py-1 px-2">
                  {{$dctitem->description}}
                </td>
              </tr>
              <tr class="bg-slate-200">
                <td class="py-1 px-2">File</td>
                <td class="py-1 px-2">
                <input type="file" name="file" class="form-text">
                </td>
              </tr>
            </table>
            
            
            <div class="flex justify-end m-2">
              <button type="submit" class="btn-submit"> Upload file
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>