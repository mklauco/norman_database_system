<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="userTable()" x-init="init()">
          <div class="flex justify-between mb-6">
            <div class="flex space-x-4">
              <div>
                <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                <select id="perPage" x-model="perPage" @change="changePage(1)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
              
              <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" 
                id="search" 
                x-model="search" 
                @input="debounceSearch()" 
                placeholder="Search by name or email..." 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
              </div>
            </div>
            
            @role('super_admin|admin')
            <div>
              <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New User
              </a>
            </div>
            @endrole
          </div>
          
          <div class="overflow-x-auto rounded-lg shadow relative">
            <!-- Loading overlay -->
            <div x-show="loading" class="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-10">
              <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  @foreach ($columns as $column)
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('{{ $column }}')">
                    <div class="flex items-center">
                      <span>{{ Str::title(str_replace('_', ' ', $column)) }}</span>
                      <template x-if="sortColumn === '{{ $column }}'">
                        <span>
                          <template x-if="sortDirection === 'asc'">
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                          </template>
                          <template x-if="sortDirection === 'desc'">
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                          </template>
                        </span>
                      </template>
                    </div>
                  </th>
                  @endforeach
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(user, index) in users" :key="user.id">
                  <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="user.id"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.first_name"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.last_name"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.email"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <template x-for="(role, roleIndex) in user.roles" :key="roleIndex">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1" x-text="role.name"></span>
                      </template>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.tokens_count"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <template x-for="(project, projectIndex) in user.projects" :key="projectIndex">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1" x-text="project.abbreviation"></span>
                      </template>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <a :href="'/backend/users/' + user.id + '/edit'" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                    </td>
                  </tr>
                </template>
                
                <!-- Empty state -->
                <tr x-show="!loading && users.length === 0" class="bg-white">
                  <td colspan="{{ count($columns) + 1 }}" class="px-6 py-10 text-center text-gray-500">
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
        }
        
        // Rest of your methods...
      };
    }
  </script>
  
  <script>
    // Set initial users as a fallback
    window.initialUsers = @json($users->take(25));
  </script>
  @endpush
</x-app-layout>