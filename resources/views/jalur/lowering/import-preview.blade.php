@extends('layouts.app')

@section('title', 'Preview Import Data Lowering')

@php
    $details = $summary['details'] ?? [];
    $hasRecall = ($summary['recall'] ?? 0) > 0;
    $hasAnyCommittable = ($summary['new'] ?? 0) + ($summary['update'] ?? 0) + ($summary['recall'] ?? 0) > 0;

    $fieldLabels = [
        'nama_jalan' => 'Nama Jalan',
        'keterangan' => 'Keterangan',
        'penggelaran' => 'Lowering (m)',
        'bongkaran' => 'Bongkaran (m)',
        'kedalaman_lowering' => 'Kedalaman (cm)',
        'cassing_quantity' => 'Cassing Qty',
        'cassing_type' => 'Cassing Type',
        'marker_tape_quantity' => 'Marker Tape Qty',
        'concrete_slab_quantity' => 'Concrete Slab Qty',
        'landasan_quantity' => 'Landasan Qty',
        'foto_evidence_penggelaran_bongkaran' => 'Foto Lowering',
        'foto_evidence_cassing' => 'Foto Cassing',
        'foto_evidence_marker_tape' => 'Foto Marker Tape',
        'foto_evidence_concrete_slab' => 'Foto Concrete Slab',
        'foto_evidence_landasan' => 'Foto Landasan',
    ];
@endphp

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Preview Import Data Lowering</h1>
            <p class="text-gray-600">Tinjau perubahan sebelum commit ke database</p>
        </div>
        <a href="{{ route('jalur.lowering.import.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
            ← Kembali
        </a>
    </div>

    {{-- Mode Indicator --}}
    <div class="mb-6 p-4 rounded-lg border-2 bg-gray-50 border-gray-300">
        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Konfigurasi Mode
        </div>
        <div class="flex flex-wrap gap-2 ml-7">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $forceUpdate ? 'bg-orange-100 text-orange-900 border border-orange-300' : 'bg-gray-100 text-gray-600 border border-gray-300' }}">
                Force Update: <strong>{{ $forceUpdate ? 'ON' : 'OFF' }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $allowRecall ? 'bg-red-100 text-red-900 border border-red-300' : 'bg-gray-100 text-gray-600 border border-gray-300' }}">
                Allow Recall: <strong>{{ $allowRecall ? 'ON' : 'OFF' }}</strong>
            </span>
        </div>
        <ul class="text-xs text-gray-700 mt-2 ml-7 space-y-0.5">
            <li>• Record draft dengan field kosong → <strong>diisi</strong> (kedua mode)</li>
            <li>• Record draft dengan field ada nilainya → {{ $forceUpdate ? 'ditimpa' : 'di-skip' }}</li>
            <li>• Record approved + ada perubahan krusial/foto → {{ $allowRecall ? 'di-RECALL ke draft' : 'di-SKIP (dilindungi)' }}</li>
        </ul>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="bg-white rounded-lg p-4 border-2 border-green-300">
            <div class="text-3xl font-bold text-green-600">{{ $summary['new'] }}</div>
            <div class="text-xs text-gray-600 mt-1">NEW</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-blue-300">
            <div class="text-3xl font-bold text-blue-600">{{ $summary['update'] }}</div>
            <div class="text-xs text-gray-600 mt-1">UPDATE</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-yellow-300">
            <div class="text-3xl font-bold text-yellow-600">{{ $summary['skip_no_change'] }}</div>
            <div class="text-xs text-gray-600 mt-1">SKIP (no change)</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-orange-300">
            <div class="text-3xl font-bold text-orange-600">{{ $summary['skip_approved'] }}</div>
            <div class="text-xs text-gray-600 mt-1">SKIP (approved)</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-red-400">
            <div class="text-3xl font-bold text-red-600">{{ $summary['recall'] }}</div>
            <div class="text-xs text-gray-600 mt-1">RECALL</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-purple-300">
            <div class="text-3xl font-bold text-purple-600">{{ $summary['duplicate_in_file'] }}</div>
            <div class="text-xs text-gray-600 mt-1">DUP IN FILE</div>
        </div>
        <div class="bg-white rounded-lg p-4 border-2 border-gray-400">
            <div class="text-3xl font-bold text-gray-600">{{ $summary['error'] }}</div>
            <div class="text-xs text-gray-600 mt-1">ERROR</div>
        </div>
    </div>

    {{-- NEW records --}}
    @if(!empty($details['new']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-green-400" open>
            <summary class="cursor-pointer text-sm font-semibold text-green-800 flex items-center gap-2">
                🟢 NEW — Akan di-insert ({{ count($details['new']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-80 overflow-y-auto border border-green-200 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-green-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal</th>
                            <th class="px-2 py-2 text-left">Tipe</th>
                            <th class="px-2 py-2 text-left">Lowering/Bongkaran</th>
                            <th class="px-2 py-2 text-center">Foto?</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['new'] as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2">{{ $row['tanggal'] }}</td>
                                <td class="px-2 py-2">{{ $row['tipe_bongkaran'] }}</td>
                                <td class="px-2 py-2">{{ $row['data']['penggelaran'] }} / {{ $row['data']['bongkaran'] }}</td>
                                <td class="px-2 py-2 text-center">{{ $row['data']['has_photos'] ? '✓' : '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- UPDATE records --}}
    @if(!empty($details['update']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-blue-400" open>
            <summary class="cursor-pointer text-sm font-semibold text-blue-800 flex items-center gap-2">
                🔵 UPDATE — Field akan berubah ({{ count($details['update']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-80 overflow-y-auto border border-blue-200 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-blue-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal / Tipe</th>
                            <th class="px-2 py-2 text-left">Status</th>
                            <th class="px-2 py-2 text-left">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['update'] as $row)
                            <tr>
                                <td class="px-2 py-2 align-top">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 align-top font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2 align-top">{{ $row['tanggal'] }}<br><span class="text-gray-500">{{ $row['tipe_bongkaran'] }}</span></td>
                                <td class="px-2 py-2 align-top"><span class="px-2 py-0.5 bg-gray-100 rounded">{{ $row['status'] }}</span></td>
                                <td class="px-2 py-2 align-top">
                                    @php
                                        $allChanges = array_merge($row['diff']['non_krusial'] ?? [], $row['diff']['krusial'] ?? [], $row['diff']['photos'] ?? []);
                                    @endphp
                                    @foreach($allChanges as $ch)
                                        <div class="mb-1">
                                            <span class="font-medium">{{ $fieldLabels[$ch['field']] ?? $ch['field'] }}:</span>
                                            <span class="text-gray-500 line-through">{{ $ch['old'] ?? '—' }}</span>
                                            →
                                            <span class="{{ $ch['will_apply'] ? 'text-green-700 font-semibold' : 'text-gray-400 italic' }}">{{ $ch['new'] ?? '—' }}</span>
                                            @if(!$ch['will_apply'])
                                                <span class="text-xs text-gray-400">(skip — field sudah terisi)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- RECALL records --}}
    @if(!empty($details['recall']))
        <details class="mb-4 bg-red-50 rounded-lg p-4 border-2 border-red-500" open>
            <summary class="cursor-pointer text-sm font-bold text-red-800 flex items-center gap-2">
                🔴 RECALL — Record approved akan kembali ke DRAFT ({{ count($details['recall']) }})
            </summary>
            <div class="mt-2 p-3 bg-red-100 border border-red-300 rounded text-xs text-red-900">
                <strong>⚠ Peringatan:</strong> Record di bawah sudah diapprove. Dengan Force Update, record akan di-recall ke status <code>draft</code>,
                semua foto-nya di-reset ke <code>tracer_pending</code>, dan butuh re-approval dari Tracer & CGP.
            </div>
            <div class="mt-3 overflow-x-auto max-h-80 overflow-y-auto border border-red-300 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-red-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal / Tipe</th>
                            <th class="px-2 py-2 text-left">Status Saat Ini</th>
                            <th class="px-2 py-2 text-left">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['recall'] as $row)
                            <tr>
                                <td class="px-2 py-2 align-top">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 align-top font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2 align-top">{{ $row['tanggal'] }}<br><span class="text-gray-500">{{ $row['tipe_bongkaran'] }}</span></td>
                                <td class="px-2 py-2 align-top">
                                    <span class="px-2 py-0.5 bg-red-200 text-red-900 rounded font-semibold">{{ $row['status'] }}</span>
                                    <div class="text-red-700 text-xs mt-1">→ akan jadi <code>draft</code></div>
                                </td>
                                <td class="px-2 py-2 align-top">
                                    @php
                                        $allChanges = array_merge($row['diff']['non_krusial'] ?? [], $row['diff']['krusial'] ?? [], $row['diff']['photos'] ?? []);
                                    @endphp
                                    @foreach($allChanges as $ch)
                                        <div class="mb-1">
                                            <span class="font-medium">{{ $fieldLabels[$ch['field']] ?? $ch['field'] }}:</span>
                                            <span class="text-gray-500 line-through">{{ $ch['old'] ?? '—' }}</span>
                                            →
                                            <span class="text-red-700 font-semibold">{{ $ch['new'] ?? '—' }}</span>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- SKIP approved --}}
    @if(!empty($details['skip_approved']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-orange-400">
            <summary class="cursor-pointer text-sm font-semibold text-orange-800 flex items-center gap-2">
                🟠 SKIP — Record approved dilindungi ({{ count($details['skip_approved']) }})
            </summary>
            <div class="mt-2 p-3 bg-orange-50 border border-orange-200 rounded text-xs text-orange-900">
                Record di bawah sudah approved dan ada perubahan di field krusial/foto. Untuk overwrite, kembali ke form dan aktifkan <strong>Force Update</strong> → record akan di-recall ke draft.
            </div>
            <div class="mt-3 overflow-x-auto max-h-80 overflow-y-auto border border-orange-200 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-orange-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal / Tipe</th>
                            <th class="px-2 py-2 text-left">Status</th>
                            <th class="px-2 py-2 text-left">Yang Coba Diubah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['skip_approved'] as $row)
                            <tr>
                                <td class="px-2 py-2 align-top">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 align-top font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2 align-top">{{ $row['tanggal'] }}<br><span class="text-gray-500">{{ $row['tipe_bongkaran'] }}</span></td>
                                <td class="px-2 py-2 align-top"><span class="px-2 py-0.5 bg-orange-200 rounded">{{ $row['status'] }}</span></td>
                                <td class="px-2 py-2 align-top">
                                    @php
                                        $allChanges = array_merge($row['diff']['krusial'] ?? [], $row['diff']['photos'] ?? []);
                                    @endphp
                                    @foreach($allChanges as $ch)
                                        <div class="mb-1">
                                            <span class="font-medium">{{ $fieldLabels[$ch['field']] ?? $ch['field'] }}:</span>
                                            {{ $ch['old'] ?? '—' }} → {{ $ch['new'] ?? '—' }}
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- SKIP no change --}}
    @if(!empty($details['skip_no_change']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-yellow-300">
            <summary class="cursor-pointer text-sm font-semibold text-yellow-800">
                🟡 SKIP — Tidak ada perubahan ({{ count($details['skip_no_change']) }})
            </summary>
            <div class="mt-3 overflow-x-auto max-h-60 overflow-y-auto border border-yellow-200 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-yellow-50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal / Tipe</th>
                            <th class="px-2 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['skip_no_change'] as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2">{{ $row['tanggal'] }} / {{ $row['tipe_bongkaran'] }}</td>
                                <td class="px-2 py-2">{{ $row['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- Duplicate in file --}}
    @if(!empty($details['duplicate_in_file']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-purple-400" open>
            <summary class="cursor-pointer text-sm font-semibold text-purple-800">
                🟣 DUPLIKAT DI FILE — Baris dengan kombinasi (line+tanggal+tipe) sama ({{ count($details['duplicate_in_file']) }})
            </summary>
            <div class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded text-xs text-purple-900">
                Baris di bawah akan diabaikan karena kombinasi line+tanggal+tipe sudah muncul di baris sebelumnya pada file Excel yang sama.
            </div>
            <div class="mt-3 overflow-x-auto max-h-60 overflow-y-auto border border-purple-200 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-purple-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line Number</th>
                            <th class="px-2 py-2 text-left">Tanggal / Tipe</th>
                            <th class="px-2 py-2 text-right">Lowering</th>
                            <th class="px-2 py-2 text-right">Bongkaran</th>
                            <th class="px-2 py-2 text-right">Kedalaman</th>
                            <th class="px-2 py-2 text-left">Duplikat Dari Baris</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['duplicate_in_file'] as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 font-mono">{{ $row['line_number'] }}</td>
                                <td class="px-2 py-2">{{ $row['tanggal'] }} / {{ $row['tipe_bongkaran'] }}</td>
                                <td class="px-2 py-2 text-right">{{ $row['lowering'] ?? '—' }}</td>
                                <td class="px-2 py-2 text-right">{{ $row['bongkaran'] ?? '—' }}</td>
                                <td class="px-2 py-2 text-right">{{ $row['kedalaman'] ?? '—' }}</td>
                                <td class="px-2 py-2">#{{ $row['first_seen_row'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- Errors --}}
    @if(!empty($details['error']))
        <details class="mb-4 bg-white rounded-lg p-4 border-2 border-gray-500" open>
            <summary class="cursor-pointer text-sm font-semibold text-gray-800">
                ⚫ ERROR — Gagal validasi ({{ count($details['error']) }})
            </summary>
            <div class="mt-3 overflow-x-auto max-h-80 overflow-y-auto border border-gray-300 rounded">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Baris</th>
                            <th class="px-2 py-2 text-left">Line / Tanggal</th>
                            <th class="px-2 py-2 text-left">Error</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($details['error'] as $row)
                            <tr>
                                <td class="px-2 py-2 align-top">{{ $row['row'] }}</td>
                                <td class="px-2 py-2 align-top">
                                    <span class="font-mono">{{ $row['line_number'] ?? '—' }}</span><br>
                                    <span class="text-gray-500">{{ $row['tanggal'] ?? '—' }}</span>
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <ul class="list-disc list-inside text-red-600">
                                        @foreach($row['errors'] as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- Action Buttons --}}
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        @if($hasAnyCommittable)
            <form action="{{ route('jalur.lowering.import.execute') }}" method="POST" id="commitForm">
                @csrf
                <input type="hidden" name="temp_file_path" value="{{ $tempFilePath }}">
                <input type="hidden" name="force_update" value="{{ $forceUpdate ? 1 : 0 }}">
                <input type="hidden" name="allow_recall" value="{{ $allowRecall ? 1 : 0 }}">

                @if($hasRecall)
                    <label class="flex items-start gap-3 mb-4 p-3 bg-red-50 border-2 border-red-300 rounded cursor-pointer">
                        <input type="checkbox" id="confirmRecall" class="mt-1 h-4 w-4 text-red-600">
                        <div class="text-sm">
                            <div class="font-semibold text-red-900">Saya paham konsekuensi Recall</div>
                            <div class="text-red-800 text-xs mt-1">
                                {{ $summary['recall'] }} record approved akan kembali ke draft dan butuh re-approval dari Tracer & CGP. Foto terkait akan di-reset ke <code>tracer_pending</code>.
                            </div>
                        </div>
                    </label>
                @endif

                <div class="flex gap-3">
                    <button type="submit" id="commitBtn"
                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-4 rounded-md font-semibold text-lg"
                        {{ $hasRecall ? 'disabled' : '' }}>
                        Commit Import
                        ({{ $summary['new'] }} new · {{ $summary['update'] }} update{{ $hasRecall ? ' · '.$summary['recall'].' recall' : '' }})
                    </button>

                    <a href="{{ route('jalur.lowering.import.index') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-4 rounded-md font-semibold">
                        Batalkan
                    </a>
                </div>
            </form>
        @else
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-yellow-800 font-semibold">Tidak ada perubahan yang bisa di-commit.</p>
                <p class="text-yellow-700 text-sm mt-1">Semua baris masuk kategori skip / error / duplicate. Silakan periksa file Excel Anda.</p>
            </div>
            <a href="{{ route('jalur.lowering.import.index') }}" class="inline-block mt-4 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-md">
                Kembali
            </a>
        @endif

        <div class="bg-gray-50 border border-gray-200 rounded p-3 mt-4 text-xs text-gray-600">
            <span class="font-semibold">File:</span> {{ $fileName }}
        </div>
    </div>
</div>

@if($hasRecall)
<script>
    const confirmBox = document.getElementById('confirmRecall');
    const commitBtn = document.getElementById('commitBtn');
    confirmBox?.addEventListener('change', () => {
        commitBtn.disabled = !confirmBox.checked;
    });
</script>
@endif
@endsection
