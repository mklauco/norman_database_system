<!-- Footer container -->
<footer class="text-center text-surface/75 bg-neutral-900 text-white/75 lg:text-left">
  

  <!-- Main container div: holds the entire content of the footer, with centered sections and responsive styling -->
  <div class="mx-6 py-5 text-center md:text-left">
    <div class="grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto">
      <!-- NORMAN Database System section -->
      <div class="text-center md:text-left">
        <h6 class="mb-4 flex items-center justify-center font-semibold uppercase md:justify-start">
          <span class="me-3 [&>svg]:h-4 [&>svg]:w-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor">
              <path
                d="M12.378 1.602a.75.75 0 00-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03zM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 00.372-.648V7.93zM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 00.372.648l8.628 5.033z" />
            </svg>
          </span>
          NORMAN Database System
        </h6>
        <p class="text-center md:text-left">
          NORMAN organises the development and maintenance of various web-based databases for the collection & evaluation of data / information on emerging substances in the environment
        </p>
      </div>
      
      <!-- Technical Resources section -->
      <div class="text-center md:text-left">
        <h6 class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          <span class="me-3">
            <i class="fas fa-code"></i>
          </span>
          Technical Resources
        </h6>
        <p class="mb-3 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fab fa-github"></i>
          </span>
          <a class="link-lime" href="https://github.com/mklauco/norman_database_system" target="_blank">Source Code Repository</a>
        </p>
        <p class="mb-3 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-bug"></i>
          </span>
          <button class="link-lime cursor-pointer hover:underline" onclick="openIssueModal()">Report Issues</button>
        </p>
        <p class="text-sm text-gray-300 text-center md:text-left">
          Found a problem with the database system? Please visit our GitHub issues page to report bugs or request features.
        </p>
      </div>
      <!-- Products section -->
      {{-- <div>
        <h6
          class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          Products
        </h6>
        <p class="mb-4">
          <a href="#!">Angular</a>
        </p>
        <p class="mb-4">
          <a href="#!">React</a>
        </p>
        <p class="mb-4">
          <a href="#!">Vue</a>
        </p>
        <p>
          <a href="#!">Laravel</a>
        </p>
      </div> --}}
      <!-- Useful links section -->
      {{-- <div>
        <h6
          class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          Useful links
        </h6>
        <p class="mb-4">
          <a href="#!">Pricing</a>
        </p>
        <p class="mb-4">
          <a href="#!">Settings</a>
        </p>
        <p class="mb-4">
          <a href="#!">Orders</a>
        </p>
        <p>
          <a href="#!">Help</a>
        </p>
      </div> --}}
      <!-- Contact section -->
      <div class="text-center md:text-left">
        <h6 class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          <span class="me-3">
            <i class="fas fa-address-book"></i>
          </span>
          Contact
        </h6>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-user-tie"></i>
          </span>
          <span class="px-2">Dr. Jaroslav Slobodnik</span>
        </p>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-envelope"></i>
          </span>
          <span class="text-center md:text-left">
            <a class="link-lime" href="mailto:slobodnik@ei.sk">slobodnik@ei.sk</a> | <a class="link-lime" href="mailto:norman@ei.sk">norman@ei.sk</a>
          </span>
        </p>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-globe"></i>
          </span>
          <a class="link-lime" href="https://www.norman-network.com/">https://www.norman-network.com</a>
        </p>
      </div>
    </div>
  </div>

  <!--Copyright section-->
  <div class="bg-neutral-950 p-2 text-center">
    <span> @php echo date('Y', time()) @endphp © Copyright All Rights Reserved</span>
    <span class="px-1">|</span>
    <a class="font-semibold link-lime" href="https://www.norman-network.com/">NORMAN website</a>
    <span class="px-1">|</span>
    Managed by
    <a class="font-semibold link-lime" href="https://mkassets.sk/">MK Assets, s.r.o.</a>
    <span class="px-1">|</span>
    Last backend update:
    @php
    $S = \Carbon\Carbon::createFromTimestamp(exec("git log -1 --format=%at"), 'UTC');
    @endphp
    <span class="font-semibold">{{$S->tz('Europe/Berlin')->toDateTimeString()}}</span>
  </div>

  <!-- Issue Reporting Modal -->
  <div id="issueModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md mx-4 p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Report Issues</h3>
        <button onclick="closeIssueModal()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <div class="mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <div class="flex">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
            <div>
              <p class="text-sm text-yellow-800 font-medium">Important Notice</p>
              <p class="text-sm text-yellow-700 mt-1">
                This reporting system does <strong>not</strong> cover problems with data inserted in the database.
              </p>
            </div>
          </div>
        </div>
        
        <div class="space-y-4">
          <div class="border-l-4 border-slate-500 pl-4">
            <h4 class="font-medium text-gray-900 mb-2">
              <i class="fas fa-database mr-2 text-slate-600"></i>
              Data-related Issues
            </h4>
            <p class="text-sm text-gray-700 mb-2">
              For problems with database content, data accuracy, or data submission:
            </p>
            <a href="mailto:norman@ei.sk" class="link-lime">
              <i class="fas fa-envelope mr-2"></i>
              norman@ei.sk
            </a>
          </div>
          
          <div class="border-l-4 border-slate-500 pl-4">
            <h4 class="font-medium text-gray-900 mb-2">
              <i class="fas fa-code mr-2 text-slate-600"></i>
              Technical Program Issues
            </h4>
            <p class="text-sm text-gray-700 mb-2">
              For bugs, incorrect program behavior, or feature requests:
            </p>
            <button onclick="goToGitHubIssues()" class="link-lime">
              <i class="fab fa-github mr-2"></i>
              GitHub Issues
            </button>
          </div>
        </div>
      </div>
      
      <div class="flex justify-end space-x-3">
        <button onclick="closeIssueModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
          Cancel
        </button>
      </div>
    </div>
  </div>

  <script>
    function openIssueModal() {
      document.getElementById('issueModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
    
    function closeIssueModal() {
      document.getElementById('issueModal').classList.add('hidden');
      document.body.style.overflow = 'auto';
    }
    
    function goToGitHubIssues() {
      window.open('https://github.com/mklauco/norman_database_system/issues', '_blank');
      closeIssueModal();
    }
    
    // Close modal when clicking outside of it
    document.getElementById('issueModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeIssueModal();
      }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeIssueModal();
      }
    });
  </script>
</footer>