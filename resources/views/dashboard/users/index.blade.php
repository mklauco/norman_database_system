<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                @foreach ($columns as $c)
                <th class="py-1 px-2">{{$c}}</th>
                @endforeach
                <th class="py-1 px-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $user)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="py-1 px-2">{{$user->id}}</td>
                <td class="py-1 px-2">{{$user->first_name}}</td>
                <td class="py-1 px-2">{{$user->last_name}}</td>
                <td class="py-1 px-2">{{$user->email}}</td>
                <td class="py-1 px-2">{{$user->getRoleNames()}}</td>
                <td class="py-1 px-2">{{$usersWithTokens[$user->id]}}</td>
                <td class="py-1 px-2">
                  @foreach ($user->projects as $project)
                  {{ $project->abbreviation }}@if (!$loop->last), @endif
                  @endforeach
                </td>
                <td class="py-1 px-2">
                  <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
