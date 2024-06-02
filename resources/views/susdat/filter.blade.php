<x-app-layout>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('substancies.search')}}" method="GET"> 
            <!-- Main Search form -->
            <div class="grid grid-cols-2 gap-5">
              
              <div>
                <div class="text-sm border-b-2">
                  Search Category
                </div>
                <div>
                  <input type="hidden" value="1" name="search">
                  @foreach ($categories as $category)
                  <div class="block p-1">
                    <span>
                      <input type="checkbox" name="category[]" value="{{$category->id}}">
                    </span>
                    <span class="ml-1">
                      {{$category->name}} 
                    </span>
                  </div>
                  @endforeach
                </div>
                
                
                <div>
                <button type="submit" class="bg-gray-200 border-2 text-bold p-2 hover:bg-gray-50 hover:border-gray-400 hover:border-2"> Submit 
                </button>
                <button class="bg-blue-100 border-2 text-bold p-2 hover:bg-gray-50 hover:border-blue-400 hover:border-2">
                  <a href="{{route('substancies.index')}}" > 
                    View all </a>
                </button>
                </div>
              </div>
              
              
              <div>
                asdf
              </div>
            </div>
            <!-- Main Search form -->
          </form>
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
