<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w">

          @if(session('new_api_token'))
          <div class="mb-6 p-4 border-2 border-amber-400 bg-amber-50 rounded" role="alert">
            <p class="text-base font-bold text-amber-900">
              <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;Copy your new API token now. It will NOT be shown again.
            </p>
            <p class="mt-1 text-sm text-amber-900">
              For security, only a fingerprint of the token is stored. Once you leave or reload this page, nobody &mdash; including the administrators &mdash; can display it again. If you lose it, revoke it and create a new one.
            </p>
            <span class="inline-block bg-white text-black text-sm font-semibold py-2 px-4 my-2 font-mono break-all border border-amber-300">
              <span id="new-api-token">{{ session('new_api_token') }}</span>
            </span>
            <span class="btn-submit" onclick="copyToClipboard('new-api-token')">
              <i class="fa fa-clipboard" aria-hidden="true"></i>&nbsp;Copy
            </span>
          </div>
          @endif

          @if($user->tokens->count() > 0)
          <p class="mb-2 text-sm text-gray-600">Tokens are shown only once, right after they are created. If you no longer have a token, revoke it and create a new one.</p>
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
              <th>Token name</th>
              <th>Created</th>
              <th>Expiration</th>
              <th>Last used</th>
              <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($user->tokens as $token)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="p-1 text-center">
                {{ $token->name }}
              </td>
              <td class="p-1 text-center">
                {{ $token->created_at }}
              </td>
              <td class="p-1 text-center">
                @if(is_null($token->expires_at))
                <span class="text-red-500">Never</span>
                @else
                {{ $token->expires_at }}
                @endif
              </td>
              <td class="p-1 text-center">
                @if(is_null($token->last_used_at))
                <span class="text-red-500">Never</span>
                @else
                {{ $token->last_used_at }}
                @endif
                
              </td>
              <td class="p-1 text-center">
                <form action="{{ route('apiresources.destroy') }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <input type="hidden" name="token_id" value="{{ $token->id }}">
                  <button type="submit" class="btn-delete">Revoke</button>
                </form>
              </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @else
          <div class="">
            <span class="text-red-500">No API tokens found.</span>
          </div>
          @endif
          
        </div>
      </div>
      
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
          <h2 class="text-lg font-medium text-gray-900 px-2">
            {{ 'Create a new API token'}}
          </h2>
          <form action="{{ route('apiresources.store') }}" method="POST" class="flex space-x-2">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <button type="submit" class="btn-submit">Create API token</button>
          </form>
        </div>
      </div>
      
      
      
    </div>
  </div>

  <script>
    function copyToClipboard(elementId) {
      // Get the text from the element
      var text = document.getElementById(elementId).innerText;
      
      // Create a temporary textarea element to select the text
      var textarea = document.createElement("textarea");
      textarea.textContent = text;
      textarea.style.position = "fixed"; // Prevent scrolling to bottom of page in MS Edge.
      document.body.appendChild(textarea);
      textarea.select();
      
      try {
        // Copy the text inside the textarea to clipboard
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Copying text command was ' + msg);
      } catch (err) {
        console.error('Oops, unable to copy', err);
      }
      
      // Remove the temporary textarea
      document.body.removeChild(textarea);
    }
    </script>

</x-app-layout>