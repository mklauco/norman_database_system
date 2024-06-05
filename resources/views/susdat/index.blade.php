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
                <tr class="bg-gray-600 text-white">
                  @foreach ($columns as $c)
                  <th>{{$c}}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach ($substances as $substance)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1">{{$substance->id}}</td>
                  <td class="p-1">{{$substance->code}}</td>
                  <td class="p-1">{{$substance->name}}</td>
                  <td class="p-1">{{$substance->cas_number}}</td>
                  <td class="p-1">{{$substance->smiles}}</td>
                  <td class="p-1">{{$substance->stdinchikey}}</td>
                  <td class="p-1"><a class="text-blue-500 hover:text-white hover:bg-blue-500 border-b-2 border-transparent hover:border-blue-500 px-2 py-1 rounded transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500" href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search={{$substance->dtxid}}" target="_blank">{{$substance->dtxid}}</a></td>
                  <td class="p-1"><a class="text-stone-500 hover:text-white hover:bg-stone-500 border-b-2 border-transparent hover:border-stone-500 px-2 py-1 rounded transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-stone-500" href="https://pubchem.ncbi.nlm.nih.gov/compound/{{$substance->pubchem_cid}}" target="_blank">{{$substance->pubchem_cid}}</a></td>
                  <td class="p-1"><a class="text-lime-600 hover:text-white hover:bg-lime-500 border-b-2 border-transparent hover:border-lime-500 px-2 py-1 rounded transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-lime-500" href="http://www.chemspider.com/Chemical-Structure.{{$substance->chemspider_id}}.html" target="_blank">{{$substance->chemspider_id}}</a></td>
                  <td class="p-1">{{$substance->molecular_formula}}</td>
                  <td class="p-1">{{$substance->mass_iso}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
            {{$substances->links('pagination::tailwind')}}
            {{-- {{$substances->links()}} --}}
            
            
          </div>
          <!-- Main Search form -->
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search=DTXSID0032601"