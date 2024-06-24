{{-- {{var_dump($tag, $$space[$tag])}} --}}
<select class="{{$tag}}" name="{{$tag}}">
  @foreach ($list as $key => $value)
  <option value="{{$key}}" @if( (($$space[$tag] ?? null) == $value) || $key == $$space[$tag]) selected @endif>{{$value}}</option>
  @endforeach
</select>