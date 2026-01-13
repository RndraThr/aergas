@extends('layouts.app')

@section('title', 'Buat Test Package Baru')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Buat Test Package Baru</h1>
                <a href="{{ route('jalur.test-package.index') }}" class="text-gray-600 hover:text-gray-900">
                    &larr; Kembali
                </a>
            </div>

            <div class="bg-white rounded-lg shadow p-8">
                <form action="{{ route('jalur.test-package.store') }}" method="POST">
                    @csrf

                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">Cluster <span
                                    class="text-red-500">*</span></label>
                            <select id="cluster_id" name="cluster_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                                required onchange="loadLines(this.value)">
                                <option value="">Pilih Cluster</option>
                                @foreach($clusters as $cluster)
                                    <option value="{{ $cluster->id }}">{{ $cluster->nama_cluster }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="test_package_code" class="block text-sm font-medium text-gray-700 mb-2">Kode Test
                                Package <span class="text-red-500">*</span></label>
                            <input type="text" id="test_package_code" name="test_package_code"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                                placeholder="Contoh: TP-KRG-001" required>
                        </div>
                    </div>

                    <!-- Line Selection -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Line Number</h3>
                        <p class="text-sm text-gray-500 mb-4">Hanya menampilkan Line Number yang Lowering-nya sudah
                            di-approve oleh CGP dan belum masuk paket test lain.</p>

                        <div id="loading-lines" class="hidden text-center py-4 text-gray-500">
                            Loading Lines...
                        </div>

                        <div id="lines-container" class="border rounded-md p-4 max-h-96 overflow-y-auto bg-gray-50">
                            <p class="text-gray-500 text-center italic">Pilih Cluster terlebih dahulu untuk memuat daftar
                                Line Number.</p>
                        </div>
                        @error('line_number_ids')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-300">
                            Buat Test Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        async function loadLines(clusterId) {
            const container = document.getElementById('lines-container');
            const loading = document.getElementById('loading-lines');

            if (!clusterId) {
                container.innerHTML = '<p class="text-gray-500 text-center italic">Pilih Cluster terlebih dahulu.</p>';
                return;
            }

            loading.classList.remove('hidden');
            container.innerHTML = '';

            try {
                const response = await fetch(`{{ url('jalur/test-packages/available-lines') }}/${clusterId}`);
                const lines = await response.json();

                loading.classList.add('hidden');

                if (lines.length === 0) {
                    container.innerHTML = '<p class="text-yellow-600 text-center">Tidak ada Line Number yang Eligible (ACC CGP) untuk cluster ini.</p>';
                    return;
                }

                // Add "Check All" functionality
                const headerDiv = document.createElement('div');
                headerDiv.className = 'flex items-center mb-3 pb-2 border-b';
                headerDiv.innerHTML = `
                <input type="checkbox" id="check-all" onchange="toggleAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                <label for="check-all" class="ml-2 text-sm font-semibold text-gray-700 cursor-pointer">Pilih Semua (${lines.length} Lines)</label>
            `;
                container.appendChild(headerDiv);

                const listDiv = document.createElement('div');
                listDiv.className = 'grid grid-cols-2 md:grid-cols-3 gap-3';

                lines.forEach(line => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-start p-2 hover:bg-white rounded border border-transparent hover:border-gray-200';
            wrapper.innerHTML = `
                <input type="checkbox" name="line_number_ids[]" value="${line.id}" class="line-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 mt-1">
                <div class="ml-2 flex flex-col">
                    <label class="text-sm font-medium text-gray-700 cursor-pointer">${line.line_number}</label>
                    <span class="text-xs text-gray-500">
                        Ø ${line.diameter || '-'} | L: ${line.estimasi_panjang || 0}m
                    </span>
                </div>
            `;
            listDiv.appendChild(wrapper);
        });

                container.appendChild(listDiv);

            } catch (error) {
                console.error(error);
                loading.classList.add('hidden');
                container.innerHTML = '<p class="text-red-500 text-center">Gagal memuat list line number.</p>';
            }
        }

        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.line-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>
@endsection