
<script type="text/javascript">
  $(document).ready(function() {
    $('.s2-{{$tag}}').select2();
    @if(isset($active_ids))
    $('.s2-{{$tag}}').val({{$active_ids}});
    $('.s2-{{$tag}}').trigger('change');
    @endif
  });
</script>
<div class="w-full">
  {{-- <label class="block text-gray-700 dark:text-gray-400 text-md font-bold mb-2" for="{{$tag}}">
    {{$tag}}
  </label> --}}
  <select class="{{'s2-'.$tag}}" name="{{$tag}}[]" multiple="multiple">
    @foreach ($list as $key => $value)
    @if(is_array($value))
      <option value="{{$value['id']}}">{{$value['name']}}</option>
    @else
    <option value="{{$key}}">{{$value}}</option>
    @endif
    @endforeach
  </select>
</div>