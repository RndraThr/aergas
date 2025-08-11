<!doctype html>
<html lang="id" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>@yield('title', config('app.name'))</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind CDN (cepat untuk start). Jika mau Vite, tinggal aktifkan baris @vite di bawah --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkfQqb1iVj0G6R9GAsx1hZlQ5GdISo5uXVVhFQ4lJ8iP+P1Z9Hf3bJrSw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  {{-- Alpine.js (untuk toggle sidebar, dropdown, dsb) --}}
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  {{-- Jika pakai Vite: --}}
  {{-- @vite(['resources/css/app.css','resources/js/app.js']) --}}

  <style>
    :root { --sidebar-w: 18rem; }
    html, body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji'; }
  </style>

  @stack('head')
</head>
<body class="h-full bg-gray-50 text-gray-800" x-data="{ sidebarOpen: false }">

  {{-- NAVBAR --}}
  @include('layouts.navbar')

  <div class="flex">
    {{-- SIDEBAR --}}
    @include('layouts.sidebar')

    {{-- MAIN CONTENT --}}
    <main class="flex-1 min-h-screen">
      {{-- Flash messages --}}
      @if (session('success') || session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
          @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-700 mb-3">
              {{ session('success') }}
            </div>
          @endif
          @if (session('error'))
            <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
              {{ session('error') }}
            </div>
          @endif
        </div>
      @endif

      <div class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
      </div>
    </main>
  </div>

  {{-- Hidden logout form as fallback (non-JS / graceful) --}}
  <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
  </form>

  @stack('scripts')
  <script>
    // helper fetch logout (opsional; fallback form juga ada)
    window.appLogout = function() {
      const f = document.getElementById('logout-form');
      f && f.submit();
    }
  </script>
</body>
</html>
