<x-app-layout>
  
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w">
          
          @if($user->tokens->count() > 0)
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
              <th>Token name</th>
              <th>Token plaintext</th>
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
              <td class="text-center">
                <span class="inline-block bg-white text-black text-sm font-semibold p-2 m-2">
                  <div id="{{ $token->plain_text_token }}">{{ $token->plain_text_token }}</div>
                </span>
                <span class="btn-submit" onclick="copyToClipboard('{{ $token->plain_text_token }}')">
                  <i class="fa fa-clipboard" aria-hidden="true"></i>&nbsp;Copy
                </span>
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
          <h2 class="text-lg font-medium text-gray-900">
            {{ 'Create a new API token'}}
          </h2>
          <form action="{{ route('apiresources.store') }}" method="POST" class="flex space-x-2">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="text" name="token_name" value="" class="form-text p-1 border border-gray-300 rounded-md">
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