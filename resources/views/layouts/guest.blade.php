<!doctype html>
<html lang="id" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>@yield('title', config('app.name'))</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Pilih salah satu: CDN atau Vite --}}
  <script src="https://cdn.tailwindcss.com"></script>
  {{-- @vite(['resources/css/app.css','resources/js/app.js']) --}}

  @stack('head')
</head>
<body class="h-full bg-gray-50">
  <main class="min-h-screen flex items-center justify-center px-4">
    @yield('content')
  </main>

  @stack('scripts')
</body>
</html>
