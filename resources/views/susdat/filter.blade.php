<x-app-layout>
  
  <div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('substances.search')}}" method="GET"> 
            <!-- Main Search form -->
            <div class="grid grid-cols-3 gap-5">
              
              <div class="bg-gray-50 p-2">
                <div class="text-lg font-bold:">
                  Search Category:
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
                  <button type="submit" class="btn-submit"> Apply Category Filter 
                  </button>
                  
                </div>
              </div>
              
              <div>
                <div class="bg-gray-50 p-2">
                  <div class="text-lg font-bold:">
                    Search for specific substance:
                  </div>                  
                </div>
              </div>
              
              <div>
                <div class="bg-gray-50 p-2">
                  <div class="text-lg font-bold:">
                    Search according to source:
                  </div>
                  <form name="asdf" id="asdf" action="{{route('substances.search')}}" method="GET">
                  <div class="w-full">
                    {{-- <input type="hidden" value="1" name="search"> --}}
                    {{-- @include('_t.form-select2', ['tag' => 'source', 'list' => $sourceList, 'active_ids' => null, 'space' => 'request']) --}}
                    @include('_t.test', ['tag' => 'source', 'list' => $sourceList])
                    {{-- @include('_t.test') --}}
                  </div>
                  
    
                    

                  <button type="submit" class="btn-submit"> Apply Source Filter</button>
                  </form>
                </div>
                
              </div>
              <!-- Main Search form -->
            </form>
          </div>        
        </div>
      </div>
    </div>
  </x-app-layout>
  