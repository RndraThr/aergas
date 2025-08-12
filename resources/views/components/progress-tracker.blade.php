@props([
    'currentStep' => 'validasi',
    'customerData' => null,
    'showDetails' => true,
    'compact' => false,
    'orientation' => 'horizontal' // horizontal, vertical
])

@php
    $steps = [
        'validasi' => [
            'label' => 'Validasi',
            'description' => 'Customer registration validation',
            'icon' => 'fas fa-user-check',
            'color' => 'blue'
        ],
        'sk' => [
            'label' => 'SK',
            'description' => 'Stove installation',
            'icon' => 'fas fa-fire',
            'color' => 'green'
        ],
        'sr' => [
            'label' => 'SR',
            'description' => 'Service route connection',
            'icon' => 'fas fa-route',
            'color' => 'yellow'
        ],
        'mgrt' => [
            'label' => 'MGRT',
            'description' => 'Gas meter installation',
            'icon' => 'fas fa-tachometer-alt',
            'color' => 'purple'
        ],
        'gas_in' => [
            'label' => 'Gas In',
            'description' => 'Gas commissioning',
            'icon' => 'fas fa-gas-pump',
            'color' => 'red'
        ],
        'jalur_pipa' => [
            'label' => 'Jalur Pipa',
            'description' => 'Pipeline installation',
            'icon' => 'fas fa-project-diagram',
            'color' => 'indigo'
        ],
        'penyambungan' => [
            'label' => 'Penyambungan',
            'description' => 'Final connection',
            'icon' => 'fas fa-link',
            'color' => 'cyan'
        ],
        'done' => [
            'label' => 'Completed',
            'description' => 'Installation completed',
            'icon' => 'fas fa-check-circle',
            'color' => 'green'
        ]
    ];

    $stepKeys = array_keys($steps);
    $currentIndex = array_search($currentStep, $stepKeys);

    // Calculate progress percentage
    $progressPercentage = $currentIndex !== false ? (($currentIndex + 1) / count($stepKeys)) * 100 : 0;
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 p-4']) }}>
    @if(!$compact)
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Installation Progress</h3>
                @if($customerData)
                    <p class="text-sm text-gray-600">{{ $customerData['nama_pelanggan'] ?? 'Customer' }} | {{ $customerData['reff_id_pelanggan'] ?? 'REF' }}</p>
                @endif
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-aergas-navy">{{ number_format($progressPercentage, 0) }}%</div>
                <div class="text-xs text-gray-500">Complete</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
    @endif

    <!-- Steps -->
    @if($orientation === 'horizontal')
        <!-- Horizontal Layout -->
        <div class="flex {{ $compact ? 'space-x-2' : 'justify-between' }} overflow-x-auto pb-2">
            @foreach($steps as $stepKey => $step)
                @php
                    $stepIndex = array_search($stepKey, $stepKeys);
                    $isCompleted = $stepIndex < $currentIndex;
                    $isCurrent = $stepKey === $currentStep;
                    $isUpcoming = $stepIndex > $currentIndex;
                @endphp

                <div class="flex flex-col items-center {{ $compact ? 'min-w-0' : 'flex-1' }} group">
                    <!-- Step Circle -->
                    <div class="relative flex items-center justify-center w-{{ $compact ? '8' : '12' }} h-{{ $compact ? '8' : '12' }} rounded-full border-2 transition-all duration-300
                                {{ $isCompleted ? 'bg-green-500 border-green-500 text-white' :
                                   ($isCurrent ? 'bg-aergas-orange border-aergas-orange text-white' :
                                   'bg-gray-100 border-gray-300 text-gray-400') }}">

                        @if($isCompleted)
                            <i class="fas fa-check text-{{ $compact ? 'sm' : 'lg' }}"></i>
                        @else
                            <i class="{{ $step['icon'] }} text-{{ $compact ? 'sm' : 'lg' }}"></i>
                        @endif

                        @if($isCurrent && !$compact)
                            <div class="absolute -inset-1 bg-aergas-orange rounded-full animate-ping opacity-25"></div>
                        @endif
                    </div>

                    @if(!$compact)
                        <!-- Step Label -->
                        <div class="mt-3 text-center">
                            <div class="text-sm font-medium {{ $isCurrent ? 'text-aergas-orange' : ($isCompleted ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $step['label'] }}
                            </div>
                            @if($showDetails)
                                <div class="text-xs text-gray-400 mt-1 max-w-20">{{ $step['description'] }}</div>
                            @endif
                        </div>
                    @else
                        <!-- Compact Label -->
                        <div class="mt-1 text-xs font-medium {{ $isCurrent ? 'text-aergas-orange' : ($isCompleted ? 'text-green-600' : 'text-gray-500') }}">
                            {{ $step['label'] }}
                        </div>
                    @endif

                    <!-- Connector Line -->
                    @if(!$loop->last)
                        <div class="absolute top-{{ $compact ? '4' : '6' }} left-{{ $compact ? '6' : '9' }} w-full h-0.5 {{ $stepIndex < $currentIndex ? 'bg-green-500' : 'bg-gray-300' }} -z-10"></div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <!-- Vertical Layout -->
        <div class="space-y-4">
            @foreach($steps as $stepKey => $step)
                @php
                    $stepIndex = array_search($stepKey, $stepKeys);
                    $isCompleted = $stepIndex < $currentIndex;
                    $isCurrent = $stepKey === $currentStep;
                    $isUpcoming = $stepIndex > $currentIndex;
                @endphp

                <div class="flex items-start space-x-4 group">
                    <!-- Step Circle with Connector -->
                    <div class="relative flex flex-col items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300
                                    {{ $isCompleted ? 'bg-green-500 border-green-500 text-white' :
                                       ($isCurrent ? 'bg-aergas-orange border-aergas-orange text-white' :
                                       'bg-gray-100 border-gray-300 text-gray-400') }}">

                            @if($isCompleted)
                                <i class="fas fa-check"></i>
                            @else
                                <i class="{{ $step['icon'] }}"></i>
                            @endif
                        </div>

                        <!-- Vertical Connector -->
                        @if(!$loop->last)
                            <div class="w-0.5 h-8 {{ $stepIndex < $currentIndex ? 'bg-green-500' : 'bg-gray-300' }} mt-2"></div>
                        @endif
                    </div>

                    <!-- Step Content -->
                    <div class="flex-1 min-w-0 pb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium {{ $isCurrent ? 'text-aergas-orange' : ($isCompleted ? 'text-green-600' : 'text-gray-500') }}">
                                    {{ $step['label'] }}
                                </h4>
                                @if($showDetails)
                                    <p class="text-xs text-gray-500 mt-1">{{ $step['description'] }}</p>
                                @endif
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-2">
                                @if($isCompleted)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i> Complete
                                    </span>
                                @elseif($isCurrent)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <i class="fas fa-clock mr-1"></i> In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        <i class="fas fa-hourglass mr-1"></i> Pending
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($showDetails && $customerData)
                            <!-- Step Details -->
                            <div class="mt-2 text-xs text-gray-600">
                                @if($stepKey === 'validasi')
                                    Status: {{ ucfirst($customerData['status'] ?? 'pending') }}
                                @elseif(isset($customerData[$stepKey . '_data']))
                                    @php $moduleData = $customerData[$stepKey . '_data']; @endphp
                                    Module Status: {{ ucfirst($moduleData['module_status'] ?? 'not_started') }}
                                @elseif($isUpcoming)
                                    Waiting for previous steps to complete
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!$compact && $showDetails)
        <!-- Summary Footer -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-gray-600">{{ $currentIndex }} Completed</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-aergas-orange rounded-full"></div>
                        <span class="text-gray-600">{{ $isCurrent ? '1 Current' : '0 Current' }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                        <span class="text-gray-600">{{ count($steps) - $currentIndex - 1 }} Remaining</span>
                    </div>
                </div>

                @if($customerData && isset($customerData['updated_at']))
                    <div class="text-xs text-gray-500">
                        Last updated: {{ \Carbon\Carbon::parse($customerData['updated_at'])->diffForHumans() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
