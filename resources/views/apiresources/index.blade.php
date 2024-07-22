<x-app-layout>
  
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
          
          @if($user->tokens->count() > 0)
          @foreach ($user->tokens as $token)
          <span class="inline-block bg-slate-200 text-black text-sm font-semibold px-4 py-2 mt-2 rounded-full">
            {{var_dump($token)}}
          </span>
          
          @endforeach
          @else
          <div class="">
            <span class="text-red-500">No API tokens found.</span>
          </div>
          @endif
          
        </div>
      </div>
      
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
          <h2 class="text-lg font-medium text-gray-900">
            {{ 'Create a new API token'}}
          </h2>
          <form action="{{route('apiresources.store')}}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{$user->id}}">
            <input type="text" name="token_name" value="" class="form-text">
            <button type="submit" class="btn-submit">Create API token</button>
          </form>
        </div>
      </div>
      
      
    </div>
  </div>
</x-app-layout>