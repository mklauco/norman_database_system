<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="grid lg:grid-cols-3 md:grid-cols-2 gap-4">
            
            @role('super_admin')
            <div class="col-span-2 bg-white border-gray-100 shadow-lg rounded-md overflow-hidden p-4">
              <div class="font-bold">
                Execute processes <i class="fas fa-lock ml-2"></i>
              </div>
              <div class="mt-2 gap-2">
                <div class="font-bold text-gray-600">
                  Empodat:
                </div>

                <div class="flex gap-2">
                <form action="{{route('cod.unique.search.countries')}}" method="POST">
                  @csrf
                  <button type="submit" class="btn-submit">Generate Unique Countries</button>
                </form>
                
                <form action="{{route('cod.unique.search.matrices')}}" method="POST">
                  @csrf
                  <button type="submit" class="btn-submit">Generate Unique Ecosystems</button>
                </form>
                
                <form action="{{route('update.dbentities.counts')}}" method="POST">
                  @csrf
                  <button type="submit" class="btn-submit">Update DB Entities</button>
                </form>
                </div>
              </div>

              <div class="mt-2 gap-2">
                <div class="font-bold text-gray-600">
                  SLE:
                </div>
                <div class="flex gap-2">
                <form action="{{route('slehome.countAll')}}" method="GET">
                  @csrf
                  <button type="submit" class="btn-submit">Count All Sources</button>
                </form>
                </div>
              </div>
            </div>
            @endrole
            
            <div class="bg-white border-gray-100 shadow-lg rounded-md overflow-hidden p-4">
              <div class="font-bold">
                API Tokens
              </div>
              <div class="mt-2">
                @if($user->tokens->count() == 0)
                <span class="text-red-500">No API tokens created yet</span>
                @else
                <span class="text-green-500">API token assigned</span>
                @endif  
              </div>
            </div>
            
            <div class="col-span-2 bg-white border-gray-100 shadow-lg rounded-md overflow-hidden p-4">
              <div class="font-bold">
                SUSDAT duplicates 
              </div>
              <div class="mt-2">
              </div>
            </div>
            
            <div class="bg-white border-gray-100 shadow-lg rounded-md overflow-hidden p-4">
              <div class="font-bold">
                Database Statistics
              </div>
              <div class="mt-2">
              </div>
            </div>
            
          </div> 
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
