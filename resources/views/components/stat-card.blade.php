@props([
    'title' => '',
    'value' => 0,
    'icon' => 'fas fa-chart-bar',
    'color' => 'blue',
    'link' => null,
    'linkText' => 'View details',
    'trend' => null,
    'trendValue' => null,
    'description' => null,
    'loading' => false
])

@php
    $colorClasses = [
        'blue' => [
            'bg' => 'from-blue-500 to-blue-600',
            'text' => 'text-blue-600',
            'hover' => 'hover:text-blue-800'
        ],
        'green' => [
            'bg' => 'from-green-500 to-green-600',
            'text' => 'text-green-600',
            'hover' => 'hover:text-green-800'
        ],
        'yellow' => [
            'bg' => 'from-yellow-500 to-yellow-600',
            'text' => 'text-yellow-600',
            'hover' => 'hover:text-yellow-800'
        ],
        'purple' => [
            'bg' => 'from-purple-500 to-purple-600',
            'text' => 'text-purple-600',
            'hover' => 'hover:text-purple-800'
        ],
        'red' => [
            'bg' => 'from-red-500 to-red-600',
            'text' => 'text-red-600',
            'hover' => 'hover:text-red-800'
        ],
        'aergas' => [
            'bg' => 'from-aergas-navy to-aergas-orange',
            'text' => 'text-aergas-orange',
            'hover' => 'hover:text-aergas-navy'
        ]
    ];

    $colors = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col']) }}>
    @if($loading)
        <!-- Loading State -->
        <div class="animate-pulse flex-1 flex flex-col">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-12 h-12 bg-gray-200 rounded-xl flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
            <div class="h-3 bg-gray-200 rounded w-1/3 mt-auto"></div>
        </div>
    @else
        <div class="flex flex-col h-full">
            <!-- Icon and Stats Section - Fixed Height -->
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-12 h-12 min-w-[3rem] min-h-[3rem] bg-gradient-to-br {{ $colors['bg'] }} rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <i class="{{ $icon }} text-white text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-gray-600 text-sm font-medium leading-tight mb-1 truncate">{{ $title }}</div>
                    <div class="flex items-center space-x-2">
                        <div class="text-2xl font-bold text-gray-900 leading-tight">{{ number_format($value) }}</div>
                        @if($trend && $trendValue)
                            <div class="flex items-center text-xs {{ $trend === 'up' ? 'text-green-600' : ($trend === 'down' ? 'text-red-600' : 'text-gray-500') }}">
                                <i class="fas fa-arrow-{{ $trend === 'up' ? 'up' : ($trend === 'down' ? 'down' : 'right') }} mr-1"></i>
                                {{ $trendValue }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Description Section - Fixed Height -->
            @if($description)
                <div class="mb-3 h-10 flex items-start">
                    <p class="text-xs text-gray-500 leading-snug line-clamp-2">{{ $description }}</p>
                </div>
            @else
                <div class="mb-3 h-10"></div>
            @endif

            <!-- Link Section - Fixed Position at Bottom -->
            <div class="mt-auto">
                @if($link)
                    <a href="{{ $link }}"
                       class="inline-flex items-center {{ $colors['text'] }} {{ $colors['hover'] }} text-sm font-medium transition-colors">
                        {{ $linkText }} <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                @else
                    <span class="{{ $colors['text'] }} text-sm font-medium">{{ $linkText }}</span>
                @endif
            </div>
        </div>
    @endif
</div>
