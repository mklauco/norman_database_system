<div>
    <input type="hidden" name="{{ $fieldName }}" value="{{ $selectedUserId }}">

    @if($selectedUser)
        <div class="flex items-center justify-between p-2 bg-gray-100 border border-gray-300 rounded-md">
            <div>
                <span class="font-medium text-gray-900">{{ $selectedUser['name'] }}</span>
                <span class="text-gray-500 text-sm">(<span class="font-mono">{{ $selectedUser['id'] }}</span> · {{ $selectedUser['email'] }})</span>
            </div>
            <button type="button" wire:click="clearUser" class="text-red-600 hover:text-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @else
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm"
                placeholder="Search by name, email or ID..."
            >

            @if($showResults)
                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($results->count() > 0)
                        @foreach($results as $user)
                            <button
                                type="button"
                                wire:click="selectUser({{ $user->id }})"
                                class="w-full text-left px-3 py-2 hover:bg-gray-100 border-b border-gray-100 last:border-b-0"
                            >
                                <div class="font-medium text-gray-900">{{ $user->last_name }}, {{ $user->first_name }}</div>
                                <div class="text-sm text-gray-500"><span class="font-mono">{{ $user->id }}</span> · {{ $user->email }}</div>
                            </button>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-gray-500 text-sm">No users found</div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
