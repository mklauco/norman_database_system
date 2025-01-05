<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  <div class="px-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="pb-4 sm:p-8 bg-white shadow sm:rounded-lg">


        @role('super_admin|admin')
        <a href="{{ route('dctitems.create') }}" class="link-lime">Create new template</a>
        @endrole

        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th class="py-2 px-2">Template</th>
              <th class="py-2 px-2">Download Latest Template</th>
              @role('admin')
              <th>Actions</th>
              @endrole
            </tr>
          </thead>
          <tbody>
            @foreach ($dctitems as $item)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="py-2 px-2">{{$item->name}}</td>
              <td class="py-2 px-2">
                {{-- {{ dd($item->files()) }} --}}
                @foreach($item->files()->limit(1)->get() as $file)
                <a class="btn-link-lime" href="{{route('dctitems.donwload_template', $file->id)}}"><i class="fas fa-download"></i><span class="pl-2">{{$file->filename}}</span></a>
                @endforeach
              </td>
              @role('admin')
              <td class="py-2 px-2 text-center">
                <a class="link-edit" href="{{route('dctitems.edit', $item->id)}}">Edit</a>
                <a class="link-edit-rose" href="{{route('dctitems.upload_new_template', $item->id)}}">Upload New Template</a>
                <a class="link-indigo" href="{{route('dctitems.index_files', $item->id)}}">View previous templates</a>
              </td>
              @endrole
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
</x-app-layout>