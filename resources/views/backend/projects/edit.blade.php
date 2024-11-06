<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="">
          
          <form action="{{route('projects.update', $project->id)}}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PUT')
            
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
              @foreach($fillables as $key)
              <div>
                <div class="mt-5">
                  <h2 class="text-lg font-medium text-gray-900">{{ ucfirst($key) }}</h2>
                  <input type="text" name="{{ $key }}" id="{{ $key }}" value="{{$project->$key}}" class="block w-full form-text" required>
                  @error($key)
                  <div class="text-red-500">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              @endforeach
              <div class="flex justify-end mt-5">
                <button type="submit" class="btn-submit"> Update project 
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>