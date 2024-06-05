<x-app-layout>
  
  <div class="py-12">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div>
            @if(isset($request))
            {{var_dump($request)}}
            @endif
          </div>
          <!-- Main Search form -->
          <div>
            
            <div class="text-gray-600">
              <span>Number of matched records: </span><span class="font-bold"></span> of <span>{{$substancesCount}}</span>.
            </div>
            <table class="table-auto w-full border-separate border-spacing-1 text-xs">
              <thead>
                <tr class="bg-gray-200">
                  <th>Code</th>
                  <th>Name</th>
                  <th>CAS</th>
                  <th>Smilies</th>
                  <th>StdInchiKey</th>
                  <th>Categories</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($substances as $s)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  {{-- <td class="p-1"> {{$s->code}} </td>
                  <td class="p-1"> {{$s->name}} </td>
                  <td class="p-1"> {{$s->cas_number}} </td>
                  <td class="p-1"> {{$s->smiles}} </td>
                  <td class="p-1"> {{$s->stdinchikey}} </td> --}}
                  <td class="p-1"> {{$s->category_ids}} </td>
                  {{-- <td class="p-1"> --}}
                    {{-- @foreach ($s->categories as $cat)
                      {{$cat->name}}
                    @endforeach --}}
                  {{-- </td> --}}
                </tr>
                @endforeach
              </tbody>
            </table>
            {{-- {{$substances->links('pagination::tailwind')}} --}}
            {{-- {{$substances->links()}} --}}
            
            
          </div>
          <!-- Main Search form -->
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
