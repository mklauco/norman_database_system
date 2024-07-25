<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  <div class="px-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="pb-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th class="py-1 px-2">Template</th>
              <th class="py-1 px-2">File</th>
              <th class="py-1 px-2">Filesize</th>
              <th class="py-1 px-2">Updated at</th>
              @role('admin')
              <th>Actions</th>
              @endrole
            </tr>
          </thead>
          <tbody>
            @foreach ($dctitems as $item)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="py-1 px-2">{{$item->name}}</td>
              <td class="py-1 px-2">{{$item->file_path}}</td>
              <td class="py-1 px-2"></td>
              <td class="py-1 px-2">{{$item->updated_at}}</td>
              @role('admin')
              <td class="py-1 px-2 text-center"> <a class="link-edit" href="{{route('dctitems.edit', $item->id)}}">Edit</a> </td>
              @endrole
            </tr>
            @endforeach
          </tbody>
        </table>
        
      </div>
    </div>
  </div>
  
</x-app-layout>