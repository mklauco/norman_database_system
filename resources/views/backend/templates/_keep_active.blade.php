@if($activeVersions->isNotEmpty())
  @php($activeLabel = Str::plural('version', $activeVersions->count()).' '.$activeVersions->pluck('version')->map(fn ($version) => $version ?? 'N/A')->implode(', '))
  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex items-start gap-3 text-sm">
    <input type="checkbox" name="keep_active" id="keep_active" value="1" @checked(old('keep_active')) class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 text-slate-600 focus:ring-slate-500">
    <div>
      <label for="keep_active" class="font-medium text-gray-700">Keep {{ $activeLabel }} active too</label>
      <p class="text-gray-500">When not ticked, {{ $activeLabel }} will be archived. When ticked, the public DCT page offers all of them.</p>
    </div>
  </div>
@endif
