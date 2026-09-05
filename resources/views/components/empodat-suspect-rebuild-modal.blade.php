{{-- Confirmation for the per-file prioritisation rebuild on the Uploaded DCT
     Files page. Replaces a native window.confirm(), which could not show the
     file name, the current row count, or the cost of the run.

     ONE instance per page, not one per row. The trigger lives in every table
     row, so the two cannot share an Alpine scope the way
     <x-empodat-suspect-onboarding-modal /> does; a window event carries the
     row's details in instead. Rendering a modal per row would put the same
     markup on the page fifteen times over.

     The queued command is App\Console\Commands\RefreshEmpodatSuspectPrioritisation
     via the `refresh_prioritisation` allowlist key. Authorisation is enforced
     on the route (auth + role:super_admin), never by this component. --}}
<div x-data="{
        open: false,
        fileId: null,
        fileName: '',
        rowCount: null,
        action: '',
        show(detail) {
            this.fileId = detail.fileId;
            this.fileName = detail.fileName;
            this.rowCount = detail.rowCount;
            this.action = detail.action;
            this.open = true;
        }
     }"
     @empodat-suspect-rebuild-requested.window="show($event.detail)">

  <div x-show="open"
       x-cloak
       @keydown.escape.window="open = false"
       class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-800 bg-opacity-50 p-4 sm:p-8"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       style="display: none;">

    <div @click.outside="open = false"
         class="w-full max-w-2xl my-8 bg-white rounded-lg shadow-xl">

      <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Rebuild prioritisation partition</h2>
          <p class="mt-1 text-sm text-gray-500">
            File <span class="font-mono" x-text="fileId"></span> — <span x-text="fileName"></span>
          </p>
        </div>
        <button type="button" @click="open = false"
                class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none" aria-label="Close">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-5 space-y-4 text-sm text-gray-700">

        <p>
          This queues
          <code class="font-mono text-xs bg-gray-100 px-1 py-0.5 rounded">empodat-suspect:refresh-prioritisation --file=<span x-text="fileId"></span></code>
          on the <span class="font-mono text-xs">exports</span> queue. The partition is built into a staging
          table and swapped in at the end, so readers never see a half-written partition.
        </p>

        <div class="p-4 bg-gray-50 border border-gray-200 rounded-md">
          <p class="font-medium text-gray-900">Current contents</p>
          <p class="mt-1">
            <template x-if="rowCount !== null">
              <span><span class="font-mono" x-text="rowCount"></span> rows will be replaced.</span>
            </template>
            <template x-if="rowCount === null">
              <span>This partition has never been built successfully. It currently contributes nothing to prioritisation.</span>
            </template>
          </p>
        </div>

        <div class="p-4 bg-amber-50 border border-amber-200 rounded-md">
          <p class="font-medium text-amber-900">Every run rebuilds the shared basin lookup first</p>
          <p class="mt-1 text-amber-900">
            That happens before this file is touched and takes a few minutes on production, whether you
            filter to one file or not. To rebuild several files, one unfiltered run from
            <span class="font-medium">EMPODAT Suspect → Commands</span> is cheaper than repeating this.
          </p>
        </div>

        <p class="text-gray-500">
          Nothing else is affected — the other partitions are not detached, dropped or rewritten. The
          separate <span class="font-mono text-xs">empodat_suspect_prioritisation</span> materialized view
          that the R application and the CSV export read is not touched by this command.
        </p>

      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        <button type="button" @click="open = false"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Cancel
        </button>
        <form method="POST" :action="action">
          @csrf
          <button type="submit"
                  class="px-4 py-2 border border-lime-600 rounded-md text-sm font-medium text-white bg-lime-600 hover:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-lime-500">
            Queue rebuild
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
