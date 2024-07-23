<x-app-layout>
  <div class="px-4">
    <div class="max-w mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="pb-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="text-center border-b-2 border-lime-400 p-2">
          NORMAN organises the development and maintenance of various web-based databases for the collection & evaluation of data / information on emerging substances in the environment
        </div>
        
        <div class="mt-4">
          
          <div class="grid lg:grid-cols-3 gap-10">
            @foreach ($databases as $d)
            <a href="{{route($d->dashboard_route_name)}}">
              <div class="bg-white border-gray-100 shadow-lg rounded-md overflow-hidden border-b-2 border-white hover:border-lime-400 hover:text-lime-500">
                <div  class="flex">
                  <div id="icon" class="flex items-top justify-center p-4 mt-2 ">
                    <i class="fa fa-calculator"></i>
                  </div>
                  <div id="text"  class="flex-1">
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