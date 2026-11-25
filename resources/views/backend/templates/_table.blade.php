{{-- $groups: versions of each template, the row to show first; the rest is collapsed unless $expanded. --}}
@php($expanded = $expanded ?? false)
@php($highlightId = $highlightId ?? null)
<div class="overflow-x-auto">
  <table class="table-standard w-full">
    <thead>
      <tr class="bg-gray-600 text-white">
        <th class="py-2 px-4 text-left">ID</th>
        <th class="py-2 px-4 text-left">Name</th>
        <th class="py-2 px-4 text-left">Version</th>
        <th class="py-2 px-4 text-left">Database Entity</th>
        <th class="py-2 px-4 text-left">File Size</th>
        <th class="py-2 px-4 text-left">Status</th>
        <th class="py-2 px-4 text-left">Valid from</th>
        <th class="py-2 px-4 text-left">Created By</th>
        <th class="py-2 px-4 text-left">Created At</th>
        <th class="py-2 px-4 text-center">Actions</th>
      </tr>
    </thead>
    @foreach ($groups as $versions)
      <tbody x-data="{ open: @js($expanded) }">
        @foreach ($versions as $template)
          <tr class="@if($template->id === $highlightId) bg-lime-100 @elseif(! $loop->first && ! $expanded) bg-white @elseif($loop->parent->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition" @if(! $loop->first && ! $expanded) x-show="open" x-cloak @endif>
            <td class="py-2 px-4 font-mono">{{ $template->id }}</td>
            <td class="py-2 px-4">
              <a href="{{ route('templates.show', $template) }}" class="link-lime-text font-medium">{{ $template->name ?? 'N/A' }}</a>
              @if($template->description)
                <div class="text-sm text-gray-600">{{ Str::limit($template->description, 50) }}</div>
              @endif
              @if($loop->first && ! $expanded && $versions->count() > 1)
                <div>
                  <button type="button" class="text-gray-600 hover:text-gray-900 underline cursor-pointer" x-on:click="open = !open">
                    <span x-text="open ? 'Hide' : 'Show'">Show</span> {{ $versions->count() - 1 }} other {{ Str::plural('version', $versions->count() - 1) }}
                  </button>
                </div>
              @endif
            </td>
            <td class="py-2 px-4">{{ $template->version ?? 'N/A' }}</td>
            <td class="py-2 px-4">{{ $template->databaseEntity->name ?? 'N/A' }}</td>
            <td class="py-2 px-4 whitespace-nowrap">{{ $template->formatted_filesize }}</td>
            <td class="py-2 px-4">
              @if($template->is_active)
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
              @else
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Archived</span>
              @endif
            </td>
            <td class="py-2 px-4 whitespace-nowrap">{{ $template->valid_from?->format('Y-m-d') ?? 'N/A' }}</td>
            <td class="py-2 px-4">{{ $template->creator->full_name ?? 'N/A' }}</td>
            <td class="py-2 px-4 whitespace-nowrap">{{ $template->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
            <td class="py-2 px-4 text-center">
              <div class="flex justify-center items-center gap-2 whitespace-nowrap">
                @if($template->file_path)
                  <a href="{{ route('templates.download', $template) }}" class="link-lime-text">Download</a>
                @endif
                <a href="{{ route('templates.edit', $template) }}" class="link-edit">Edit</a>
                @if($loop->first && ! $expanded)
                  <a href="{{ route('templates.versions.create', $template) }}" class="link-lime-text">New version</a>
                @endif
                @if($template->is_active)
                  <form action="{{ route('templates.archive', $template) }}" method="POST" class="inline" onsubmit="return confirm('Archive this version? It will no longer be offered on the public DCT page. Nothing is deleted and it can be restored.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="link-edit-rose cursor-pointer">Archive</button>
                  </form>
                @else
                  <form action="{{ route('templates.restore', $template) }}" method="POST" class="inline" onsubmit="return confirm('Restore this version? It becomes the active version of the template; the version active now will be archived.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="link-lime-text cursor-pointer">Restore</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    @endforeach
  </table>
</div>
