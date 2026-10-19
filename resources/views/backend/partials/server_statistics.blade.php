@php
  $backupStatus ??= null;
  $roles = collect($serverStats ? \App\Services\ServerStatsService::VIEWER_ROLES : [])
      ->merge($backupStatus ? \App\Services\BackupStatusService::VIEWER_ROLES : [])
      ->unique()
      ->values()
      ->all();
@endphp

<div class="bg-white overflow-hidden shadow-sm rounded-lg">
  <div class="p-4 bg-zinc-800 border-b border-zinc-700">
    <h3 class="flex items-center gap-2 text-base font-semibold text-white">
      <i class="fas fa-chart-bar text-zinc-300"></i>
      Server Statistics
      <x-role-lock :roles="$roles" color="text-zinc-300" />
    </h3>
    <p class="text-xs text-zinc-300 mt-1">Resource usage</p>
  </div>
  <div class="p-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      @if ($serverStats)
        <div class="border border-gray-200 rounded-lg p-3 {{ $backupStatus ? 'sm:row-span-2' : '' }}">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-600">Disk Space</span>
            <i class="fas fa-hdd text-gray-400 text-sm"></i>
          </div>
          @if ($serverStats['disk'])
            <div class="text-xl font-semibold text-gray-900 font-mono">{{ number_format($serverStats['disk']['used_percentage'], 1, '.', ' ') }} %</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
              <div class="h-1.5 rounded-full @if ($serverStats['disk']['used_percentage'] >= 90) bg-red-500 @elseif ($serverStats['disk']['used_percentage'] >= 75) bg-amber-500 @else bg-lime-500 @endif"
                   style="width: {{ min($serverStats['disk']['used_percentage'], 100) }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              {{ $serverStats['disk']['used_human'] }} of {{ $serverStats['disk']['total_human'] }} used
            </div>
            <div class="text-xs text-gray-500">{{ $serverStats['disk']['free_human'] }} free</div>
          @else
            <div class="text-xl font-semibold text-gray-900">--</div>
            <div class="text-xs text-gray-500 mt-1">Unavailable</div>
          @endif
        </div>

        <div class="border border-gray-200 rounded-lg p-3">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-600">Uptime</span>
            <i class="fas fa-clock text-gray-400 text-sm"></i>
          </div>
          @if ($serverStats['uptime'])
            <div class="text-xl font-semibold text-lime-600">{{ $serverStats['uptime']['host_human'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Host system</div>
            @if ($serverStats['uptime']['container_human'])
              <div class="text-xs text-gray-500 mt-1">Container: {{ $serverStats['uptime']['container_human'] }}</div>
            @endif
          @else
            <div class="text-xl font-semibold text-lime-600">--</div>
            <div class="text-xs text-gray-500 mt-1">Unavailable</div>
          @endif
        </div>
      @endif

      @if ($backupStatus)
        @php
          $latestBackup = $backupStatus['latest'];
          $barClasses = ['ok' => 'bg-lime-500', 'failed' => 'bg-red-500', 'other' => 'bg-gray-300'];
        @endphp
        <div class="border rounded-lg p-3 {{ $backupStatus['warning'] ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
          <div class="flex items-center justify-between mb-2">
            <span class="flex items-center gap-1 text-xs text-gray-600">
              Backups
              <x-role-lock :roles="\App\Services\BackupStatusService::VIEWER_ROLES" />
            </span>
            <i class="fas fa-database text-sm {{ $backupStatus['warning'] ? 'text-red-500' : 'text-gray-400' }}"></i>
          </div>
          @if ($latestBackup)
            <div class="text-xl font-semibold {{ $backupStatus['warning'] ? 'text-red-600' : 'text-lime-600' }}">
              {{ $latestBackup['label'] }}
            </div>
            <div class="text-xs mt-1 {{ $backupStatus['warning'] ? 'text-red-700' : 'text-gray-500' }}">
              Last backup: {{ $latestBackup['time']->format('Y-m-d H:i') }}{{ $latestBackup['size_gb'] !== null ? ', '.$latestBackup['size_gb'].' GB' : '' }}, {{ $latestBackup['label'] }}
            </div>
            @if ($backupStatus['stale'])
              <div class="text-xs font-semibold text-red-700">No report for over {{ \App\Services\BackupStatusService::STALE_AFTER_HOURS }} hours</div>
            @endif
            <div class="flex items-end gap-0.5 mt-2" role="list" aria-label="Last {{ count($backupStatus['bars']) }} backups, oldest first">
              @foreach ($backupStatus['bars'] as $bar)
                <span role="listitem" title="{{ $bar['tooltip'] }}" aria-label="{{ $bar['tooltip'] }}"
                      class="h-5 flex-1 max-w-[0.5rem] rounded-sm {{ $barClasses[$bar['state']] }}"></span>
              @endforeach
            </div>
          @else
            <div class="text-xl font-semibold text-gray-900">--</div>
            <div class="text-xs text-gray-500 mt-1">Backup status unavailable</div>
          @endif
        </div>
      @endif
    </div>
  </div>
</div>
