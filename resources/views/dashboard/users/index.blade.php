<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="userTable()" x-init="init()">
          
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 mr-4">Users</h2>
            @role('super_admin|admin')
            <a href="{{ route('users.create') }}" class="btn-create">
              Add New User
            </a>
            @endrole
          </div>
          
          <!-- User Actions -->
          <div class="mb-6 flex justify-between items-center">
            <div class="flex space-x-4 flex-1">
              <div class="w-32">
                <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                <select id="perPage" x-model="perPage" @change="changePage(1)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
              
              <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" 
                id="search" 
                x-model="search" 
                @input="debounceSearch()" 
                placeholder="Search by name or email..." 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
              </div>
            </div>
        
          </div>
          
          <div class="overflow-x-auto">
            <!-- Loading overlay -->
            <div x-show="loading" class="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-10">
              <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            
            <table class="table-standard w-full">
              <thead>
                <tr class="bg-gray-600 text-white">
                  @foreach ($columns as $column)
                  <th class="py-2 px-4 text-left cursor-pointer" @click="sortBy('{{ $column }}')">
                    <div class="flex items-center">
                      <span>{{ Str::title(str_replace('_', ' ', $column)) }}</span>
                      <template x-if="sortColumn === '{{ $column }}'">
                        <span class="ml-1">
                          <template x-if="sortDirection === 'asc'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                          </template>
                          <template x-if="sortDirection === 'desc'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                          </template>
                        </span>
                      </template>
                    </div>
                  </th>
                  @endforeach
                  <th class="py-2 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(user, index) in users" :key="user.id">
                  <tr class="@if(true) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                    <td class="py-2 px-4" x-text="user.id"></td>
                    <td class="py-2 px-4" x-text="user.first_name"></td>
                    <td class="py-2 px-4" x-text="user.last_name"></td>
                    <td class="py-2 px-4" x-text="user.email"></td>
                    <td class="py-2 px-4">
                      <template x-for="(role, roleIndex) in user.roles" :key="roleIndex">
                        <span class="px-2 mr-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" x-text="role.name"></span>
                      </template>
                    </td>
                    <td class="py-2 px-4" x-text="user.tokens_count"></td>
                    <td class="py-2 px-4">
                      <template x-for="(project, projectIndex) in user.projects" :key="projectIndex">
                        <span class="px-2 mr-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800" x-text="project.abbreviation"></span>
                      </template>
                    </td>
                    <td class="py-2 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a :href="'/backend/users/' + user.id" class="text-blue-600 hover:text-blue-800" title="View">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </a>
                        <a :href="'/backend/users/' + user.id + '/edit'" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        <!-- Uncomment if you need a delete function
                          <form :action="'/backend/users/' + user.id" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            </button>
                          </form>
                        -->
                      </div>
                    </td>
                  </tr>
                </template>
                
                <!-- Empty state -->
                <tr x-show="!loading && users.length === 0" class="bg-slate-100">
                  <td colspan="{{ count($columns) + 1 }}" class="py-6 px-4 text-center text-gray-500">
                    <p class="text-base">No users found</p>
                    <p class="text-sm mt-1">Try adjusting your search or filter to find what you're looking for.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-700" x-show="totalUsers > 0">
              Showing <span x-text="(currentPage - 1) * perPage + 1"></span> to <span x-text="Math.min(currentPage * perPage, totalUsers)"></span> of <span x-text="totalUsers"></span> users
            </div>
            <div class="flex space-x-1" x-show="totalPages > 1">
              <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Previous
              </button>
              
              <template x-for="page in paginationButtons" :key="page">
                <template x-if="page === '...'">
                  <span class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white">...</span>
                </template>
                <template x-if="page !== '...'">
                  <button @click="changePage(page)" :class="page === currentPage ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'" class="px-3 py-2 border rounded-md text-sm font-medium">
                    <span x-text="page"></span>
                  </button>
                </template>
              </template>
              
              <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages" :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  @push('scripts')
  <script>
    function userTable() {
      return {
        users: [],
        loading: true,
        perPage: 25,
        currentPage: 1,
        totalUsers: 0,
        totalPages: 1,
        search: '',
        searchTimeout: null,
        sortColumn: 'id',
        sortDirection: 'asc',
        
        init() {
          console.log('Initializing user table');
          this.fetchUsers();
        },
        
        fetchUsers() {
          this.loading = true;
          console.log('Fetching users with search:', this.search);
          
          // Build URL with search parameters
          const url = new URL('backend/user-data', window.location.origin);
          url.searchParams.append('page', this.currentPage);
          url.searchParams.append('per_page', this.perPage);
          url.searchParams.append('sort', this.sortColumn);
          url.searchParams.append('direction', this.sortDirection);
          
          // Only append search param if it's not empty
          if (this.search && this.search.trim() !== '') {
            url.searchParams.append('search', this.search.trim());
          }
          
          console.log('Fetching from URL:', url.toString());
          
          fetch(url.toString())
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            console.log('Data received:', data);
            this.users = data.data;
            this.totalUsers = data.total;
            this.totalPages = data.last_page;
            this.loading = false;
          })
          .catch(error => {
            console.error('Error fetching users:', error);
            this.loading = false;
            alert('Error loading users: ' + error.message);
          });
        },
        
        debounceSearch() {
          clearTimeout(this.searchTimeout);
          this.searchTimeout = setTimeout(() => {
            console.log('Search term:', this.search);
            this.currentPage = 1; // Reset to first page when searching
            this.fetchUsers();
          }, 500); // 500ms debounce delay
        },
        
        sortBy(column) {
          if (this.sortColumn === column) {
            // Toggle direction if same column
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
          } else {
            // Default to ascending for new column
            this.sortColumn = column;
            this.sortDirection = 'asc';
          }
          this.fetchUsers();
        },
        
        changePage(page) {
          if (page < 1 || page > this.totalPages) {
            return;
          }
          this.currentPage = page;
          this.fetchUsers();
        },
        
        get paginationButtons() {
          const buttons = [];
          const totalPages = this.totalPages;
          const currentPage = this.currentPage;
          
          // Always show first page
          buttons.push(1);
          
          // Show dots if needed (between first and currentPage - 1)
          if (currentPage > 3) {
            buttons.push('...');
          }
          
          // Show page before current if it exists and isn't the first page
          if (currentPage - 1 > 1) {
            buttons.push(currentPage - 1);
          }
          
          // Show current page if it isn't the first or last
          if (currentPage !== 1 && currentPage !== totalPages) {
            buttons.push(currentPage);
          }
          
          // Show page after current if it exists and isn't the last page
          if (currentPage + 1 < totalPages) {
            buttons.push(currentPage + 1);
          }
          
          // Show dots if needed (between currentPage + 1 and last page)
          if (currentPage < totalPages - 2) {
            buttons.push('...');
          }
          
          // Always show last page if there is more than one page
          if (totalPages > 1) {
            buttons.push(totalPages);
          }
          
          return buttons;
        }
      };
    }
  </script>
  
  <script>
    // Set initial users as a fallback
    window.initialUsers = @json($users->take(25));
  </script>
  @endpush
</x-app-layout>