<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  <div class="px-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="pb-4 sm:p-8 bg-white shadow sm:rounded-lg">

        <div class="py-1">
          Showing all previous templates for: <span  class="font-bold">{{ $dctitem->name }}</span>
        </div>

        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th class="py-2 px-2">Template</th>
              <th class="py-2 px-2">Uploaded at</th>
              @role('admin')
              <th>Actions</th>
              @endrole
            </tr>
          </thead>
          <tbody>
            @foreach ($files as $file)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="py-2 px-2">
                <a class="btn-link-lime" href="{{route('dctitems.donwload_template', $file->id)}}"><i class="fas fa-download"></i><span class="pl-2">{{$file->filename}}</span></a>
              </td>
              <td class="py-2 px-2">{{Carbon\Carbon::parse($file->updated_at)->timezone('Europe/Bratislava')->format('Y-d-m G:i:s')}}</td>
              @role('admin')
              <td class="py-2 px-2 text-center">
                <form action="{{ route('dctitems.delete_template', $file->id) }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                </form>
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