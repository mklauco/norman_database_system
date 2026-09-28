<x-app-layout>
    <x-slot name="header">
        @include('backend.system-settings.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('backend.user-login-retention.search') }}" class="space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- User Filter -->
                            <div>
                                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    User
                                </label>
                                @livewire('backend.user-search', [
                                    'selectedUserId' => request()->integer('user_id') ?: null,
                                    'fieldName' => 'user_id',
                                    'minimumSearchLength' => 3,
                                ])
                                <p class="mt-1 text-xs text-gray-500">Leave empty for all users.</p>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date From
                                </label>
                                <input type="date" name="date_from" id="date_from" 
                                       value="{{ request('date_from', now()->subMonth()->format('Y-m-d')) }}"
                                       class="form-text">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date To
                                </label>
                                <input type="date" name="date_to" id="date_to" 
                                       value="{{ request('date_to', now()->format('Y-m-d')) }}"
                                       class="form-text">
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <button type="submit" class="btn-submit">
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
