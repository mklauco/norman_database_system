@php
  $navActive = $navActive ?? 'overview';
  $tabClass = fn (bool $active): string => $active
      ? 'px-3 py-1 rounded-sm text-sm font-medium bg-lime-500 text-white'
      : 'px-3 py-1 rounded-sm text-sm font-medium text-gray-600 hover:bg-lime-100';
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div class="flex items-center gap-2">
    <a href="{{ route('usage-statistics.index', ['year' => $year]) }}" class="{{ $tabClass($navActive === 'overview') }}">Overview</a>
    <a href="{{ route('usage-statistics.users', ['year' => $year]) }}" class="{{ $tabClass($navActive === 'users') }}">Users</a>
    <a href="{{ route('usage-statistics.logins', ['year' => $year]) }}" class="{{ $tabClass($navActive === 'logins') }}">Logins</a>
  </div>
  <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
    <label for="usage-year" class="text-sm font-medium">Year:</label>
    <select id="usage-year" name="year" class="border rounded-sm px-2 py-1 text-sm" onchange="this.form.submit()">
      @foreach ($years as $y)
        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
      @endforeach
    </select>
    <noscript><button type="submit" class="px-3 py-1 bg-lime-600 text-white rounded-sm text-sm">Apply</button></noscript>
  </form>
</div>
