<x-app-layout>
  <div class="px-4">
    <div class="2xl:w-2/3 xl:w-full mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="pb-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="text-center border-b-2 border-lime-400 p-2">
          NORMAN organises the development and maintenance of various web-based databases for the collection & evaluation of data / information on emerging substances in the environment
        </div>
        
        <div class="mt-4">
          
          <div class="grid lg:grid-cols-3 gap-8 rounded-none ">
            @foreach ($databases as $d)
            @php
            $external = false;
              if (str_starts_with($d->dashboard_route_name, 'https')){
                $external = true;
                $link = $d->dashboard_route_name;
              } else {
                $link = route($d->dashboard_route_name);
              }
            @endphp
            <a href="{{ $link }}" @if($external == true) target="_blank"@endif>
              <div class="rounded-none bg-white border-gray-100 shadow-lg rounded-md overflow-hidden border-b-2 border-white @if($external == true) hover:border-cyan-500 hover:text-cyan-500 @else   hover:border-lime-400 hover:text-lime-500 @endif">
                <div class="flex rounded-0">
                  <div id="icon" class="flex items-top justify-center py-4 px-4 mt-2 ">
                    <i class="{{ $d->image_path }}"></i>
                  </div>
                  <div id="text" class="flex-1">
                    <div class="py-4 pr-2">
                      <span class="font-bold">{{$d->name}}</span>
                      <span class="block text-gray-500 text-sm">{{$d->description}}</span>
                    </div>
                    <div class="flex justify-end">
                      <div class="text-gray-500 text-xs py-2 pr-2">
                        @if($external == true)
                        <span>Link to external entity:</span><span class="text-gray-500 hover:text-cyan-600"> {{$link}}</span>
                        @else
                        <span>Number of records: </span><span class="text-gray-800 font-medium">{{ number_format($d->number_of_records ?? 0, 0, '.', ' ') }}</span>,
                        <span>Last update on: </span><span class="text-gray-800 font-medium">{{ $d->last_update ?? 'n/a' }}</span>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
  
  
  
  
</x-app-layout>