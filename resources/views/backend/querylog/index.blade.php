<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="overflow-x-auto">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2">ID</th>
                  <th class="py-1 px-2">Content</th>
                  <th class="py-1 px-2">Query</th>
                  <th class="py-1 px-2">User</th>
                  <th class="py-1 px-2">Created at</th>
                  <th class="py-1 px-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($queries as $q)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="py-1 px-2">
                    {{ $q->id }}
                  </td>
                  <td class="py-1 px-2 text-xs">
                    <pre>{{ json_encode(json_decode($q->content), JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}
                    </pre>
                  </td>
                  <td class="py-1 px-2 font-mono  text-xs">
                    {!!  $q->formatted_query !!}
                  </td>
                  <td class="py-1 px-2">
                    @if(is_null($q->user_id))
                    Guest
                    @else
                    {{ $q->users->last_name }}
                    @endif
                  </td>
                  <td class="py-1 px-2">
                    {{ $q->created_at }}
                  </td>
                  <td class="py-1 px-2">
                    <a href="{{ route('projects.edit', $q->id) }}" class="text-blue-500 hover:underline">Edit</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            {{$queries->links('pagination::tailwind')}}
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="hidden">
    <span class="text-purple-600"></span>
    <span class="text-teal-600"></span>
    <span class="text-orange-800"></span>
  </div>
</x-app-layout>