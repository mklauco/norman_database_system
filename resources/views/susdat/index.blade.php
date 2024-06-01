<x-app-layout>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div>
            @if(isset($request))
            {{var_dump($request)}}
            @endif
          </div>
          <!-- Main Search form -->
          <div>
            
            
            <table class="table-auto w-full border-separate border-spacing-1">
              <thead>
                <tr class="bg-gray-200">
                  <th>Id</th>
                  <th>Name</th>
                  <th>Category</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($substances as $s)
                <tr>
                  <td class="bg-slate-100 p-1"> {{$s->code}} </td>
                  <td class="bg-slate-100 p-1"> {{$s->name}} </td>
                  <td class="bg-slate-100 p-1"> {{var_dump($s->categories->count())}} </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            {{-- {{$substances->links('pagination::tailwind')}} --}}
            
            
          </div>
          <!-- Main Search form -->
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
