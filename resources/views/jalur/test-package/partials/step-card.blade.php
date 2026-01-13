@php
    $dateField = $step . '_date';
    $evidenceField = $step . '_evidence_path';
    $notesField = $step . '_notes';

    $isDone = !empty($testPackage->$dateField);
    $currentDate = $testPackage->$dateField; // Carbon object or null
    $evidencePath = $testPackage->$evidenceField;
    $notes = $testPackage->$notesField;
@endphp

<div class="bg-white rounded-lg shadow border-l-4 border-{{ $color }}-500 overflow-hidden" id="step-{{ $step }}">
    <div class="p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center">
                <div class="bg-{{ $color }}-100 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-{{ $color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
                    <p class="text-sm text-gray-600">{{ $description }}</p>
                </div>
            </div>
            @if($isDone)
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Selesai
                </span>
            @else
                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded">Pending</span>
            @endif
        </div>

        <div class="mt-6 border-t pt-4">
            @if($isDone)
                <!-- View Mode -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Tanggal Pelaksanaan</p>
                        <p class="font-medium text-gray-900">{{ $currentDate->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Evidence</p>
                        @if($evidencePath)
                            @php
                                $url = \Illuminate\Support\Str::startsWith($evidencePath, ['http://', 'https://'])
                                    ? $evidencePath
                                    : Storage::url($evidencePath);
                            @endphp
                            <a href="{{ $url }}" target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                Lihat File
                            </a>
                        @else
                            <span class="text-gray-400 italic text-sm">Tidak ada file</span>
                        @endif
                    </div>
                    @if($notes)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Catatan</p>
                            <p class="text-gray-700 text-sm">{{ $notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Edit Button (Optional) -->
                <div class="mt-4 flex justify-end">
                    <button onclick="document.getElementById('form-{{ $step }}').classList.toggle('hidden')"
                        class="text-xs text-gray-500 hover:text-{{ $color }}-600 underline">
                        Edit Data
                    </button>
                </div>
            @endif

            <!-- Input Form (Hidden if Done, unless Edit clicked) -->
            <form id="form-{{ $step }}" action="{{ route('jalur.test-package.update-step', $testPackage) }}"
                method="POST" enctype="multipart/form-data"
                class="{{ $isDone ? 'hidden mt-4 pt-4 border-t border-gray-100' : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="step" value="{{ $step }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pelaksanaan <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="{{ $step }}_date"
                            value="{{ $currentDate ? $currentDate->format('Y-m-d') : '' }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-{{ $color }}-500 focus:ring focus:ring-{{ $color }}-200"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Evidence (Foto/PDF)</label>
                        <input type="file" name="{{ $step }}_evidence" accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-{{ $color }}-50 file:text-{{ $color }}-700 hover:file:bg-{{ $color }}-100">
                        <p class="text-xs text-gray-400 mt-1">Maks 10MB.</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <textarea name="{{ $step }}_notes" rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-{{ $color }}-500 focus:ring focus:ring-{{ $color }}-200">{{ $notes }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-{{ $color }}-600 hover:bg-{{ $color }}-700 text-white font-bold py-2 px-4 rounded shadow-md transition duration-300 text-sm">
                        {{ $isDone ? 'Update Data' : 'Simpan & Selesai' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>