@php
  $backupStatus ??= null;
  $roles = collect($serverStats ? \App\Services\ServerStatsService::VIEWER_ROLES : [])
      ->merge($backupStatus ? \App\Services\BackupStatusService::VIEWER_ROLES : [])
      ->unique()
      ->values()
      ->all();
@endphp

<div class="bg-white overflow-hidden shadow-xs rounded-lg">
  <div class="p-4 bg-zinc-800 border-b border-zinc-700">
    <h3 class="flex items-center gap-2 text-base font-semibold text-white">
      <i class="fas fa-chart-bar text-zinc-300"></i>
      Server Statistics
      <x-role-lock :roles="$roles" color="text-zinc-300" />
    </h3>
    <p class="text-xs text-zinc-300 mt-1">Resource usage</p>
  </div>
  <div class="p-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $serverStats && $backupStatus ? 'lg:grid-cols-3' : '' }} gap-4">
      @if ($serverStats)
        <div class="border border-gray-200 rounded-lg p-3">
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
          $barClasses = ['ok' => 'bg-lime-500', 'failed' => 'bg-red-500', 'other' => 'bg-gray-400'];
          $emptySlots = max(0, \App\Services\BackupStatusService::BAR_COUNT - count($backupStatus['bars']));
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
            {{-- One slot per run, filled from the right like Uptime Kuma; the tooltip is position: fixed so the card cannot clip it. --}}
            <div
              x-data="{
                tip: '',
                open: false,
                placed: false,
                top: 0,
                left: 0,
                show(bar) {
                  this.tip = bar.dataset.tip;
                  this.open = true;
                  this.placed = false;
                  this.$nextTick(() => {
                    const anchor = bar.getBoundingClientRect();
                    const tip = this.$refs.tip.getBoundingClientRect();
                    this.left = Math.max(8, Math.min(anchor.left + anchor.width / 2 - tip.width / 2, window.innerWidth - tip.width - 8));
                    this.top = anchor.top - tip.height - 6;
                    this.placed = true;
                  });
                },
                hide() {
                  this.open = false;
                },
              }"
              @scroll.window="hide()"
              class="flex items-center gap-[3px] mt-3"
              role="list"
              aria-label="Last {{ \App\Services\BackupStatusService::BAR_COUNT }} backups, oldest first"
            >
              @for ($slot = 0; $slot < $emptySlots; $slot++)
                <span role="listitem" aria-label="No backup recorded" class="h-7 flex-1 max-w-[10px] rounded-full bg-gray-200"></span>
              @endfor
              @foreach ($backupStatus['bars'] as $bar)
                <span
                  role="listitem"
                  tabindex="0"
                  data-tip="{{ $bar['tooltip'] }}"
                  aria-label="{{ $bar['tooltip'] }}"
                  @mouseenter="show($el)"
                  @mouseleave="hide()"
                  @focus="show($el)"
                  @blur="hide()"
                  class="h-7 flex-1 max-w-[10px] rounded-full cursor-help transition-transform hover:scale-y-110 focus:outline-hidden focus:ring-2 focus:ring-lime-400 {{ $barClasses[$bar['state']] }}"
                ></span>
              @endforeach
              <span
                x-ref="tip"
                x-show="open"
                x-cloak
                x-text="tip"
                role="tooltip"
                :style="`top: ${top}px; left: ${left}px; visibility: ${placed ? 'visible' : 'hidden'}`"
                class="fixed z-50 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg pointer-events-none"
              ></span>
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
