<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="overflow-x-auto">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2">Project Name</th>
                  <th class="py-1 px-2">Abbreviation</th>
                  <th class="py-1 px-2">Description</th>
                  <th class="py-1 px-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($projects as $project)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="py-1 px-2">{{ $project->name }}</td>
                  <td class="py-1 px-2">{{ $project->abbreviation }}</td>
                  <td class="py-1 px-2">{{ $project->description }}</td>
                  <td class="py-1 px-2">
                    <a href="{{ route('projects.edit', $project->id) }}" class="text-blue-500 hover:underline">Edit</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>