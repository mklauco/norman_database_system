@php($code = $activeModule ?? null)
@switch($code)
  @case('empodat')
    @include('empodat.header')
  @break

  @case('ecotox')
    @include('ecotox.header')
  @break

  @case('arbg')
    @include('arbg.header')
  @break

  @case('indoor')
    @include('indoor.header')
  @break

  @case('passive')
    @include('passive.header')
  @break

  @case('bioassay')
    @include('bioassay.header')
  @break

  @case('sars')
    @include('sars.header')
  @break

  @default
    @include('backend.system-settings.header')
@endswitch