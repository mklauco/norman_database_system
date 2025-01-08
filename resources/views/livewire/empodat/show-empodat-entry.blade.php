<div>
    <!-- Button to trigger modal -->
    <a wire:click="openModal({{ $recordId }})" class="link-lime">
        <i class="fas fa-search"></i>
    </a>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-1/3">
                <!-- Modal Header -->
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-lg font-semibold">Data for {{ $recordId }}</h3>
                    <button wire:click="closeModal" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <!-- Modal Content -->
                <div class="py-4">
                    To be implemented...
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end pt-3">
                    <button wire:click="closeModal" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
