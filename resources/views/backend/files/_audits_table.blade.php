{{-- Audit entries of DCT files. Expects $audits; $showFile adds the File column. --}}
@php
  $formatAuditValue = function (mixed $value): string {
      if ($value === null || $value === '') {
          return '—';
      }
      if (is_bool($value)) {
          return $value ? 'Yes' : 'No';
      }
      if (is_array($value)) {
          return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }

      return (string) $value;
  };
@endphp
<div class="overflow-x-auto">
  <table class="table-standard w-full">
    <thead>
      <tr class="bg-gray-600 text-white">
        <th class="py-2 px-2 text-left whitespace-nowrap">Date (CET)</th>
        @if($showFile)
          <th class="py-2 px-2 text-left">File</th>
        @endif
        <th class="py-2 px-2 text-left">Event</th>
        <th class="py-2 px-2 text-left">User</th>
        <th class="py-2 px-2 text-left">Changes</th>
      </tr>
    </thead>
    <tbody>
      @foreach($audits as $audit)
        <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif align-top">
          <td class="py-2 px-2 font-mono whitespace-nowrap">{{ $audit->created_at->timezone('Europe/Bratislava')->format('Y-m-d H:i:s') }}</td>
          @if($showFile)
            <td class="py-2 px-2 font-mono">
              <a href="{{ route('files.show', $audit->auditable_id) }}" class="link-lime-text">{{ $audit->auditable_id }}</a>
            </td>
          @endif
          <td class="py-2 px-2">
            <span class="px-1 py-0.5 text-xs font-medium rounded-sm
              @if($audit->event === 'created') bg-green-100 text-green-800
              @elseif($audit->event === 'updated') bg-amber-100 text-amber-800
              @elseif($audit->event === 'deleted') bg-red-100 text-red-800
              @else bg-gray-100 text-gray-800 @endif">
              {{ ucfirst($audit->event) }}
            </span>
          </td>
          <td class="py-2 px-2 whitespace-nowrap" title="IP: {{ $audit->ip_address ?? '—' }}">
            @if($audit->user)
              {{ $audit->user->full_name }}
            @else
              <span class="text-gray-500 italic">System</span>
            @endif
          </td>
          <td class="py-2 px-2 text-sm">
            @if($audit->event === 'updated')
              <div class="flex flex-col gap-1">
                @foreach($audit->old_values as $field => $oldValue)
                  <div class="min-w-0 break-all">
                    <span class="font-medium">{{ $field }}:</span>
                    <span class="text-red-700 line-through">{{ $formatAuditValue($oldValue) }}</span>
                    <span aria-hidden="true">→</span>
                    <span class="text-green-700">{{ $formatAuditValue($audit->new_values[$field] ?? null) }}</span>
                    @if($field === 'file_path' && $oldValue)
                      <a href="{{ route('files.audits.previous', $audit->id) }}" class="link-lime-text ml-1">download previous file</a>
                    @endif
                  </div>
                @endforeach
              </div>
            @elseif($audit->event === 'created')
              Uploaded: {{ $formatAuditValue($audit->new_values['original_name'] ?? $audit->new_values['name'] ?? null) }}
            @elseif($audit->event === 'deleted')
              Record removed from the database
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
