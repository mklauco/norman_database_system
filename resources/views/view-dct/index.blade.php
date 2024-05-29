@foreach ($data as $d)
  {{$d->id}}
@endforeach
{{ $data->links() }}