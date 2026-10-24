<x-app-layout>
    <x-slot name="header">
        @include('backend.system-settings.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Server Payments</h2>
                        @hasanyrole('super_admin|server_payment_admin')
                        <a href="{{ route('backend.server-payments.create') }}" class="btn-create">Add payment</a>
                        @endhasanyrole
                    </div>

                    @if(session('status'))
                        <div class="mb-4 text-sm text-green-700">{{ session('status') }}</div>
                    @endif

                    @if($payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount (no VAT)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variable symbol</th>
                                        @hasanyrole('super_admin|server_payment_admin')
                                        <th class="px-6 py-3"></th>
                                        @endhasanyrole
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payments as $payment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->formatted_period }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ ucfirst(str_replace('_',' ', $payment->status)) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($payment->amount_without_vat, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->variable_symbol }}</td>
                                            @hasanyrole('super_admin|server_payment_admin')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('backend.server-payments.edit', $payment) }}" class="link-lime-text">Edit</a>
                                                    <form method="POST" action="{{ route('backend.server-payments.destroy', $payment) }}" onsubmit="return confirm('Delete this payment?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="link-lime-text">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                            @endhasanyrole
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-600">No records found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


