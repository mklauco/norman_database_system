{{-- Onboarding overview for a new EMPODAT Suspect source file.
     Rendered on the Uploaded DCT Files page when the Database filter is set
     to EMPODAT Suspect. Button + modal are one Alpine scope so the page needs
     no global events. Full procedure: Empodat-Suspect-3-new-source.md in the
     internal documentation repository. --}}
<div x-data="{ open: false }" class="inline-block">

  <button type="button"
          @click="open = true"
          class="inline-flex items-center gap-2 px-4 py-2 border border-lime-600 rounded-md text-sm font-medium text-lime-700 bg-white hover:bg-lime-50 focus:outline-none focus:ring-2 focus:ring-lime-500">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    Adding a new source?
  </button>

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
         class="w-full max-w-3xl my-8 bg-white rounded-lg shadow-xl">

      <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Onboarding a new EMPODAT Suspect source</h2>
          <p class="mt-1 text-sm text-gray-500">High-level overview. The full procedure lives in the internal documentation.</p>
        </div>
        <button type="button" @click="open = false"
                class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none" aria-label="Close">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-5">
        <ol class="space-y-3 text-sm text-gray-700 list-decimal list-outside ml-5">

          <li>
            <span class="font-medium text-gray-900">Read the spreadsheet first.</span>
            Confirm it has a substance identifier (NORMAN ID), <code class="font-mono text-xs">IP</code> / <code class="font-mono text-xs">IP_max</code>,
            <code class="font-mono text-xs">Units</code>, and one contiguous block of station columns. Note where that block
            starts and ends — the seeder needs both as constants. An HRMS metadata block is optional.
          </li>

          <li>
            <span class="font-medium text-gray-900">Decide how a station column header maps to a station.</span>
            Every source differs here, and this is the step that most often goes wrong.
          </li>

          <li>
            <span class="font-medium text-gray-900">Put the file in <code class="font-mono text-xs">storage/app/public/empodat_suspect/</code>.</span>
            Flat, no subdirectories. It is gitignored and never travels with a deploy.
          </li>

          <li>
            <span class="font-medium text-gray-900">Allocate the <code class="font-mono text-xs">files.id</code> — and verify it is free.</span>
            The 10000+ block is reserved for EMPODAT Suspect by convention only. Nothing enforces it:
            <code class="font-mono text-xs">files.id</code> is an ordinary sequence shared with uploads made on this page.
            Check <code class="font-mono text-xs">SELECT last_value FROM files_id_seq;</code> before trusting that your id is free.
          </li>

          <li>
            <span class="font-medium text-gray-900">Copy the five BlackSea seeder classes and edit constants only.</span>
            There is no generic importer — that was a deliberate decision, not a gap. Stream the file in one read pass;
            never load it into an array.
          </li>

          <li>
            <span class="font-medium text-gray-900">Smoke-test, then import for real.</span>
            Cap the run with <code class="font-mono text-xs">EMPODAT_SUSPECT_SEED_ROW_LIMIT=10000</code> first.
            Never run two source imports at once — they interleave a shared sequence and corrupt the per-file id ranges.
          </li>

          <li>
            <span class="font-medium text-gray-900">QA before refreshing anything.</span>
            Check for unresolved station headers above all: a header that never resolved silently discards every
            measurement in that column. Also check ambiguous headers, substances missing from susdat, and row counts.
          </li>

          <li>
            <span class="font-medium text-gray-900">Rescan the file on this page.</span>
            Only the Rescan button fills <code class="font-mono text-xs">main_id_from</code>,
            <code class="font-mono text-xs">main_id_to</code> and <code class="font-mono text-xs">number_of_records</code>. No seeder does it.
          </li>

          <li>
            <span class="font-medium text-gray-900">Refresh the derived data, in order.</span>
            Filters → matrix metadata → prioritisation → statistics. Running matrix metadata before filters silently
            builds views from a stale helper table, with no error. The <span class="font-medium">Refresh all</span>
            button on the EMPODAT Suspect Commands page enforces the correct order.
          </li>

          <li>
            <span class="font-medium text-gray-900">Deploying to production.</span>
            Feature PR into <code class="font-mono text-xs">development</code>, then a release PR into
            <code class="font-mono text-xs">main</code> as a merge commit, never a squash. The spreadsheet must be copied
            to the shared storage directory separately. Migrations run only through the
            <span class="font-medium">Migrate Production DB</span> GitHub Action — never over SSH.
          </li>

        </ol>

        <div class="mt-5 p-4 bg-gray-50 border border-gray-200 rounded-md">
          <p class="text-sm font-medium text-gray-900">Two things people expect that are not true</p>
          <ul class="mt-2 space-y-1 text-sm text-gray-700 list-disc list-outside ml-5">
            <li>No prioritisation partition migration is needed. The refresh command creates the partition itself, with
              indexes; a migration's partition is dropped on the first run.</li>
            <li>A newly onboarded source does not reach the R application or the CSV export. Both still read the older
              materialized view, which no command refreshes.</li>
          </ul>
        </div>
      </div>

      <div class="flex justify-end px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        <button type="button" @click="open = false"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Close
        </button>
      </div>

    </div>
  </div>
</div>
