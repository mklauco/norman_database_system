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
            @foreach($logins as $login)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="max-w-xs truncate" title="{{ $login->user->last_name }}, {{ $login->user->first_name }}">
                            {{ $login->user->last_name }}, {{ $login->user->first_name }}
                        </div>
                        <div class="max-w-xs truncate text-xs text-gray-500" title="{{ $login->user->email }}">
                            <span class="font-mono">{{ $login->user->id }}</span>
                            <span class="mx-1">·</span>
                            {{ $login->user->email }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $login->formatted_login_date }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                        {{ $login->ip_address ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="max-w-xs truncate" title="{{ $login->meta_data['user_agent'] ?? 'N/A' }}">
                            {{ $login->meta_data['user_agent'] ?? 'N/A' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $login->meta_data['country'] ?? 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
