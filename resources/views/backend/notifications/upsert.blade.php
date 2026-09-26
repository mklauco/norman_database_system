<x-app-layout>
    <x-slot name="header">
        @include('backend.system-settings.header')
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Back Link -->
                    <div class="mb-6">
                        <a href="{{ route('backend.notifications.index') }}" class="link-lime-text text-slate-600 hover:text-slate-900 inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Notifications
                        </a>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mt-2 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ isset($notification) ? route('backend.notifications.update', $notification) : route('backend.notifications.store') }}">
                        @csrf
                        @if(isset($notification))
                            @method('PUT')
                        @endif

                        <!-- Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   value="{{ old('title', $notification->title ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-slate-500 focus:border-slate-500 @error('title') border-red-500 @enderror"
                                   required>
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div class="mb-6">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message" 
                                      id="message" 
                                      rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-slate-500 focus:border-slate-500 @error('message') border-red-500 @enderror"
                                      required>{{ old('message', $notification->message ?? '') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date and Time Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Start Date Time -->
                            <div>
                                <label for="start_datetime" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       name="start_datetime" 
                                       id="start_datetime" 
                                       value="{{ old('start_datetime', isset($notification) ? $notification->start_datetime_cet : '') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-slate-500 focus:border-slate-500 @error('start_datetime') border-red-500 @enderror"
                                       required>
                                <p class="mt-1 text-xs text-gray-500">Time in Central European Time (CET/CEST)</p>
                                @error('start_datetime')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- End Date Time -->
                            <div>
                                <label for="end_datetime" class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       name="end_datetime" 
                                       id="end_datetime" 
                                       value="{{ old('end_datetime', isset($notification) ? $notification->end_datetime_cet : '') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-slate-500 focus:border-slate-500 @error('end_datetime') border-red-500 @enderror"
                                       required>
                                <p class="mt-1 text-xs text-gray-500">Time in Central European Time (CET/CEST)</p>
                                @error('end_datetime')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Is Active Checkbox -->
                        <div class="mb-6">
                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" 
                                       name="is_active" 
                                       id="is_active" 
                                       value="1"
                                       {{ old('is_active', $notification->is_active ?? true) ? 'checked' : '' }}
                                       class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                                    Active (uncheck to disable the notification)
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                When unchecked, the notification will not be displayed even if within the date range.
                            </p>
                        </div>

                        <!-- Current Status (for edit mode) -->
                        @if(isset($notification))
                            <div class="mb-6 p-4 bg-gray-50 rounded-md">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Current Status</h4>
                                <div class="text-sm text-gray-600">
                    <p><strong>Created by:</strong> {{ $notification->createdBy ? $notification->createdBy->first_name . ' ' . $notification->createdBy->last_name : 'Unknown' }} on {{ $notification->created_at_formatted }} CET</p>
                    @if($notification->turned_off_datetime)
                        <p><strong>Turned off by:</strong> {{ $notification->turnedOffBy ? $notification->turnedOffBy->first_name . ' ' . $notification->turnedOffBy->last_name : 'Unknown' }} on {{ $notification->turned_off_datetime_formatted }} CET</p>
                    @endif
                                    <p><strong>Currently:</strong> 
                                        @if($notification->isCurrentlyActive())
                                            <span class="text-green-600 font-medium">Active and Visible</span>
                                        @elseif($notification->turned_off_datetime)
                                            <span class="text-red-600 font-medium">Manually Turned Off</span>
                                        @elseif(!$notification->is_active)
                                            <span class="text-gray-600 font-medium">Inactive</span>
                                        @else
                                            <span class="text-yellow-600 font-medium">Scheduled</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('backend.notifications.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="btn-submit px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ isset($notification) ? 'Update Notification' : 'Create Notification' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Ensure end date is after start date
        document.getElementById('start_datetime').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_datetime');
            if (startDate && (!endDateInput.value || endDateInput.value <= startDate)) {
                const startDateTime = new Date(startDate);
                startDateTime.setHours(startDateTime.getHours() + 1);
                endDateInput.value = startDateTime.toISOString().slice(0, 16);
            }
        });
    </script>
</x-app-layout>
