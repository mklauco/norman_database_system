<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <form action="{{route('projects.update', $project->id)}}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
              @foreach($fillables as $key)
              <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                  <h2 for="{{ $key }}" class="text-lg font-medium text-gray-900">{{ $key }}</h2>
                  <input type="text" name="{{ $key }}" id="{{ $key }}" value="{{$project->$key}}" class="block w-full form-text text-sm" required>
                  @error($key)
                  <div class="text-red-500">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              @endforeach
            </div>
            
            <div class="flex justify-end m-2">
              <button type="submit" class="btn-submit"> Update project 
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>