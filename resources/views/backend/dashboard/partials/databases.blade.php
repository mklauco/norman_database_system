<div class="bg-white shadow-sm rounded-lg overflow-hidden">
  <div class="p-4 border-b border-gray-200">
    <h3 class="text-lg font-semibold text-gray-800">
      <i class="fas fa-database mr-2 text-gray-600"></i>
      Databases
    </h3>
  </div>

  @if ($databaseEntities->isEmpty())
    <p class="p-4 text-sm text-gray-500">No databases available.</p>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-gray-200">
          <tr class="text-left font-medium text-gray-600">
            <th class="px-4 py-3">Database</th>
            <th class="px-4 py-3 text-right">Records</th>
            @if ($canViewSearches)
              <th class="px-4 py-3 text-right">
                <span class="inline-flex items-center gap-1">Searches <x-role-lock :roles="['super_admin', 'admin']" /></span>
              </th>
            @endif
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach ($databaseEntities as $entity)
            @php
              $isExternal = str_starts_with($entity->dashboard_route_name, 'https');
              $link = $isExternal ? $entity->dashboard_route_name : route($entity->dashboard_route_name);
            @endphp
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3">
                <a href="{{ $link }}" @if ($isExternal) target="_blank" @endif class="font-medium text-gray-800 hover:text-lime-600">
                  {{ $entity->name }}
                </a>
                @if (! $entity->is_public)
                  <i class="fas fa-lock ml-1 text-gray-400 text-xs"></i>
                @endif
              </td>
              <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($entity->number_of_records ?? 0, 0, '.', ' ') }}</td>
              @if ($canViewSearches)
                <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($entity->query_log_count ?? 0, 0, '.', ' ') }}</td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
