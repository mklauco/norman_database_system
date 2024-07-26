<div class="text-gray-600">
  <span>Number of matched records: </span><span class="font-bold">{{$substances->total()}}</span> of <span>{{$substancesCount}}</span>.
</div>
<table class="table-standard">
  <thead>
    <tr class="bg-gray-600 text-white">
      @foreach ($columns as $c)
      <th class="py-1 px-2">{{$c}}</th>
      @endforeach
      @if(($show['substances'] ?? false) == true)
      <th>categories</th>
      @endif
      @if(($show['sources'] ?? false) == true)
      <th>sources</th>
      @endif
      @if(($show['duplicates'] ?? false) == true)
      <th>Duplicate management</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @foreach ($substances as $substance)
    @if(is_null($substance->deleted_at) == false)
    <tr class="bg-zinc-100 text-zinc-400">
    @else
    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
    @endif
    
      <td class="p-1 flex justify-end">
        <div class="flex justify-right">
          <a class="btn-link-lime" href="{{route('substances.show', $substance->id)}}">{{$substance->id}}</a>
          @auth
          <a class="link-edit" href="{{route('substances.edit', $substance->id)}}">Edit</a>
          @endauth
        </div>
        {{-- <a class="btn-link-lime" href="{{route('substances.show', $substance->id)}}">{{$substance->id}}</a> --}}
        {{-- <a class="link-edit" href="{{route('substances.edit', $substance->id)}}">Edit</a> --}}
      </td>
      <td class="p-1 text-center @if(is_null($substance->deleted_at) == false)) line-through @endif">NS{{$substance->code}}</td>
      <td class="p-1">{{$substance->name}}</td>
      <td class="p-1">{{$substance->cas_number}}</td>
      <td class="p-1">{{$substance->smiles}}</td>
      <td class="p-1">{{$substance->stdinchikey}}</td>
      <td class="p-1 text-center"><a class="btn-link-lime" href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search={{$substance->dtxid}}" target="_blank">{{$substance->dtxid}}</a></td>
      <td class="p-1 text-center"><a class="btn-link-lime" href="https://pubchem.ncbi.nlm.nih.gov/compound/{{$substance->pubchem_cid}}" target="_blank">{{$substance->pubchem_cid}}</a></td>
      <td class="p-1 text-center"><a class="btn-link-lime" href="http://www.chemspider.com/Chemical-Structure.{{$substance->chemspider_id}}.html" target="_blank">{{$substance->chemspider_id}}</a></td>
      <td class="p-1 text-center">{{$substance->molecular_formula}}</td>
      <td class="p-1 text-right">{{$substance->mass_iso}}</td>
      @if(($show['substances'] ?? false) == true)
      <td class="p-1 text-right">
        @if(is_null($substance->category_ids) == false)
        @php
        $categoryList = explode('|', $substance->category_ids);
        @endphp
        @foreach ($categoryList  as $category)
        {{$categories[(int)$category]->abbreviation}}@if(!$loop->last), @endif
        @endforeach
        @else
        <span class="text-red-500">No category assigned</span>
        @endif
      </td>
      @endif

      @if(($show['sources'] ?? false) == true)
      <td class="p-1 text-right">
        @if(isset($sourceIds) && array_key_exists($substance->id, $sourceIds) == true)
        @php
        $sourceList = explode('|', $sourceIds[$substance->id]['source_ids'] ); 
        @endphp
        @foreach ($sourceList  as $source)
        {{$sources[(int)$source]->code}}@if(!$loop->last), @endif
        @endforeach
        @else
        <span class="text-red-500">No source assigned</span>
        @endif
      </td>
      @endif

      @if(($show['duplicates'] ?? false) == true)
      <td>
        <div class="flex px-2">
          @if((is_null($substance->deleted_at) == false) == false)
          <label class="inline-flex items-center">
            <input type="radio" name="duplicateChoice[{{$substance->id}}]" value="1">
            <span class="ml-2">activate</span>
          </label>
          <label class="inline-flex items-center ml-6">
            <input type="radio" name="duplicateChoice[{{$substance->id}}]" value="0">
            <span class="ml-2">deactivate</span>
          </label>
          @else
          <label class="inline-flex items-center">
            <input type="radio" name="duplicateRestore[{{$substance->id}}]" value="1">
            <span class="ml-2 text-black">restore</span>
          @endif
        </div>
      </td>
      @endif

    </tr>
    @endforeach
  </tbody>
</table>
{{$substances->links('pagination::tailwind')}}
