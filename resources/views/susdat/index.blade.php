<x-app-layout>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('substancies.index')}}" method="GET"> 
            <!-- Main Search form -->
            <div>
              
              
              <input type="hidden" value="1" name="search">
              @foreach ($categories as $category)
              <span class="block"> {{$category->name}} </span>
              @endforeach
              
              
              
              <button type="submit" class="bg-gray-200 border-2 text-bold p-2 hover:bg-gray-50 hover:border-gray-400 hover:border-2"> Submit </button>
            </div>
            <!-- Main Search form -->
          </form>
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
