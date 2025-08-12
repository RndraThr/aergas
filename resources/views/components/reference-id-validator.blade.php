@props([
    'name' => 'reff_id_pelanggan',
    'value' => '',
    'placeholder' => 'Enter Reference ID (e.g., REF001)',
    'required' => true,
    'validateUrl' => null,
    'createUrl' => null,
    'debounceMs' => 500
])

@php
    $validateUrl = $validateUrl ?? route('customers.validate-reff', ['reffId' => 'PLACEHOLDER']);
    $uniqueId = 'ref_validator_' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'space-y-3']) }}
     x-data="referenceValidator('{{ $uniqueId }}', '{{ $validateUrl }}', {{ $debounceMs }})">

    <!-- Input Field -->
    <div class="relative">
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            Reference ID
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>

        <div class="relative">
            <input type="text"
                   id="{{ $name }}"
                   name="{{ $name }}"
                   x-model="reffId"
                   @input="validateReference()"
                   placeholder="{{ $placeholder }}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent text-lg font-mono uppercase"
                   :class="{
                       'border-green-300 bg-green-50': validationState === 'valid',
                       'border-red-300 bg-red-50': validationState === 'invalid',
                       'border-yellow-300 bg-yellow-50': validationState === 'exists'
                   }"
                   {{ $required ? 'required' : '' }}>

            <!-- Loading Spinner -->
            <div x-show="validating"
                 class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-aergas-orange"></div>
            </div>

            <!-- Status Icons -->
            <div x-show="!validating && validationState"
                 class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <i x-show="validationState === 'valid'"
                   class="fas fa-check-circle text-green-500 text-xl"></i>
                <i x-show="validationState === 'invalid'"
                   class="fas fa-times-circle text-red-500 text-xl"></i>
                <i x-show="validationState === 'exists'"
                   class="fas fa-info-circle text-yellow-500 text-xl"></i>
            </div>
        </div>

        <!-- Format Helper -->
        <p class="text-xs text-gray-500 mt-1">
            Format: Letters and numbers only (e.g., REF001, CUST123, ABC001)
        </p>
    </div>

    <!-- Validation Messages -->
    <div x-show="validationMessage" x-cloak>
        <!-- Success Message -->
        <div x-show="validationState === 'valid'"
             class="p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-sm text-green-700 font-medium">Reference ID is available</p>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="validationState === 'invalid'"
             class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <p class="text-sm text-red-700" x-text="validationMessage"></p>
            </div>
        </div>

        <!-- Existing Customer -->
        <div x-show="validationState === 'exists'"
             class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-yellow-500 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm text-yellow-800 font-medium mb-2">Customer Found</p>
                    <div x-show="customerData" class="space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-yellow-700 font-medium">Name:</span>
                                <span class="text-yellow-800" x-text="customerData?.nama_pelanggan"></span>
                            </div>
                            <div>
                                <span class="text-yellow-700 font-medium">Status:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': customerData?.status === 'validated',
                                          'bg-blue-100 text-blue-800': customerData?.status === 'in_progress',
                                          'bg-yellow-100 text-yellow-800': customerData?.status === 'pending',
                                          'bg-red-100 text-red-800': customerData?.status === 'batal'
                                      }"
                                      x-text="customerData?.status"></span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-yellow-700 font-medium">Progress:</span>
                                <span class="text-yellow-800" x-text="customerData?.progress_status"></span>
                                <span class="text-yellow-600 text-xs" x-text="'(' + (customerData?.progress_percentage || 0) + '%)'"></span>
                            </div>
                        </div>

                        @if($createUrl)
                            <div class="flex justify-end pt-2">
                                <button type="button"
                                        @click="proceedWithExisting()"
                                        class="px-4 py-2 bg-aergas-orange text-white rounded-lg hover:bg-aergas-navy transition-colors text-sm">
                                    Continue with this customer
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Generate -->
    <div class="flex items-center justify-between pt-2">
        <button type="button"
                @click="generateReffId()"
                class="text-sm text-aergas-orange hover:text-aergas-navy font-medium transition-colors">
            <i class="fas fa-magic mr-1"></i>
            Generate ID
        </button>

        <div x-show="validationState === 'valid'" class="text-xs text-green-600 font-medium">
            ✓ Ready to proceed
        </div>
    </div>
</div>

@push('scripts')
<script>
function referenceValidator(id, validateUrl, debounceMs) {
    return {
        reffId: '{{ $value }}',
        validating: false,
        validationState: null, // 'valid', 'invalid', 'exists'
        validationMessage: '',
        customerData: null,
        debounceTimer: null,

        init() {
            if (this.reffId) {
                this.validateReference();
            }
        },

        validateReference() {
            // Clear previous timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Reset states
            this.validationState = null;
            this.validationMessage = '';
            this.customerData = null;

            // Check if input is empty
            if (!this.reffId.trim()) {
                return;
            }

            // Format input (uppercase, alphanumeric only)
            this.reffId = this.reffId.toUpperCase().replace(/[^A-Z0-9]/g, '');

            // Basic validation
            if (this.reffId.length < 3) {
                this.validationState = 'invalid';
                this.validationMessage = 'Reference ID must be at least 3 characters';
                return;
            }

            if (this.reffId.length > 20) {
                this.validationState = 'invalid';
                this.validationMessage = 'Reference ID must not exceed 20 characters';
                return;
            }

            // Debounced API validation
            this.debounceTimer = setTimeout(() => {
                this.performValidation();
            }, debounceMs);
        },

        async performValidation() {
            this.validating = true;

            try {
                const url = validateUrl.replace('PLACEHOLDER', this.reffId);
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Customer exists
                    this.validationState = 'exists';
                    this.validationMessage = 'Customer found with this Reference ID';
                    this.customerData = result.data;
                } else if (response.status === 404) {
                    // ID available
                    this.validationState = 'valid';
                    this.validationMessage = 'Reference ID is available';
                } else {
                    // Error
                    this.validationState = 'invalid';
                    this.validationMessage = result.message || 'Validation failed';
                }
            } catch (error) {
                console.error('Validation error:', error);
                this.validationState = 'invalid';
                this.validationMessage = 'Network error occurred';
            } finally {
                this.validating = false;
            }
        },

        generateReffId() {
            const prefixes = ['REF', 'CUST', 'GAS', 'AER'];
            const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
            const number = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
            this.reffId = prefix + number;
            this.validateReference();
        },

        proceedWithExisting() {
            if (this.customerData && '{{ $createUrl }}') {
                const url = '{{ $createUrl }}' + '?reff_id=' + this.reffId;
                window.location.href = url;
            }
        }
    }
}
</script>
@endpush
