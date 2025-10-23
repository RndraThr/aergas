{{-- Smart Floating Action Button (FAB) - Role & Context Aware --}}
@php
    $currentRoute = Route::currentRouteName();
    $user = auth()->user();

    // Determine current context
    $isOnDashboard = $currentRoute === 'dashboard';
    $isOnCustomers = str_contains($currentRoute, 'customers') || str_contains($currentRoute, 'calon-pelanggan');
    $isOnSK = str_contains($currentRoute, 'sk') && !str_contains($currentRoute, 'berita-acara');
    $isOnSR = str_contains($currentRoute, 'sr') && !str_contains($currentRoute, 'berita-acara');
    $isOnGasIn = str_contains($currentRoute, 'gas-in');
    $isOnApprovals = str_contains($currentRoute, 'approvals');
    $isOnPhotos = str_contains($currentRoute, 'photos');
    $isOnJalur = str_contains($currentRoute, 'jalur');

    // Detect if on create/edit pages
    $isOnCreatePage = str_contains($currentRoute, '.create');
    $isOnEditPage = str_contains($currentRoute, '.edit');
    $isOnFormPage = $isOnCreatePage || $isOnEditPage;

    // Specific create page detection
    $isOnSKCreate = $currentRoute === 'sk.create';
    $isOnSRCreate = $currentRoute === 'sr.create';
    $isOnGasInCreate = $currentRoute === 'gas-in.create';
    $isOnCustomerCreate = str_contains($currentRoute, 'customers.create');

    // Define available actions per role
    $actions = [];
    $hasBasicAccess = false; // Track if user has basic module access roles

    // SUPER ADMIN & ADMIN - Full access (highest priority)
    if($user->hasAnyRole(['super_admin', 'admin'])) {
        if(!$isOnDashboard) $actions[] = ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-home', 'color' => 'gray'];
        if(!$isOnCustomers) $actions[] = ['route' => 'customers.index', 'label' => 'Customers', 'icon' => 'fa-users', 'color' => 'gray'];
        if(!$isOnSK) $actions[] = ['route' => 'sk.index', 'label' => 'SK Module', 'icon' => 'fa-tasks', 'color' => 'green'];
        if(!$isOnSR) $actions[] = ['route' => 'sr.index', 'label' => 'SR Module', 'icon' => 'fa-route', 'color' => 'yellow'];
        if(!$isOnGasIn) $actions[] = ['route' => 'gas-in.index', 'label' => 'Gas In', 'icon' => 'fa-gas-pump', 'color' => 'orange'];
        if(!$isOnApprovals) $actions[] = ['route' => 'approvals.cgp.index', 'label' => 'CGP Approval', 'icon' => 'fa-check-circle', 'color' => 'purple'];
        if(!$isOnPhotos) $actions[] = ['route' => 'photos.index', 'label' => 'Photo Review', 'icon' => 'fa-clipboard-check', 'color' => 'indigo'];
        if(!$isOnJalur) $actions[] = ['route' => 'jalur.index', 'label' => 'Jalur', 'icon' => 'fa-road', 'color' => 'teal'];

        // Create actions - tampil dari mana saja selama TIDAK di create page (untuk super_admin/admin bisa create dari mana saja)
        if(!$isOnCustomerCreate) $actions[] = ['route' => 'customers.create', 'label' => 'Add Customer', 'icon' => 'fa-user-plus', 'color' => 'blue'];
        if(!$isOnSKCreate) $actions[] = ['route' => 'sk.create', 'label' => 'Create SK', 'icon' => 'fa-fire', 'color' => 'green'];
        if(!$isOnSRCreate) $actions[] = ['route' => 'sr.create', 'label' => 'Create SR', 'icon' => 'fa-plus', 'color' => 'yellow'];
        if(!$isOnGasInCreate) $actions[] = ['route' => 'gas-in.create', 'label' => 'Create Gas In', 'icon' => 'fa-plus', 'color' => 'orange'];
    }

    // Jika bukan super_admin/admin, build actions berdasarkan kombinasi role (MULTI-ROLE SUPPORT)
    else {
        // Dashboard access - hanya untuk role yang punya akses
        if($user->hasAnyRole(['cgp', 'tracer', 'jalur'])) {
            if(!$isOnDashboard) $actions[] = ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-home', 'color' => 'gray'];
        }

        // Customers - semua role bisa akses
        if(!$isOnCustomers) $actions[] = ['route' => 'customers.index', 'label' => 'Customers', 'icon' => 'fa-users', 'color' => 'gray'];

        // CGP Role - Hanya Approval (CGP tidak punya akses ke Jalur Module)
        if($user->hasRole('cgp')) {
            if(!$isOnApprovals) $actions[] = ['route' => 'approvals.cgp.index', 'label' => 'CGP Approval', 'icon' => 'fa-check-circle', 'color' => 'purple'];
        }

        // Tracer Role - Photos, Approval & All Modules
        if($user->hasRole('tracer')) {
            if(!$isOnPhotos) $actions[] = ['route' => 'photos.index', 'label' => 'Photo Review', 'icon' => 'fa-clipboard-check', 'color' => 'indigo'];
            if(!$isOnApprovals) $actions[] = ['route' => 'approvals.tracer.index', 'label' => 'Tracer Approval', 'icon' => 'fa-check-circle', 'color' => 'purple'];

            // Tracer punya akses ke semua module (SK, SR, Gas In)
            if(!$isOnSK) $actions[] = ['route' => 'sk.index', 'label' => 'SK Module', 'icon' => 'fa-tasks', 'color' => 'green'];
            if(!$isOnSR) $actions[] = ['route' => 'sr.index', 'label' => 'SR Module', 'icon' => 'fa-route', 'color' => 'yellow'];
            if(!$isOnGasIn) $actions[] = ['route' => 'gas-in.index', 'label' => 'Gas In', 'icon' => 'fa-gas-pump', 'color' => 'orange'];

            // Add customer - tampil dari mana saja selama TIDAK di create page
            if(!$isOnCustomerCreate) {
                $actions[] = ['route' => 'customers.create', 'label' => 'Add Customer', 'icon' => 'fa-user-plus', 'color' => 'blue'];
            }
        }

        // Jalur Role
        if($user->hasRole('jalur')) {
            if(!$isOnJalur) $actions[] = ['route' => 'jalur.index', 'label' => 'Jalur', 'icon' => 'fa-road', 'color' => 'teal'];
        }

        // SK Officer Role
        if($user->hasRole('sk')) {
            // Jika di SK create page, tampilkan menu ke index
            if($isOnSKCreate) {
                $actions[] = ['route' => 'sk.index', 'label' => 'SK Module', 'icon' => 'fa-tasks', 'color' => 'green'];
            } else {
                // Jika tidak di SK page, tampilkan menu SK Module
                if(!$isOnSK) $actions[] = ['route' => 'sk.index', 'label' => 'SK Module', 'icon' => 'fa-tasks', 'color' => 'green'];
            }
            // Create SK - tampil selama TIDAK di SK create page (untuk multi-role bisa create dari mana saja)
            if(!$isOnSKCreate) {
                $actions[] = ['route' => 'sk.create', 'label' => 'Create SK', 'icon' => 'fa-fire', 'color' => 'green'];
            }
        }

        // SR Officer Role
        if($user->hasRole('sr')) {
            // Jika di SR create page, tampilkan menu ke index
            if($isOnSRCreate) {
                $actions[] = ['route' => 'sr.index', 'label' => 'SR Module', 'icon' => 'fa-route', 'color' => 'yellow'];
            } else {
                // Jika tidak di SR page, tampilkan menu SR Module
                if(!$isOnSR) $actions[] = ['route' => 'sr.index', 'label' => 'SR Module', 'icon' => 'fa-route', 'color' => 'yellow'];
            }
            // Create SR - tampil selama TIDAK di SR create page (untuk multi-role bisa create dari mana saja)
            if(!$isOnSRCreate) {
                $actions[] = ['route' => 'sr.create', 'label' => 'Create SR', 'icon' => 'fa-plus', 'color' => 'yellow'];
            }
        }

        // Gas In Officer Role
        if($user->hasRole('gas_in')) {
            // Jika di Gas In create page, tampilkan menu ke index
            if($isOnGasInCreate) {
                $actions[] = ['route' => 'gas-in.index', 'label' => 'Gas In', 'icon' => 'fa-gas-pump', 'color' => 'orange'];
            } else {
                // Jika tidak di Gas In page, tampilkan menu Gas In Module
                if(!$isOnGasIn) $actions[] = ['route' => 'gas-in.index', 'label' => 'Gas In', 'icon' => 'fa-gas-pump', 'color' => 'orange'];
            }
            // Create Gas In - tampil selama TIDAK di Gas In create page (untuk multi-role bisa create dari mana saja)
            if(!$isOnGasInCreate) {
                $actions[] = ['route' => 'gas-in.create', 'label' => 'Create Gas In', 'icon' => 'fa-plus', 'color' => 'orange'];
            }
        }

        // Remove duplicate actions berdasarkan route
        $uniqueActions = [];
        $seenRoutes = [];
        foreach($actions as $action) {
            if(!in_array($action['route'], $seenRoutes)) {
                $uniqueActions[] = $action;
                $seenRoutes[] = $action['route'];
            }
        }
        $actions = $uniqueActions;
    }

    // Color mapping
    $colorClasses = [
        'blue' => 'from-blue-500 to-blue-600',
        'green' => 'from-green-500 to-green-600',
        'yellow' => 'from-yellow-500 to-yellow-600',
        'orange' => 'from-orange-500 to-orange-600',
        'red' => 'from-red-500 to-red-600',
        'purple' => 'from-purple-500 to-purple-600',
        'indigo' => 'from-indigo-500 to-indigo-600',
        'pink' => 'from-pink-500 to-pink-600',
        'gray' => 'from-gray-500 to-gray-600',
        'teal' => 'from-teal-500 to-teal-600',
    ];

    $hoverClasses = [
        'blue' => 'group-hover:bg-blue-50',
        'green' => 'group-hover:bg-green-50',
        'yellow' => 'group-hover:bg-yellow-50',
        'orange' => 'group-hover:bg-orange-50',
        'red' => 'group-hover:bg-red-50',
        'purple' => 'group-hover:bg-purple-50',
        'indigo' => 'group-hover:bg-indigo-50',
        'pink' => 'group-hover:bg-pink-50',
        'gray' => 'group-hover:bg-gray-50',
        'teal' => 'group-hover:bg-teal-50',
    ];
@endphp

@if(count($actions) > 0)
<!-- Smart FAB - Role & Context Aware -->
<div x-data="{ quickActionsOpen: false }" class="fixed bottom-6 right-6 z-[1100]">
    <!-- Quick Actions Menu -->
    <div x-show="quickActionsOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="absolute bottom-20 right-0 bg-transparent"
         style="display: none;">

        <div class="flex flex-col items-end space-y-2 p-2">
            @foreach($actions as $action)
            <div @click="window.location.href='{{ route($action['route']) }}'"
                 class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap {{ $hoverClasses[$action['color']] }}">
                    {{ $action['label'] }}
                </span>
                <div class="w-12 h-12 bg-gradient-to-br {{ $colorClasses[$action['color']] }} rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                    <i class="fas {{ $action['icon'] }} text-white text-base"></i>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Floating Action Button -->
    <button @click="quickActionsOpen = !quickActionsOpen"
            class="w-16 h-16 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full shadow-2xl hover:shadow-3xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 active:scale-95">
        <i class="fas fa-bolt text-white text-2xl" x-show="!quickActionsOpen"></i>
        <i class="fas fa-times text-white text-2xl" x-show="quickActionsOpen" style="display: none;"></i>
    </button>
</div>
@endif
