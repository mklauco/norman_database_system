<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          @if($edit == true)
          
          <form action="{{route('users.update', $user->id)}}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @else
            
            <form action="{{route('users.store')}}" method="POST" enctype="multipart/form-data">  
              @csrf
              @method('POST')
              @endif
              
              
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-1 px-2">Item</th>
                    <th class="py-1 px-2">Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="bg-slate-100">
                    <td class="py-1 px-2 font-bold text-sm">First name:</td>
                    <td class="py-1 px-2">
                      <input type="text" name="first_name" value="{{$user->first_name ?? null}}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="py-1 px-2 font-bold text-sm">Last name:</td>
                    <td class="py-1 px-2">
                      <input type="text" name="last_name" value="{{$user->last_name ?? null}}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="py-1 px-2 font-bold text-sm">Email:</td>
                    <td class="py-1 px-2">
                      <input type="text" name="email" value="{{$user->email ?? null}}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="py-1 px-2 font-bold text-sm">Roles:</td>
                    <td class="py-1 px-2">
                      <div class="grid grid-cols-3 gap-1">
                        {{-- {{ dump }} --}}
                        @php
                        if( (auth()->user()->role == 'super_admin') || (auth()->user()->role == 'admin')) {
                          $roles = Spatie\Permission\Models\Role::all();
                        } else {
                          $roles = Spatie\Permission\Models\Role::whereNotIn('name', ['super_admin', 'admin'])->get();
                        }
                        @endphp
                        @foreach ($roles as $role)
                        <label class="inline-flex items-center space-x-2">
                          <input type="checkbox" name="roles[]" value="{{ $role->name }}" @if($user->hasRole($role->name)) checked @endif >
                          <span class="pl-1 text-sm">{{ $role->name }}</span>
                        </label>
                        @endforeach
                      </div>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="py-1 px-2 font-bold text-sm">Projects:</td>
                    <td class="py-1 px-2">
                      <div class="grid grid-cols-3 gap-1">
                        @foreach ($projects as $project)
                        <label class="inline-flex items-center space-x-2">
                          <input type="checkbox" name="projects[]" value="{{ $project->id }}" @if($user->projects->contains($project->id)) checked @endif >
                          <span class="pl-1 text-sm">{{ $project->name }}</span>
                        </label>
                        @endforeach
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              
              <div class="flex justify-end m-2 gap-2">
                <a href="{{route('users.index')}}" class="btn-clear"> Cancel</a>
                <button type="submit" class="btn-submit"> Submit
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </x-app-layout>