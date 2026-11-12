<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold text-gray-800">Create New Substance</h2>
            <a class="link-lime-text" href="{{ route('substances.index') }}">Back to the list of substances</a>
          </div>

          <form action="{{ route('substances.store') }}" method="POST">
            @csrf

            <div class="overflow-x-auto">
              <table class="table-auto w-full border-separate border-spacing-1 text-xs">
                @foreach ($editables as $value)
                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    @php
                      $label = config('substance_field_labels.'.$value, $value);
                      $isMetadata = substr($value, 0, 8) === 'metadata';
                    @endphp
                    @if ($value === 'code')
                      <td class="p-1 font-bold align-top">{{ $label }}</td>
                      <td class="p-1">
                        <span class="font-mono text-sm">NS{{ $substance->$value }}</span>
                        <input type="hidden" name="code" value="{{ $substance->$value }}">
                      </td>
                    @elseif ($isMetadata)
                      <td class="p-1 font-bold align-top">
                        <label for="{{ $value }}_new_key">{{ $label }}</label>
                      </td>
                      <td class="p-1">
                        <div class="flex flex-wrap gap-2 py-1">
                          <input type="text" id="{{ $value }}_new_key" name="{{ $value }}_new_key[]" placeholder="key"
                            class="form-text-small text-sm max-w-full">
                          <input type="text" id="{{ $value }}_new_value" name="{{ $value }}_new_value[]"
                            placeholder="value" aria-label="{{ $label }} value" class="form-text-small text-sm max-w-full">
                        </div>
                      </td>
                    @else
                      <td class="p-1 font-bold align-top">
                        <label for="{{ $value }}">{{ $label }}</label>
                      </td>
                      <td class="p-1">
                        <input type="text" id="{{ $value }}" name="{{ $value }}"
                          value="{{ old($value, $substance->$value) }}" class="form-text">
                      </td>
                    @endif
                  </tr>
                @endforeach
              </table>
            </div>

            <div class="flex justify-end pt-4">
              <button type="submit" class="btn-submit">Create Substance</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
