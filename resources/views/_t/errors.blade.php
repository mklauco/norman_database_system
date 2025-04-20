<div class="pt-2">
  @if (session()->has('success'))
  <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">{!! session()->get('success') !!}</div>
  @endif
  
  @if (session()->has('failure') || session()->has('error'))
  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
    @if (session()->has('failure'))
    <div>{!! session()->get('failure') !!}</div>
    @endif
    
    @if (session()->has('error'))
    <div>{!! session()->get('error') !!}</div>
    @endif
  </div>
  @endif
  
  @if ($errors->any())
  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
    <ul>
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif
</div>