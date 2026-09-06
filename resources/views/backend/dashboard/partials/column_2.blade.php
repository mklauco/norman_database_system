@role(['admin', 'super_admin'])

  <div class="p-4 bg-stone-100 border-b border-stone-200">
    <h3 class="text-lg font-semibold text-gray-800">
      <i class="fas fa-file-alt mr-2 text-gray-600"></i>
      Templates & Management
    </h3>
    <p class="text-xs text-gray-600 mt-1">Admin & Super Admin</p>
  </div>

  <div class="bg-white overflow-hidden shadow-md rounded-lg">
    <!-- Messages section -->
    <div class="p-4 bg-stone-100 border-b border-stone-200">
      <h4 class="font-medium text-gray-700 text-sm uppercase tracking-wide">System Messages</h4>
    </div>
    <div class="p-4 space-y-3 border-b border-stone-200">
      {{-- Check if NO templates exist in the entire system --}}
      @if ($statistics['total_templates'] == 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
          <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
            <span class="text-red-700 text-sm font-medium">No DCT Templates found in system</span>
          </div>
        </div>
      @else
        <div class="bg-stone-50 rounded-lg p-4 text-center">
          <i class="fas fa-envelope-open-text text-2xl text-gray-400 mb-2"></i>
          <p class="text-gray-600 text-sm">No new messages</p>
          <p class="text-gray-500 text-xs mt-1">Message system will be added here</p>
        </div>
      @endif
    </div>


    <div class="p-4">
      <!-- Template counts per database entity -->
      <div class="space-y-3 mb-6">
        <h4 class="font-medium text-gray-700 text-sm uppercase tracking-wide">Template Counts</h4>
        <div class="overflow-hidden bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-4 py-3 bg-stone-50 border-b border-gray-200">
            <div class="grid grid-cols-4 font-medium text-sm text-gray-600">
              <div class="col-span-2">Database</div>
              <div class="text-right">Active</div>
              <div class="text-right">Inactive</div>
            </div>
          </div>

          <div class="divide-y divide-gray-100">
            @foreach ($entitiesWithTemplateCounts as $entity)
              <div class="grid grid-cols-4 px-4 py-2 hover:bg-stone-50">
                <div class="col-span-2 text-gray-800 text-sm">{{ $entity->name }}</div>
                <div class="text-right font-mono text-gray-700 text-sm">
                  <span class="text-green-600">{{ number_format($entity->active_templates_count) }}</span>
                </div>
                <div class="text-right font-mono text-gray-700 text-sm">
                  <span class="text-gray-500">{{ number_format($entity->inactive_templates_count) }}</span>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <!-- System statistics -->
      <div class="space-y-3 mb-6">
        <h4 class="font-medium text-gray-700 text-sm uppercase tracking-wide">System Counts</h4>
        <div class="bg-stone-50 rounded-lg p-4 space-y-2">
          <div class="flex justify-between items-center py-1">
            <span class="text-gray-700 text-sm">Total Templates</span>
            <span class="font-semibold text-sm">{{ number_format($statistics['total_templates']) }}</span>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-gray-700 text-sm">Total Files</span>
            <span class="font-semibold text-sm">{{ number_format($statistics['total_files']) }}</span>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-gray-700 text-sm">Total Projects</span>
            <span class="font-semibold text-sm">{{ number_format($statistics['total_projects']) }}</span>
          </div>
        </div>
      </div>


    </div>
  </div>
@else
  <!-- API Tokens for non-admin users -->
  <div class="bg-white overflow-hidden shadow-md rounded-lg">
    <div class="p-4 bg-stone-100 border-b border-stone-200">
      <h3 class="text-lg font-semibold text-gray-800">
        <i class="fas fa-key mr-2 text-gray-600"></i>API Access
      </h3>
      <p class="text-xs text-gray-600 mt-1">Your API tokens</p>
    </div>
    <div class="p-4">
      @if ($user->tokens->count() == 0)
        <div class="text-center py-4">
          <i class="fas fa-exclamation-circle text-2xl text-red-500 mb-2"></i>
          <p class="text-gray-700">No API tokens created yet</p>
          <a href="{{ route('apiresources.index') }}" class="mt-2 inline-block btn-create">
            Create API Token
          </a>
        </div>
      @else
        <div class="text-center py-4">
          <i class="fas fa-check-circle text-2xl text-green-500 mb-2"></i>
          <p class="text-gray-700">You have {{ $user->tokens->count() }} active API token(s)</p>
          <a href="{{ route('apiresources.index') }}" class="mt-2 inline-block btn-submit">
            Manage Tokens
          </a>
        </div>
      @endif

      <!-- Data Collection Templates (DCT) for non-admin users -->
      @if ($entitiesWithTemplates->count() > 0)
        <div class="mt-6 pt-4 border-t border-gray-200">
          <h4 class="font-medium text-gray-700 text-sm uppercase tracking-wide mb-3">Data Collection Templates (DCT)</h4>
          <div class="grid grid-cols-1 gap-3">
            @foreach ($entitiesWithTemplates as $entity)
              @if($entity->is_public)
                <a href="{{ route('templates.specific.index', ['code' => $entity->code]) }}"
                  class="flex items-center p-3 border rounded-lg hover:bg-stone-50 transition">
                  <div class="bg-stone-100 p-2 rounded-full mr-3">
                    <i class="fas fa-file-alt text-gray-600"></i>
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-900 text-sm">{{ $entity->name }}</h4>
                    <p class="text-xs text-gray-600">Download templates</p>
                  </div>
                </a>
              @endif
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
@endrole
