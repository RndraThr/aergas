@php
    $u = auth()->user();
    $isActive = function ($pattern) {
        return request()->routeIs($pattern) ? 'bg-gray-100 text-indigo-600' : 'text-gray-700 hover:bg-gray-50';
    };

    // akses Photo Approvals (tracer/admin)
    $canPhotos = $u && (
        (method_exists($u,'isTracer') && $u->isTracer()) ||
        (method_exists($u,'isAdmin') && $u->isAdmin()) ||
        // kalau pakai spatie:
        (method_exists($u,'hasAnyRole') && $u->hasAnyRole(...['tracer','admin','super_admin']))
    );
@endphp

<aside class="hidden md:block w-[var(--sidebar-w)] border-r border-gray-200 bg-white min-h-screen">
  <div class="p-4">
    <div class="text-xs uppercase text-gray-500 mb-2">Menu</div>
    <nav class="space-y-1">
      <a href="{{ route('dashboard') }}"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('dashboard') }}">
        <i class="fa-solid fa-gauge-high w-5 text-center"></i>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('customers.index') }}"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('customers.*') }}">
        <i class="fa-solid fa-users w-5 text-center"></i>
        <span>Calon Pelanggan</span>
      </a>

      @can('viewAny', \App\Models\SkData::class)
        <a href="{{ route('sk.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('sk.*') }}">
          <i class="fa-solid fa-screwdriver-wrench w-5 text-center"></i>
          <span>SK</span>
        </a>
      @endcan

      @can('viewAny', \App\Models\SrData::class)
        <a href="{{ route('sr.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('sr.*') }}">
          <i class="fa-solid fa-water w-5 text-center"></i>
          <span>SR</span>
        </a>
      @endcan

      @if($canPhotos)
        <a href="{{ route('photos.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('photos.*') }}">
          <i class="fa-regular fa-images w-5 text-center"></i>
          <span>Photo Approvals</span>
        </a>
      @endif

      <a href="{{ route('notifications.index') }}"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('notifications.*') }}">
        <i class="fa-regular fa-bell w-5 text-center"></i>
        <span>Notifikasi</span>
      </a>
    </nav>
  </div>
</aside>

{{-- Mobile drawer --}}
<div class="md:hidden" x-show="sidebarOpen" x-cloak>
  <div class="fixed inset-0 bg-black/40 z-40" @click="sidebarOpen=false"></div>
  <aside class="fixed z-50 left-0 top-0 bottom-0 w-[var(--sidebar-w)] bg-white border-r border-gray-200 p-4"
         x-trap.noscroll="sidebarOpen" x-transition>
    <div class="flex items-center justify-between mb-4">
      <div class="font-semibold">{{ config('app.name','Aergas') }}</div>
      <button class="w-9 h-9 inline-flex items-center justify-center rounded-md hover:bg-gray-100"
              @click="sidebarOpen=false">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <nav class="space-y-1">
      <a href="{{ route('dashboard') }}" @click="sidebarOpen=false"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('dashboard') }}">
        <i class="fa-solid fa-gauge-high w-5 text-center"></i>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('customers.index') }}" @click="sidebarOpen=false"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('customers.*') }}">
        <i class="fa-solid fa-users w-5 text-center"></i>
        <span>Calon Pelanggan</span>
      </a>

      @can('viewAny', \App\Models\SkData::class)
        <a href="{{ route('sk.index') }}" @click="sidebarOpen=false"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('sk.*') }}">
          <i class="fa-solid fa-screwdriver-wrench w-5 text-center"></i>
          <span>SK</span>
        </a>
      @endcan

      @can('viewAny', \App\Models\SrData::class)
        <a href="{{ route('sr.index') }}" @click="sidebarOpen=false"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('sr.*') }}">
          <i class="fa-solid fa-water w-5 text-center"></i>
          <span>SR</span>
        </a>
      @endcan

      @if($canPhotos)
        <a href="{{ route('photos.index') }}" @click="sidebarOpen=false"
           class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('photos.*') }}">
          <i class="fa-regular fa-images w-5 text-center"></i>
          <span>Photo Approvals</span>
        </a>
      @endif

      <a href="{{ route('notifications.index') }}" @click="sidebarOpen=false"
         class="flex items-center gap-2 px-3 py-2 rounded-md {{ $isActive('notifications.*') }}">
        <i class="fa-regular fa-bell w-5 text-center"></i>
        <span>Notifikasi</span>
      </a>
    </nav>
  </aside>
</div>
