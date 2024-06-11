<x-app-layout>
  
  <div class="py-12">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          
          @if(isset($request))
          <div class="bg-gray-100 p-2">
            <form action="{{route('substances.search')}}" method="GET"> 
              <div class="flex space-x-2 items-center">
                <div>
                  <span class="text-sm font-bold">Search Category:</span>
                </div>
                <div id="select2">
                  @include('_t.form-apline-multiselect', ['tag' => 'category', 'list' => $categories, 'active_ids' => $active_ids, 'label' => 'Category', 'space' => 'request'])
                </div>

                <div>
                  <span class="text-sm font-bold">Order by:</span>
                </div>
                <div>
                  @include('_t.form-select', ['tag' => 'order_by_column', 'list' => $columns, 'label' => 'Category', 'space' => 'filter'])
                </div>
                <div>
                  @include('_t.form-select', ['tag' => 'order_by_direction', 'list' => $orderByDirection, 'label' => 'Category', 'space' => 'filter'])
                </div>
                
                <div id="submit_button">
                  <button type="submit" class="btn-submit"> Apply Filter 
                  </button>
                </div>
                <div id="submit_button">
                  <a href="{{route('substances.filter')}}" class="btn-submit"> Cancel Search
                  </a>
                </div>
              </div>
            </form>
          </div>
          @endif

          <div>
            
            <div class="text-gray-600">
              <span>Number of matched records: </span><span class="font-bold">{{$substances->total()}}</span> of <span>{{$substancesCount}}</span>.
            </div>
            <table class="table-auto w-full border-separate border-spacing-1 text-xs">
              <thead>
                <tr class="bg-gray-600 text-white">
                  @foreach ($columns as $c)
                  <th class="py-1 px-2">{{$c}}</th>
                  @endforeach
                  <th>categories</th>
                  <th>sources</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($substances as $substance)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 text-center"><a class="link-lime" href="{{route('substances.show', $substance->id)}}">{{$substance->id}}</a></td>
                  <td class="p-1 text-center">NS{{$substance->code}}</td>
                  <td class="p-1">{{$substance->name}}</td>
                  <td class="p-1">{{$substance->cas_number}}</td>
                  <td class="p-1">{{$substance->smiles}}</td>
                  <td class="p-1">{{$substance->stdinchikey}}</td>
                  <td class="p-1 text-center"><a class="link-lime" href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search={{$substance->dtxid}}" target="_blank">{{$substance->dtxid}}</a></td>
                  <td class="p-1 text-center"><a class="link-lime" href="https://pubchem.ncbi.nlm.nih.gov/compound/{{$substance->pubchem_cid}}" target="_blank">{{$substance->pubchem_cid}}</a></td>
                  <td class="p-1 text-center"><a class="link-lime" href="http://www.chemspider.com/Chemical-Structure.{{$substance->chemspider_id}}.html" target="_blank">{{$substance->chemspider_id}}</a></td>
                  <td class="p-1 text-center">{{$substance->molecular_formula}}</td>
                  <td class="p-1 text-right">{{$substance->mass_iso}}</td>
                  <td class="p-1 text-right">
                    @php
                    $categoryList = explode('|', $substance->category_ids);
                    @endphp
                    @foreach ($categoryList  as $category)
                    {{$categories[(int)$category]['abbreviation']}}@if(!$loop->last), @endif
                    @endforeach
                  </td>
                  <td class="p-1 text-right">
                    @php
                    $sourceList = explode('|', $sourceIds[$substance->id]['source_ids'] ); 
                    @endphp
                    @foreach ($sourceList  as $source)
                    {{$sources[(int)$source]->code}}@if(!$loop->last), @endif
                    @endforeach
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            {{$substances->links('pagination::tailwind')}}
          </div>
          <!-- Main Search form -->
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
{{-- href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search=DTXSID0032601" --}}