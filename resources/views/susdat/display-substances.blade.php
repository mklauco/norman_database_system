<div class="text-gray-600">
  <span>Number of matched records: </span><span class="font-bold">{{$substances->total()}}</span> of <span>{{$substancesCount}}</span>.
</div>

{{-- Top scrollbar container --}}
<div class="overflow-x-auto mb-0" id="topScrollContainer" style="overflow-y: hidden; height: 16px;">
  <div id="topScrollContent" style="height: 1px;"></div>
</div>

<div class="overflow-x-auto" id="tableScrollContainer">
  <table class="table-standard w-full table-fixed">
    <thead>
      <tr class="bg-gray-600 text-white">
        @foreach ($columns as $c)
        <th class="py-1 px-2 text-left
          @if($c == '') w-12
          @elseif($c == 'NORMAN SusDat ID') w-28
          @elseif($c == 'Name') w-40
          @elseif($c == 'CAS_RN') w-20
          @elseif($c == 'StdInChIKey') w-44
          @elseif($c == 'DTXSID') w-28
          @elseif($c == 'PubChem_CID') w-24
          @elseif($c == 'Molecular Formula') w-28
          @elseif($c == 'SMILES') w-48
          @elseif($c == 'Monoisotopic Mass') w-28
          @endif
        ">{{$c}}</th>
        @endforeach
        @if(($show['duplicates'] ?? false) == true)
        <th class="w-40">Duplicate management</th>
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

        {{-- Icons column --}}
        <td class="p-1 text-center w-12">
          <div class="flex justify-center items-center space-x-2">
            <a class="text-teal-600 hover:text-teal-800" href="{{route('substances.show', $substance->id)}}" title="Show">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </a>
            @role('super_admin|admin|susdat')
            <a class="text-slate-600 hover:text-slate-800" href="{{route('substances.edit', $substance->id)}}" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </a>
            @endrole
          </div>
        </td>
        {{-- NORMAN SusDat ID --}}
        <td class="p-1 text-center w-28 @if(is_null($substance->deleted_at) == false)) line-through @endif">
          <span class="text-sm text-teal-800 font-mono whitespace-nowrap">NS{{$substance->code}}</span>
        </td>
        {{-- Name --}}
        <td class="p-1 w-40 wrap-break-word">{{$substance->name}}</td>
        {{-- CAS_RN --}}
        <td class="p-1 font-mono w-20 whitespace-nowrap">{{$substance->cas_number}}</td>
        {{-- StdInChIKey --}}
        <td class="p-1 font-mono w-44 break-all text-xs">{{$substance->stdinchikey}}</td>
        {{-- DTXSID --}}
        <td class="p-1 text-center w-28 break-all text-xs"><a class="btn-link-lime" href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search={{$substance->dtxid}}" target="_blank">{{$substance->dtxid}}</a></td>
        {{-- PubChem_CID --}}
        <td class="p-1 text-center font-mono w-24"><a class="btn-link-lime" href="https://pubchem.ncbi.nlm.nih.gov/compound/{{$substance->pubchem_cid}}" target="_blank">{{$substance->pubchem_cid}}</a></td>
        {{-- Molecular Formula --}}
        <td class="p-1 text-center w-28 wrap-break-word text-xs">{{$substance->molecular_formula}}</td>
        {{-- SMILES --}}
        <td class="p-1 font-mono text-xs break-all w-48">{{$substance->smiles}}</td>
        {{-- Monoisotopic Mass --}}
        <td class="p-1 text-right font-mono w-28 whitespace-nowrap">{{ $substance->mass_iso ? number_format((float)$substance->mass_iso, 4, '.', ' ') : '' }}</td>

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
</div>
{{$substances->links('pagination::tailwind')}}

<script>
document.addEventListener('DOMContentLoaded', function() {
  const topScroll = document.getElementById('topScrollContainer');
  const topContent = document.getElementById('topScrollContent');
  const tableScroll = document.getElementById('tableScrollContainer');

  if (topScroll && topContent && tableScroll) {
    const table = tableScroll.querySelector('table');
    if (table) {
      // Set the top scroll content width to match table width
      topContent.style.width = table.scrollWidth + 'px';

      // Sync scrolling between top and bottom
      let isSyncingTop = false;
      let isSyncingTable = false;

      topScroll.addEventListener('scroll', function() {
        if (!isSyncingTop) {
          isSyncingTable = true;
          tableScroll.scrollLeft = topScroll.scrollLeft;
          isSyncingTable = false;
        }
      });

      tableScroll.addEventListener('scroll', function() {
        if (!isSyncingTable) {
          isSyncingTop = true;
          topScroll.scrollLeft = tableScroll.scrollLeft;
          isSyncingTop = false;
        }
      });

      // Update top scroll width on window resize
      window.addEventListener('resize', function() {
        topContent.style.width = table.scrollWidth + 'px';
      });
    }
  }
});
</script>
