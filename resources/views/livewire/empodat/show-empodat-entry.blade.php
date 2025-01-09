<div>
    <!-- Button to trigger modal -->
    <div class="flex justify-center">
        <div class="py-1 pr-1">{{ $recordId }}</div>
        <div>
            <button wire:click="openModal({{ $recordId }})" class="link-lime">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    
    <!-- Modal -->
    @if($showModal)
    <!-- Modal Background -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
        <!-- Modal Container -->
        <div class="bg-white rounded-lg shadow-lg w-1/3 max-h-screen flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center border-b px-6 py-3">
                <h3 class="text-base font-semibold">Data for {{ $recordId }}</h3>
                <button wire:click="closeModal" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <!-- Content (Scrollable) -->
            <div class="flex-1 px-6 py-4 overflow-y-auto">

                <div class="font-semibold text-base border-b-2 border-lime-500">Substance</div>
                {{-- {{ dump($empodat) }} --}}
                <div class="flex justify-between py-1">
                    <div class="font-semibold text-base ">Substance name </div>
                    <div>{{ $empodat->name}}</div>
                </div>            
                <div class="flex justify-between py-1">
                    <div class="font-semibold">Substance code </div>
                    <div> <a href="{{route('substances.show', $empodat->substance_id)}}" target="_blank" class="link-lime">{{ 'NS'.$empodat->code}} </a></div>
                </div>


                <div class="font-semibold text-base border-b-2 border-lime-500">Station</div>
                @foreach ($empodat->station->toArray() as $key => $value)
                @if(!in_array($key, ['id', 'created_at', 'updated_at']))
                    <div class="flex justify-between py-1">
                        <div class="font-semibold">{{ $key }}</div>
                        <div>{{ $value }}</div>
                    </div>
                    @endif
                @endforeach

                <div class="font-semibold text-base border-b-2 border-lime-500">Analytical Methods</div>
                @foreach ($empodat->analyticalMethod->toArray() as $key => $value)
                @if(!in_array($key, ['id', 'created_at', 'updated_at']))
                    <div class="flex justify-between py-1">
                        <div class="font-semibold">{{ $key }}</div>
                        <div>{{ is_null($value) ? 'n/a' : $value }}</div>
                    </div>
                    @endif
                @endforeach
            </div>

            <!-- Footer -->
            <div class="flex justify-end border-t px-6 py-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
