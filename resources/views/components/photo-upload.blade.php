@props([
    'fieldName' => '',
    'fieldLabel' => '',
    'module' => '',
    'reffId' => '',
    'currentPhoto' => null,
    'required' => false,
    'accept' => 'image/jpeg,image/png,image/jpg,image/gif,image/webp,application/pdf',
    'maxSize' => 20480, // 20MB in KB
    'uploadUrl' => '',
    'deleteUrl' => '',
    'previewUrl' => null,
    'status' => null,
    'approvalData' => null
])

@php
    $statusConfig = [
        'ai_pending' => ['color' => 'yellow', 'icon' => 'fas fa-clock', 'text' => 'AI Processing'],
        'ai_approved' => ['color' => 'blue', 'icon' => 'fas fa-robot', 'text' => 'AI Approved'],
        'ai_rejected' => ['color' => 'red', 'icon' => 'fas fa-times', 'text' => 'AI Rejected'],
        'tracer_pending' => ['color' => 'orange', 'icon' => 'fas fa-user-check', 'text' => 'Tracer Review'],
        'tracer_approved' => ['color' => 'green', 'icon' => 'fas fa-check', 'text' => 'Tracer Approved'],
        'tracer_rejected' => ['color' => 'red', 'icon' => 'fas fa-times', 'text' => 'Tracer Rejected'],
        'cgp_pending' => ['color' => 'purple', 'icon' => 'fas fa-user-tie', 'text' => 'CGP Review'],
        'cgp_approved' => ['color' => 'green', 'icon' => 'fas fa-check-circle', 'text' => 'CGP Approved'],
        'cgp_rejected' => ['color' => 'red', 'icon' => 'fas fa-times-circle', 'text' => 'CGP Rejected']
    ];

    $currentStatus = $status ? ($statusConfig[$status] ?? null) : null;
    $hasPhoto = $currentPhoto || $previewUrl;
    $uniqueId = 'photo_' . $fieldName . '_' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 p-4']) }}
     x-data="photoUpload('{{ $uniqueId }}', '{{ $uploadUrl }}', '{{ $deleteUrl }}')">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-900">
                {{ $fieldLabel }}
                @if($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>
            @if($module && $reffId)
                <p class="text-xs text-gray-500 mt-1">{{ $module }} | {{ $reffId }}</p>
            @endif
        </div>

        @if($currentStatus)
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-{{ $currentStatus['color'] }}-500 rounded-full"></div>
                <span class="text-xs font-medium text-{{ $currentStatus['color'] }}-700">
                    {{ $currentStatus['text'] }}
                </span>
            </div>
        @endif
    </div>

    <!-- Upload Area -->
    <div class="relative">
        @if($hasPhoto)
            <!-- Photo Preview -->
            <div class="relative group">
                @if($currentPhoto && str_ends_with($currentPhoto, '.pdf'))
                    <!-- PDF Preview -->
                    <div class="w-full h-32 bg-red-50 border border-red-200 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-file-pdf text-red-500 text-3xl mb-2"></i>
                            <p class="text-sm text-red-700 font-medium">PDF Document</p>
                        </div>
                    </div>
                @else
                    <!-- Image Preview -->
                    <img src="{{ $previewUrl ?? $currentPhoto }}"
                         alt="{{ $fieldLabel }}"
                         class="w-full h-48 object-cover rounded-lg border border-gray-200">
                @endif

                <!-- Overlay Actions -->
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-300 rounded-lg flex items-center justify-center">
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex space-x-2">
                        <!-- View Full Size -->
                        @if($currentPhoto)
                            <button type="button"
                                    onclick="window.open('{{ $currentPhoto }}', '_blank')"
                                    class="px-3 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-colors text-sm">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                        @endif

                        <!-- Replace Photo -->
                        <button type="button"
                                @click="$refs.fileInput.click()"
                                class="px-3 py-2 bg-aergas-orange text-white rounded-lg hover:bg-aergas-navy transition-colors text-sm">
                            <i class="fas fa-camera mr-1"></i> Replace
                        </button>

                        <!-- Delete Photo -->
                        @if($deleteUrl)
                            <button type="button"
                                    @click="deletePhoto()"
                                    class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm">
                                <i class="fas fa-trash mr-1"></i> Delete
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- Upload Dropzone -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-aergas-orange transition-colors"
                 @dragover.prevent="dragover = true"
                 @dragleave.prevent="dragover = false"
                 @drop.prevent="handleDrop($event)"
                 :class="{ 'border-aergas-orange bg-aergas-orange/5': dragover }">

                <div class="space-y-4">
                    <div class="flex justify-center">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                    </div>

                    <div>
                        <p class="text-lg font-medium text-gray-900">Upload {{ $fieldLabel }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Drag and drop or
                            <button type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-aergas-orange hover:text-aergas-navy font-medium">
                                browse files
                            </button>
                        </p>
                    </div>

                    <div class="text-xs text-gray-400">
                        <p>Max size: {{ number_format($maxSize / 1024, 1) }}MB</p>
                        <p>Formats: JPG, PNG, GIF, WebP, PDF</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Hidden File Input -->
        <input type="file"
               x-ref="fileInput"
               accept="{{ $accept }}"
               @change="handleFileSelect($event)"
               class="hidden">

        <!-- Upload Progress -->
        <div x-show="uploading"
             x-cloak
             class="absolute inset-0 bg-white bg-opacity-90 rounded-lg flex items-center justify-center">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-aergas-orange mx-auto mb-2"></div>
                <p class="text-sm text-gray-600">Uploading...</p>
                <div class="w-48 bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-aergas-orange h-2 rounded-full transition-all duration-300"
                         :style="`width: ${uploadProgress}%`"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Information -->
    @if($approvalData)
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900">Approval Status</h4>

                    <!-- AI Validation -->
                    @if(isset($approvalData['ai_confidence_score']))
                        <div class="mt-2 flex items-center space-x-2">
                            <i class="fas fa-robot text-purple-500"></i>
                            <span class="text-sm text-gray-600">
                                AI Confidence: {{ $approvalData['ai_confidence_score'] }}%
                            </span>
                        </div>
                    @endif

                    <!-- Rejection Reason -->
                    @if(isset($approvalData['rejection_reason']))
                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                            <p class="text-sm text-red-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ $approvalData['rejection_reason'] }}
                            </p>
                        </div>
                    @endif

                    <!-- Tracer Notes -->
                    @if(isset($approvalData['tracer_notes']))
                        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded">
                            <p class="text-xs text-blue-600 font-medium">Tracer Notes:</p>
                            <p class="text-sm text-blue-700">{{ $approvalData['tracer_notes'] }}</p>
                        </div>
                    @endif

                    <!-- CGP Notes -->
                    @if(isset($approvalData['cgp_notes']))
                        <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                            <p class="text-xs text-green-600 font-medium">CGP Notes:</p>
                            <p class="text-sm text-green-700">{{ $approvalData['cgp_notes'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    <div x-show="error" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
            <p class="text-sm text-red-700" x-text="error"></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function photoUpload(id, uploadUrl, deleteUrl) {
    return {
        uploading: false,
        uploadProgress: 0,
        dragover: false,
        error: null,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.uploadFile(file);
            }
        },

        handleDrop(event) {
            this.dragover = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                this.uploadFile(file);
            }
        },

        async uploadFile(file) {
            // Validate file
            if (!this.validateFile(file)) {
                return;
            }

            this.uploading = true;
            this.uploadProgress = 0;
            this.error = null;

            const formData = new FormData();
            formData.append('photo', file);
            formData.append('photo_field', '{{ $fieldName }}');

            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.showToast('success', 'Photo uploaded successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.error = result.message || 'Upload failed';
                }
            } catch (error) {
                console.error('Upload error:', error);
                this.error = 'Network error occurred';
            } finally {
                this.uploading = false;
            }
        },

        async deletePhoto() {
            if (!confirm('Are you sure you want to delete this photo?')) {
                return;
            }

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.showToast('success', 'Photo deleted successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.error = result.message || 'Delete failed';
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.error = 'Network error occurred';
            }
        },

        validateFile(file) {
            // Size validation ({{ $maxSize }}KB = {{ $maxSize * 1024 }} bytes)
            if (file.size > {{ $maxSize * 1024 }}) {
                this.error = `File size exceeds {{ number_format($maxSize / 1024, 1) }}MB limit`;
                return false;
            }

            // Type validation
            const allowedTypes = '{{ $accept }}'.split(',');
            const fileType = file.type;
            const isValidType = allowedTypes.some(type =>
                type.trim() === fileType ||
                (type.includes('/*') && fileType.startsWith(type.split('/')[0]))
            );

            if (!isValidType) {
                this.error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or PDF files.';
                return false;
            }

            return true;
        }
    }
}
</script>
@endpush
