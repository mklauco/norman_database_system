<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Google Analytics - GDPR Compliant -->
  <x-google-analytics />

  <title>{{ config('app.name', 'Laravel') }}</title>

  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('f/apple-touch-icon.png') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('f/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('f/favicon-16x16.png') }}">
  <link rel="manifest" href="{{ asset('f/site.webmanifest') }}">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
  <!-- Scripts -->

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />



  <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>



  {{-- Leaflet --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

  <!-- Make sure you put this AFTER Leaflet's CSS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>


  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/select2-tw.css'])

  {{-- <script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js"></script> --}}
  {{-- <link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet"></link> --}}
  @livewireStyles
</head>

<body class="font-sans antialiased">
  <div class="min-h-screen flex flex-col  bg-gray-100">
    <!-- Notification Banner -->
    @if (isset($activeNotifications) && $activeNotifications->count() > 0)
      @foreach ($activeNotifications as $notification)
        <div class="w-full bg-yellow-100 border-b border-yellow-200 px-4 py-3">
          <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center">
              <div class="shrink-0">
                <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">{{ $notification->title }}</h3>
                <div class="text-sm text-yellow-700">{{ $notification->message }}</div>
              </div>
            </div>
            <div class="shrink-0 text-xs text-yellow-600">
              Valid until {{ $notification->end_datetime->setTimezone('Europe/Prague')->format('M j, Y H:i') }} CET
            </div>
          </div>
        </div>
      @endforeach
    @endif

    @include('layouts.navigation')

    <!-- Page Heading -->
    @isset($header)
      <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {{ $header }}
        </div>
      </header>
    @endisset

    {{-- @isset($errors) --}}
    <div class="max-w-6xl mx-auto">
      @include('_t.errors')
    </div>
    {{-- @endisset --}}

    <!-- Page Content -->
    <main class="grow">
      {{ $slot }}
    </main>

    @include('layouts.footer')
  </div>
  {{-- @include('layouts.footer') --}}




  <!-- Scripts -->

  @livewireScripts
  @stack('scripts')

</body>

</html>
