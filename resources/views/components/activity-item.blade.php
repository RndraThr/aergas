@props([
    'type' => 'info',
    'title' => '',
    'description' => '',
    'time' => '',
    'user' => null,
    'icon' => null,
    'link' => null,
    'data' => []
])

@php
    $typeConfig = [
        'new' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
            'icon' => 'fas fa-plus-circle',
            'dot' => 'bg-blue-500'
        ],
        'completed' => [
            'bg' => 'bg-green-100',
            'text' => 'text-green-600',
            'icon' => 'fas fa-check-circle',
            'dot' => 'bg-green-500'
        ],
        'pending' => [
            'bg' => 'bg-yellow-100',
            'text' => 'text-yellow-600',
            'icon' => 'fas fa-clock',
            'dot' => 'bg-yellow-500'
        ],
        'rejected' => [
            'bg' => 'bg-red-100',
            'text' => 'text-red-600',
            'icon' => 'fas fa-times-circle',
            'dot' => 'bg-red-500'
        ],
        'approved' => [
            'bg' => 'bg-green-100',
            'text' => 'text-green-600',
            'icon' => 'fas fa-thumbs-up',
            'dot' => 'bg-green-500'
        ],
        'photo_upload' => [
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-600',
            'icon' => 'fas fa-camera',
            'dot' => 'bg-purple-500'
        ],
        'ai_validation' => [
            'bg' => 'bg-indigo-100',
            'text' => 'text-indigo-600',
            'icon' => 'fas fa-robot',
            'dot' => 'bg-indigo-500'
        ],
        'module_start' => [
            'bg' => 'bg-cyan-100',
            'text' => 'text-cyan-600',
            'icon' => 'fas fa-play-circle',
            'dot' => 'bg-cyan-500'
        ],
        'user_action' => [
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-600',
            'icon' => 'fas fa-user',
            'dot' => 'bg-gray-500'
        ],
        'system' => [
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-600',
            'icon' => 'fas fa-cog',
            'dot' => 'bg-orange-500'
        ],
        'info' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
            'icon' => 'fas fa-info-circle',
            'dot' => 'bg-blue-500'
        ]
    ];

    $config = $typeConfig[$type] ?? $typeConfig['info'];
    $displayIcon = $icon ?? $config['icon'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors group']) }}>
    <!-- Icon -->
    <div class="w-8 h-8 {{ $config['bg'] }} rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
        <i class="{{ $displayIcon }} {{ $config['text'] }} text-sm"></i>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                @if($link)
                    <a href="{{ $link }}" class="block hover:underline">
                @endif

                <p class="text-sm font-medium text-gray-900 leading-tight">
                    {{ $title }}
                </p>

                @if($description)
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                        {{ $description }}
                    </p>
                @endif

                @if($link)
                    </a>
                @endif

                <!-- Additional Data -->
                @if(!empty($data))
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach($data as $key => $value)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ ucfirst($key) }}: {{ $value }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <!-- Meta Information -->
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                        @if($user)
                            <div class="flex items-center space-x-1">
                                <i class="fas fa-user text-gray-400"></i>
                                <span>{{ $user }}</span>
                            </div>
                        @endif

                        @if($time)
                            <div class="flex items-center space-x-1">
                                <i class="fas fa-clock text-gray-400"></i>
                                <span>{{ $time }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Status Dot -->
                    <div class="w-2 h-2 {{ $config['dot'] }} rounded-full"></div>
                </div>
            </div>
        </div>
    </div>
</div>
