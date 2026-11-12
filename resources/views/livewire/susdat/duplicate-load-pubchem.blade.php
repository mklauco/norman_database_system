<div>
  <div wire:init="init">
    @if ($response)
      <div class="overflow-x-auto">
        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              @foreach ($columns as $pubchemField => $normanField)
                <th class="py-1 px-2">{{ $normanField }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($response as $r)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                @foreach ($columns as $pubchemField => $normanField)
                  <td class="p-1 break-words {{ in_array($normanField, ['stdinchikey', 'pubchem_cid'], true) ? 'font-mono' : '' }}">
                    @if (\App\Services\Susdat\SusdatDisplay::isEmpty($r[$normanField] ?? null))
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @elseif ($normanField === 'mass_iso')
                      {{ \App\Services\Susdat\SusdatDisplay::number($r[$normanField]) }}
                    @else
                      {{ $r[$normanField] }}
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <span class="text-slate-600">Loading data from Pubchem...</span>
    @endif
  </div>
</div>
