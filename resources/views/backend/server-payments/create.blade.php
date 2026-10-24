<x-app-layout>
    <x-slot name="header">
        @include('backend.system-settings.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('backend.server-payments.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="period_start_date" class="block text-sm font-medium text-gray-700 mb-2">Period start</label>
                                <input type="date" name="period_start_date" id="period_start_date" value="{{ old('period_start_date', $defaultStartDate) }}" class="form-text" required>
                                @error('period_start_date')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="period_end_date" class="block text-sm font-medium text-gray-700 mb-2">Period end</label>
                                <input type="date" name="period_end_date" id="period_end_date" value="{{ old('period_end_date', $defaultEndDate) }}" class="form-text" required>
                                @error('period_end_date')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="not_paid" {{ old('status')==='not_paid' ? 'selected' : '' }}>Not paid</option>
                                    <option value="pending" {{ old('status')==='pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="paid" {{ old('status')==='paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                                @error('status')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="amount_without_vat" class="block text-sm font-medium text-gray-700 mb-2">Amount (no VAT)</label>
                                <input type="number" step="0.01" min="0" name="amount_without_vat" id="amount_without_vat" value="{{ old('amount_without_vat') }}" class="form-text" required>
                                @error('amount_without_vat')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="variable_symbol" class="block text-sm font-medium text-gray-700 mb-2">Variable symbol</label>
                            <input type="text" name="variable_symbol" id="variable_symbol" value="{{ old('variable_symbol') }}" class="form-text">
                            @error('variable_symbol')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('backend.server-payments.index') }}" class="link-lime-text">Cancel</a>
                            <button type="submit" class="btn-submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


