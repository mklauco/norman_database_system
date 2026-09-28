<x-app-layout>
    <x-slot name="header">
        @include('backend.system-settings.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                        <a href="{{ route('backend.user-login-retention.filter', request()->query()) }}" class="link-lime-text">
                            ← Back to Filter
                        </a>

                        <form method="GET" action="{{ route('backend.user-login-retention.search') }}" id="perPageForm" class="w-32">
                            @foreach (request()->except(['per_page', 'page']) as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                            <select name="per_page" id="perPage" onchange="document.getElementById('perPageForm').submit()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                                @foreach ([10, 25, 50, 100] as $option)
                                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if($results->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Login Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IP Address
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User Agent
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Country
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div>{{ $result->user->last_name }}, {{ $result->user->first_name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-mono">{{ $result->user->id }}</span>
                                                    <span class="mx-1">·</span>
                                                    {{ $result->user->email }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $result->formatted_login_date }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                                {{ $result->ip_address ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="max-w-xs truncate" title="{{ $result->meta_data['user_agent'] ?? 'N/A' }}">
                                                    {{ $result->meta_data['user_agent'] ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $result->meta_data['country'] ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $results->appends(request()->except('page'))->links('pagination::tailwind') }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-gray-500 text-lg">
                                No login records found matching your criteria.
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('backend.user-login-retention.filter') }}" class="link-lime-text">
                                    Try different filters
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
