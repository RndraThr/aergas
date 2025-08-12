@props([
    'chartId' => 'progress-chart-' . uniqid(),
    'type' => 'doughnut', // doughnut, pie, bar, line
    'data' => [],
    'labels' => [],
    'colors' => [],
    'title' => '',
    'height' => '300',
    'showLegend' => true,
    'showCenter' => true,
    'centerValue' => null,
    'centerLabel' => '',
    'loading' => false
])

@php
    $defaultColors = [
        '#3B82F6', // Blue
        '#10B981', // Green
        '#F59E0B', // Yellow
        '#EF4444', // Red
        '#8B5CF6', // Purple
        '#06B6D4', // Cyan
        '#84CC16', // Lime
        '#F97316'  // Orange
    ];

    $chartColors = !empty($colors) ? $colors : $defaultColors;
    $chartData = !empty($data) ? $data : [0];
    $chartLabels = !empty($labels) ? $labels : ['No data'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-lg p-6 border border-gray-100']) }}>
    @if($title)
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            @if($loading)
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-aergas-orange"></div>
            @endif
        </div>
    @endif

    @if($loading)
        <!-- Loading State -->
        <div class="animate-pulse">
            <div class="h-{{ $height }} bg-gray-200 rounded-lg flex items-center justify-center">
                <div class="text-gray-400 text-center">
                    <i class="fas fa-chart-pie text-3xl mb-2"></i>
                    <p class="text-sm">Loading chart...</p>
                </div>
            </div>
        </div>
    @else
        <!-- Chart Container -->
        <div class="relative" style="height: {{ $height }}px;">
            <canvas id="{{ $chartId }}" class="max-w-full max-h-full"></canvas>

            @if($showCenter && in_array($type, ['doughnut', 'pie']))
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        @if($centerValue !== null)
                            <div class="text-3xl font-bold text-gray-900">{{ $centerValue }}</div>
                        @endif
                        @if($centerLabel)
                            <div class="text-sm text-gray-500 font-medium">{{ $centerLabel }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($showLegend && !empty($labels))
            <!-- Custom Legend -->
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                @foreach($labels as $index => $label)
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $chartColors[$index] ?? $defaultColors[0] }};"></div>
                        <span class="text-gray-700">{{ $label }}</span>
                        @if(isset($chartData[$index]))
                            <span class="text-gray-500 text-xs">({{ number_format($chartData[$index]) }})</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>

@if(!$loading)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('{{ $chartId }}');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    const chartConfig = {
        type: '{{ $type }}',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                data: @json($chartData),
                backgroundColor: @json($chartColors),
                borderWidth: 0,
                @if(in_array($type, ['doughnut', 'pie']))
                cutout: '{{ $type === 'doughnut' ? '75%' : '0%' }}'
                @endif
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            @if(in_array($type, ['doughnut', 'pie']))
            plugins: {
                legend: {
                    display: {{ $showLegend && !$showCenter ? 'true' : 'false' }},
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            },
            @elseif($type === 'bar')
            plugins: {
                legend: {
                    display: {{ $showLegend ? 'true' : 'false' }}
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            },
            @elseif($type === 'line')
            plugins: {
                legend: {
                    display: {{ $showLegend ? 'true' : 'false' }}
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 4,
                    hoverRadius: 6
                }
            },
            @endif
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    };

    new Chart(ctx, chartConfig);
});
</script>
@endpush
@endif
