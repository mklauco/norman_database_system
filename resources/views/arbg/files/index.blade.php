<x-app-layout>
  <x-slot name="header">
    @include('arbg.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-8">

          <div>
            <h1 class="text-lg font-bold text-lime-700">List of Uploaded Files</h1>
            <p class="mt-1 text-sm text-gray-600">
              The data files submitted to this module, and the records each one
              covers. To contribute data, use the blank templates on the
              <a class="link-lime-text" href="{{ route('templates.specific.index', ['code' => 'arbg']) }}">DCT Download</a> page.
            </p>
          </div>

          @foreach ([['ARB', $bacteriaFiles], ['ARG', $geneFiles]] as [$label, $files])
            <div>
              <h2 class="mb-2 font-bold">{{ $label }}</h2>

              @if ($files->isEmpty())
                <p class="text-sm italic text-gray-400">No files have been uploaded yet.</p>
              @else
                <div class="overflow-x-auto">
                  <table class="table-standard">
                    <thead>
                      <tr class="bg-gray-600 text-white">
                        <th class="p-1">#</th>
                        <th class="p-1 text-left">File Name</th>
                        <th class="p-1">Upload Date</th>
                        <th class="p-1">ID From</th>
                        <th class="p-1">ID To</th>
                        <th class="p-1">Count</th>
                        <th class="p-1">Matrix</th>
                        <th class="p-1 text-left">Note</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($files as $file)
                        <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                          <td class="p-1 text-center font-mono">{{ $file['number'] }}</td>
                          <td class="p-1">
                            {{ $file['name'] }}
                            @if ($file['superseded'])
                              <span class="ml-1 text-gray-500">(superseded)</span>
                            @endif
                          </td>
                          <td class="p-1 text-center whitespace-nowrap">{{ $file['uploaded_at'] }}</td>
                          <td class="p-1 text-right font-mono">{!! number_format($file['id_from'], 0, '.', '&nbsp;') !!}</td>
                          <td class="p-1 text-right font-mono">{!! number_format($file['id_to'], 0, '.', '&nbsp;') !!}</td>
                          <td class="p-1 text-right font-mono">{!! number_format($file['records'], 0, '.', '&nbsp;') !!}</td>
                          <td class="p-1">{{ $file['matrix'] }}</td>
                          <td class="p-1">{{ $file['note'] }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          @endforeach

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
