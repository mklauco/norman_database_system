<div>
  <div wire:init="init">
    @if ($error)
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-sm" role="alert">
        {{ $error }}
      </div>
    @elseif ($isLoading)
      <span class="text-slate-600">Loading data from Comptox...</span>
    @elseif ($response)
      <div class="overflow-x-auto">
        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              @foreach ($columns as $c)
                <th class="py-1 px-2">{{ $c }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($response as $r)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                @if (sizeof($r) > 1)
                  @foreach ($columns as $c)
                    <td class="p-1 break-words {{ in_array($c, ['cas_number', 'stdinchikey', 'dtxid', 'pubchem_cid'], true) ? 'font-mono' : '' }}">
                      @if (\App\Services\Susdat\SusdatDisplay::isEmpty($r[$c] ?? null))
                        <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                      @elseif ($c === 'mass_iso')
                        {{ \App\Services\Susdat\SusdatDisplay::number($r[$c]) }}
                      @else
                        {{ $r[$c] }}
                      @endif
                    </td>
                  @endforeach
                @else
                  <td class="p-1" colspan="{{ count($columns) }}">{{ \App\Services\Susdat\SusdatDisplay::EMPTY_LIST }}</td>
                @endif
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY_LIST }}</span>
    @endif
  </div>
</div>
