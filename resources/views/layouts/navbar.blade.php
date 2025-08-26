<header class="bg-white border-b border-gray-200">
  <div class="mx-auto max-w-7xl px-4">
    <div class="flex h-14 items-center justify-between">
      <div class="flex items-center gap-2">
        <button class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100"
                @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
          <i class="fa-solid fa-bars"></i>
        </button>
        <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight">
          {{ config('app.name','Aergas') }}
        </a>
      </div>

      <div class="flex items-center gap-3">
        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}"
           class="relative inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100"
           title="Notifikasi">
          <i class="fa-regular fa-bell"></i>
          {{-- contoh badge (opsional). Isi jumlah unread via view composer/section --}}
          @isset($unreadCount)
            @if($unreadCount > 0)
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $unreadCount }}</span>
            @endif
          @endisset
        </a>

        {{-- User menu --}}
        <div x-data="{ open: false }" class="relative">
          <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-100"
                  @click="open = !open">
            <i class="fa-regular fa-circle-user text-lg"></i>
            <span class="hidden sm:inline">{{ auth()->user()->full_name ?? auth()->user()->name ?? 'User' }}</span>
            <i class="fa-solid fa-chevron-down text-xs"></i>
          </button>
          <div x-cloak x-show="open" @click.outside="open=false"
               class="absolute right-0 mt-2 w-56 rounded-md bg-white shadow border border-gray-200 z-20">
            <div class="p-3 border-b">
              <div class="text-sm font-medium">{{ auth()->user()->email ?? '-' }}</div>
              @if(property_exists(auth()->user(), 'role') || method_exists(auth()->user(), 'getRoleNames'))
                <div class="text-xs text-gray-500 mt-0.5">
                  @if(method_exists(auth()->user(), 'getRoleNames'))
                    {{ implode(', ', auth()->user()->getRoleNames()->toArray()) }}
                  @else
                    {{ auth()->user()->role ?? '-' }}
                  @endif
                </div>
              @endif
            </div>
            <div class="py-1">
              <a href="{{ route('auth.me') }}" class="block px-3 py-2 text-sm hover:bg-gray-50">
                <i class="fa-regular fa-id-badge mr-2"></i> Profil
              </a>
              <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm hover:bg-gray-50">
                <i class="fa-solid fa-gauge-high mr-2"></i> Dashboard
              </a>
            </div>
            <div class="py-1 border-t">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Logout
                </button>
            </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
