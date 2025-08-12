@props([
    'status' => 'draft',
    'photoApproval' => null,
    'size' => 'md', // sm, md, lg
    'showDetails' => false,
    'showActions' => false,
    'canApprove' => false,
    'canReject' => false,
    'approveUrl' => null,
    'rejectUrl' => null
])

@php
    $statusConfig = [
        'draft' => [
            'color' => 'gray',
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-800',
            'icon' => 'fas fa-edit',
            'label' => 'Draft',
            'description' => 'Photo not uploaded yet'
        ],
        'ai_pending' => [
            'color' => 'yellow',
            'bg' => 'bg-yellow-100',
            'text' => 'text-yellow-800',
            'icon' => 'fas fa-clock',
            'label' => 'AI Processing',
            'description' => 'Photo being validated by AI'
        ],
        'ai_approved' => [
            'color' => 'blue',
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-800',
            'icon' => 'fas fa-robot',
            'label' => 'AI Approved',
            'description' => 'AI validation passed'
        ],
        'ai_rejected' => [
            'color' => 'red',
            'bg' => 'bg-red-100',
            'text' => 'text-red-800',
            'icon' => 'fas fa-times',
            'label' => 'AI Rejected',
            'description' => 'AI validation failed'
        ],
        'tracer_pending' => [
            'color' => 'orange',
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-800',
            'icon' => 'fas fa-user-check',
            'label' => 'Tracer Review',
            'description' => 'Waiting for tracer approval'
        ],
        'tracer_approved' => [
            'color' => 'green',
            'bg' => 'bg-green-100',
            'text' => 'text-green-800',
            'icon' => 'fas fa-check',
            'label' => 'Tracer Approved',
            'description' => 'Approved by tracer'
        ],
        'tracer_rejected' => [
            'color' => 'red',
            'bg' => 'bg-red-100',
            'text' => 'text-red-800',
            'icon' => 'fas fa-times',
            'label' => 'Tracer Rejected',
            'description' => 'Rejected by tracer'
        ],
        'cgp_pending' => [
            'color' => 'purple',
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-800',
            'icon' => 'fas fa-user-tie',
            'label' => 'CGP Review',
            'description' => 'Waiting for CGP approval'
        ],
        'cgp_approved' => [
            'color' => 'green',
            'bg' => 'bg-green-100',
            'text' => 'text-green-800',
            'icon' => 'fas fa-check-circle',
            'label' => 'CGP Approved',
            'description' => 'Final approval completed'
        ],
        'cgp_rejected' => [
            'color' => 'red',
            'bg' => 'bg-red-100',
            'text' => 'text-red-800',
            'icon' => 'fas fa-times-circle',
            'label' => 'CGP Rejected',
            'description' => 'Rejected by CGP'
        ]
    ];

    $config = $statusConfig[$status] ?? $statusConfig['draft'];

    $sizeClasses = [
        'sm' => ['badge' => 'px-2 py-1 text-xs', 'icon' => 'text-xs'],
        'md' => ['badge' => 'px-3 py-1 text-sm', 'icon' => 'text-sm'],
        'lg' => ['badge' => 'px-4 py-2 text-base', 'icon' => 'text-base']
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => 'space-y-3']) }} x-data="approvalStatus()">
    <!-- Status Badge -->
    <div class="flex items-center space-x-2">
        <span class="inline-flex items-center {{ $config['bg'] }} {{ $config['text'] }} {{ $sizeClass['badge'] }} rounded-full font-medium">
            <i class="{{ $config['icon'] }} {{ $sizeClass['icon'] }} mr-1"></i>
            {{ $config['label'] }}
        </span>

        @if($photoApproval && $photoApproval->ai_confidence_score)
            <span class="text-xs text-gray-500">
                AI: {{ $photoApproval->ai_confidence_score }}%
            </span>
        @endif

        @if($photoApproval && $photoApproval->created_at)
            <span class="text-xs text-gray-400">
                {{ $photoApproval->created_at->diffForHumans() }}
            </span>
        @endif
    </div>

    @if($showDetails && $photoApproval)
        <!-- Detailed Information -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
            <!-- AI Validation Details -->
            @if($photoApproval->ai_approved_at)
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-purple-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">AI Validation</span>
                            <span class="text-xs text-gray-500">{{ $photoApproval->ai_approved_at->format('M d, H:i') }}</span>
                        </div>
                        @if($photoApproval->ai_confidence_score)
                            <div class="flex items-center space-x-2 mt-1">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $photoApproval->ai_confidence_score }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600">{{ $photoApproval->ai_confidence_score }}%</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Tracer Review -->
            @if($photoApproval->tracer_user_id)
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-{{ $photoApproval->tracer_approved_at ? 'green' : 'red' }}-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-{{ $photoApproval->tracer_approved_at ? 'check' : 'times' }} text-{{ $photoApproval->tracer_approved_at ? 'green' : 'red' }}-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">Tracer Review</span>
                            <span class="text-xs text-gray-500">
                                {{ ($photoApproval->tracer_approved_at ?? $photoApproval->updated_at)->format('M d, H:i') }}
                            </span>
                        </div>
                        @if($photoApproval->tracerUser)
                            <p class="text-xs text-gray-600 mt-1">by {{ $photoApproval->tracerUser->name }}</p>
                        @endif
                        @if($photoApproval->tracer_notes)
                            <p class="text-sm text-gray-700 mt-2 italic">"{{ $photoApproval->tracer_notes }}"</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- CGP Review -->
            @if($photoApproval->cgp_user_id)
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-{{ $photoApproval->cgp_approved_at ? 'green' : 'red' }}-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-{{ $photoApproval->cgp_approved_at ? 'check-circle' : 'times-circle' }} text-{{ $photoApproval->cgp_approved_at ? 'green' : 'red' }}-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">CGP Review</span>
                            <span class="text-xs text-gray-500">
                                {{ ($photoApproval->cgp_approved_at ?? $photoApproval->updated_at)->format('M d, H:i') }}
                            </span>
                        </div>
                        @if($photoApproval->cgpUser)
                            <p class="text-xs text-gray-600 mt-1">by {{ $photoApproval->cgpUser->name }}</p>
                        @endif
                        @if($photoApproval->cgp_notes)
                            <p class="text-sm text-gray-700 mt-2 italic">"{{ $photoApproval->cgp_notes }}"</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Rejection Reason -->
            @if($photoApproval->rejection_reason && in_array($status, ['ai_rejected', 'tracer_rejected', 'cgp_rejected']))
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start space-x-2">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Rejection Reason</p>
                            <p class="text-sm text-red-700 mt-1">{{ $photoApproval->rejection_reason }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($showActions && ($canApprove || $canReject))
        <!-- Action Buttons -->
        <div class="flex items-center space-x-3 pt-3 border-t border-gray-200">
            @if($canApprove && $approveUrl)
                <button type="button"
                        @click="approvePhoto('{{ $approveUrl }}')"
                        :disabled="processing"
                        class="flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50">
                    <i class="fas fa-check mr-2"></i>
                    <span x-text="processing ? 'Processing...' : 'Approve'"></span>
                </button>
            @endif

            @if($canReject && $rejectUrl)
                <button type="button"
                        @click="showRejectModal = true"
                        :disabled="processing"
                        class="flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50">
                    <i class="fas fa-times mr-2"></i>
                    Reject
                </button>
            @endif
        </div>

        <!-- Reject Modal -->
        <div x-show="showRejectModal"
             x-cloak
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             @click.self="showRejectModal = false">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Photo</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                        <textarea x-model="rejectReason"
                                  placeholder="Please provide a reason for rejection..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                  rows="4"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button"
                                @click="showRejectModal = false"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="button"
                                @click="rejectPhoto('{{ $rejectUrl }}')"
                                :disabled="!rejectReason.trim() || processing"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50">
                            <span x-text="processing ? 'Processing...' : 'Reject Photo'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function approvalStatus() {
    return {
        processing: false,
        showRejectModal: false,
        rejectReason: '',

        async approvePhoto(url) {
            if (!url) return;

            this.processing = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.showToast('success', 'Photo approved successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.showToast('error', result.message || 'Approval failed');
                }
            } catch (error) {
                console.error('Approval error:', error);
                window.showToast('error', 'Network error occurred');
            } finally {
                this.processing = false;
            }
        },

        async rejectPhoto(url) {
            if (!url || !this.rejectReason.trim()) return;

            this.processing = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reason: this.rejectReason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.showToast('success', 'Photo rejected successfully');
                    this.showRejectModal = false;
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.showToast('error', result.message || 'Rejection failed');
                }
            } catch (error) {
                console.error('Rejection error:', error);
                window.showToast('error', 'Network error occurred');
            } finally {
                this.processing = false;
            }
        }
    }
}
</script>
@endpush
