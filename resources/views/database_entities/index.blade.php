<x-app-layout>
 
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div>
            
            <div class="grid lg:grid-cols-3 gap-10">
              @foreach ($databases as $d)
              <a href="{{route($d->dashboard_route_name)}}">
                <div class="bg-white border-gray-100 shadow-lg rounded-md overflow-hidden border-b-2 border-white hover:border-lime-400 hover:text-lime-500">
                  <img src="{{asset('images/databases/'.$d->image_path)}}" alt="Substance Database" class="w-full h-48 object-cover">
                  <div class="m-4">
                    <span class="font-bold">{{$d->name}}</span>
                    <span class="block text-gray-500 text-sm">{{$d->description}}</span>
                  </div>
                  <div class="flex justify-end">
                    <div class="text-gray-500 text-xs p-2">
                      <span>Last update on</span>
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
  </div>
</x-app-layout>
