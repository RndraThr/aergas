@extends('layouts.app')

@section('title', 'Resolusi Duplikat Data Lowering')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Resolusi Duplikat Data Lowering</h1>
            <p class="text-gray-600">Pilih record yang ingin dipertahankan per grup duplikat — sisanya akan di-soft-delete</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('jalur.lowering.import.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                ← Kembali ke Import
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if($totalGroups === 0)
        <div class="bg-green-50 border-2 border-green-400 rounded-lg p-8 text-center">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-2xl font-bold text-green-800 mb-2">Tidak ada data duplikat</h2>
            <p class="text-green-700">Semua data lowering sudah unique berdasarkan kombinasi 6 field: <br>
                <code class="text-xs">line_number + tanggal + tipe_bongkaran + lowering + bongkaran + kedalaman</code>
            </p>
            <p class="text-sm text-gray-600 mt-4">
                Setelah ini aman untuk menjalankan migration unique constraint jika diinginkan.
            </p>
        </div>
    @else
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div class="text-sm text-yellow-900">
                    <p class="font-semibold">Ditemukan {{ $totalGroups }} grup duplikat — total {{ $totalRecordsToDelete }} record akan dihapus (soft-delete) jika semua diresolve.</p>
                    <ul class="list-disc list-inside mt-2 text-xs text-yellow-800">
                        <li>Pilih satu record untuk <strong>dipertahankan</strong> per grup (default: record approved / paling banyak foto / terbaru).</li>
                        <li>Record yang tidak dipilih akan di-<strong>soft-delete</strong> (masih bisa di-restore via DB).</li>
                        <li>Record <span class="px-1 bg-green-200 rounded">acc_cgp</span> / <span class="px-1 bg-blue-200 rounded">acc_tracer</span> sebaiknya dipertahankan karena sudah melewati approval.</li>
                    </ul>
                </div>
            </div>
        </div>

        <form action="{{ route('jalur.lowering.duplicates.resolve') }}" method="POST" id="resolveForm">
            @csrf

            @foreach($groups as $group)
                @php
                    $recommendedKeepId = null;
                    $approved = collect($group['records'])->firstWhere('is_approved', true);
                    if ($approved) {
                        $recommendedKeepId = $approved['id'];
                    } else {
                        $recommendedKeepId = collect($group['records'])->sortByDesc('photo_count')->sortByDesc('updated_at')->first()['id'] ?? null;
                    }
                @endphp

                <div class="bg-white rounded-lg shadow-sm border-2 border-gray-300 mb-4 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <span class="font-mono font-semibold text-gray-900">{{ $group['line_number'] }}</span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Cluster: <strong>{{ $group['cluster'] }}</strong></span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Tanggal: <strong>{{ $group['tanggal_jalur'] }}</strong></span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Tipe: <strong>{{ $group['tipe_bongkaran'] }}</strong></span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Lowering: <strong>{{ $group['records'][0]['penggelaran'] ?? '—' }}</strong></span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Bongkaran: <strong>{{ $group['records'][0]['bongkaran'] ?? '—' }}</strong></span>
                            <span class="text-gray-500">·</span>
                            <span class="text-gray-700">Kedalaman: <strong>{{ $group['records'][0]['kedalaman_lowering'] ?? '—' }}</strong></span>
                            <span class="ml-auto px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                {{ $group['total'] }} record duplikat
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-2 py-2 text-center w-12">Keep</th>
                                    <th class="px-2 py-2 text-left">ID</th>
                                    <th class="px-2 py-2 text-left">Status</th>
                                    <th class="px-2 py-2 text-left">Nama Jalan</th>
                                    <th class="px-2 py-2 text-right">Lowering</th>
                                    <th class="px-2 py-2 text-right">Bongkaran</th>
                                    <th class="px-2 py-2 text-right">Kedalaman</th>
                                    <th class="px-2 py-2 text-right">Cassing</th>
                                    <th class="px-2 py-2 text-right">Marker</th>
                                    <th class="px-2 py-2 text-right">C.Slab</th>
                                    <th class="px-2 py-2 text-right">Landasan</th>
                                    <th class="px-2 py-2 text-center">Foto</th>
                                    <th class="px-2 py-2 text-left">Created</th>
                                    <th class="px-2 py-2 text-left">Updated</th>
                                    <th class="px-2 py-2 text-left">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($group['records'] as $rec)
                                    <tr class="hover:bg-blue-50 keep-row" data-group="{{ $group['key'] }}">
                                        <td class="px-2 py-2 text-center">
                                            <input type="radio"
                                                   name="keep[{{ $group['key'] }}]"
                                                   value="{{ $rec['id'] }}"
                                                   class="keep-radio h-4 w-4 text-green-600"
                                                   {{ $rec['id'] == $recommendedKeepId ? 'checked' : '' }}
                                                   required>
                                        </td>
                                        <td class="px-2 py-2 font-mono">{{ $rec['id'] }}</td>
                                        <td class="px-2 py-2">
                                            @if($rec['is_approved'])
                                                <span class="px-2 py-0.5 bg-green-200 text-green-900 rounded font-semibold">{{ $rec['status_laporan'] }}</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-gray-200 text-gray-700 rounded">{{ $rec['status_laporan'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">{{ $rec['nama_jalan'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['penggelaran'] }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['bongkaran'] }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['kedalaman_lowering'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['cassing_quantity'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['marker_tape_quantity'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['concrete_slab_quantity'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-right">{{ $rec['landasan_quantity'] ?? '—' }}</td>
                                        <td class="px-2 py-2 text-center">
                                            @if($rec['photo_count'] > 0)
                                                <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded">{{ $rec['photo_count'] }}</span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">
                                            <div>{{ $rec['created_at'] }}</div>
                                            <div class="text-gray-500">{{ $rec['created_by'] }}</div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div>{{ $rec['updated_at'] }}</div>
                                            <div class="text-gray-500">{{ $rec['updated_by'] }}</div>
                                        </td>
                                        <td class="px-2 py-2 max-w-xs truncate" title="{{ $rec['keterangan'] }}">{{ $rec['keterangan'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="bg-white rounded-lg shadow p-6 mt-6 sticky bottom-0 border-2 border-red-300">
                <label class="flex items-start gap-3 mb-4 p-3 bg-red-50 border border-red-300 rounded cursor-pointer">
                    <input type="checkbox" id="confirmDelete" class="mt-1 h-4 w-4 text-red-600">
                    <div class="text-sm">
                        <div class="font-semibold text-red-900">Saya paham konsekuensinya</div>
                        <div class="text-red-800 text-xs mt-1">
                            Record yang tidak dipilih akan di-soft-delete. Total record yang akan dihapus: <strong id="deleteCount">{{ $totalRecordsToDelete }}</strong>.
                        </div>
                    </div>
                </label>

                <div class="flex gap-3">
                    <button type="submit"
                            id="submitBtn"
                            disabled
                            class="flex-1 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-4 rounded-md font-semibold text-lg">
                        Hapus Duplikat & Pertahankan yang Dipilih
                    </button>
                    <a href="{{ route('jalur.lowering.import.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-4 rounded-md font-semibold">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
    const confirmBox = document.getElementById('confirmDelete');
    const submitBtn = document.getElementById('submitBtn');
    confirmBox?.addEventListener('change', () => {
        submitBtn.disabled = !confirmBox.checked;
    });
</script>
@endsection
