@extends('layouts.app')

@section('title', 'Kelola Duplikat Data Joint')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Kelola Duplikat Data Joint</h1>
            <p class="text-gray-500 text-sm">
                Ditemukan <strong>{{ $totalGroups }} grup duplikat</strong>
                ({{ $totalRecords }} record total) berdasarkan kombinasi:
                <code class="bg-gray-100 px-1 rounded text-xs">joint_number + tanggal + line_from + line_to</code>
            </p>
        </div>
        <a href="{{ route('jalur.joint.import.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Import
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center">
            <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    @if($totalGroups === 0)
        {{-- No duplicates --}}
        <div class="bg-green-50 border border-green-200 rounded-lg p-10 text-center">
            <svg class="w-14 h-14 mx-auto text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-xl font-semibold text-green-700 mb-1">Tidak Ada Duplikat</h2>
            <p class="text-green-600 text-sm">Semua data joint di database bersih, tidak ada duplikat.</p>
        </div>

    @else
        {{-- Warning banner --}}
        <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-sm text-amber-800">
                <strong>Cara penggunaan:</strong> Untuk setiap grup duplikat, pilih satu record yang ingin
                <strong>dipertahankan</strong> (tombol hijau), lalu klik <strong>"Hapus Duplikat"</strong>.
                Record yang tidak dipilih akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
            </div>
        </div>

        {{-- Duplicate Groups --}}
        @foreach($duplicateGroups as $groupIdx => $group)
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden border border-red-200">

            {{-- Group header --}}
            <div class="bg-red-50 border-b border-red-200 px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="font-bold text-red-700">Grup {{ $groupIdx + 1 }}</span>
                    <span class="ml-2 text-sm text-gray-600">
                        <code class="font-semibold text-purple-700">{{ $group['nomor_joint'] }}</code>
                        &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($group['tanggal_joint'])->format('d M Y') }}
                        &nbsp;·&nbsp; {{ $group['joint_line_from'] }} → {{ $group['joint_line_to'] }}
                    </span>
                </div>
                <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded-full font-semibold">
                    {{ $group['total'] }} duplikat
                </span>
            </div>

            {{-- Records table --}}
            <form action="{{ route('jalur.joint.import.duplicates.resolve') }}" method="POST"
                  id="form-group-{{ $groupIdx }}" onsubmit="return confirmResolve(this, {{ $group['total'] - 1 }})">
                @csrf

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-28">Pertahankan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fitting Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dibuat</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Diupdate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="tbody-{{ $groupIdx }}">
                            @foreach($group['records'] as $recIdx => $rec)
                            <tr class="hover:bg-gray-50 transition" id="row-{{ $groupIdx }}-{{ $rec->id }}">
                                <td class="px-4 py-3 text-center">
                                    <input type="radio"
                                           name="keep_id"
                                           value="{{ $rec->id }}"
                                           {{ $recIdx === 0 ? 'checked' : '' }}
                                           onchange="highlightSelected('{{ $groupIdx }}', '{{ $rec->id }}', {{ $group['records']->pluck('id')->toJson() }})"
                                           class="w-4 h-4 text-green-600 focus:ring-green-500">
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $rec->id }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $rec->cluster?->nama_cluster ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $rec->fittingType?->nama_fitting ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                                        {{ $rec->tipe_penyambungan }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'draft'            => 'bg-gray-100 text-gray-600',
                                            'tracer_pending'   => 'bg-yellow-100 text-yellow-700',
                                            'tracer_approved'  => 'bg-green-100 text-green-700',
                                            'tracer_rejected'  => 'bg-red-100 text-red-700',
                                            'cgp_approved'     => 'bg-emerald-100 text-emerald-700',
                                            'cgp_rejected'     => 'bg-red-200 text-red-800',
                                        ];
                                        $color = $statusColors[$rec->status_laporan] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $color }}">
                                        {{ $rec->status_laporan }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $rec->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $rec->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                                    {{ $rec->keterangan ?? '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Hidden: delete_ids will be filled by JS based on radio selection --}}
                <div id="delete-inputs-{{ $groupIdx }}"></div>

                {{-- Action bar --}}
                <div class="bg-gray-50 border-t px-5 py-3 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Pilih radio button pada record yang ingin dipertahankan. Sisanya ({{ $group['total'] - 1 }} record) akan dihapus.
                    </p>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-md text-sm font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus Duplikat ({{ $group['total'] - 1 }} record)
                    </button>
                </div>

            </form>
        </div>
        @endforeach

    @endif
</div>

<script>
// All record IDs per group (pre-rendered from PHP)
const groupRecordIds = {
    @foreach($duplicateGroups as $groupIdx => $group)
    '{{ $groupIdx }}': {{ $group['records']->pluck('id')->toJson() }},
    @endforeach
};

// On page load: highlight default selected rows & populate delete inputs
document.addEventListener('DOMContentLoaded', function () {
    @foreach($duplicateGroups as $groupIdx => $group)
    @if($group['records']->isNotEmpty())
    highlightSelected('{{ $groupIdx }}', '{{ $group['records']->first()->id }}',
        {{ $group['records']->pluck('id')->toJson() }});
    @endif
    @endforeach
});

function highlightSelected(groupIdx, keepId, allIds) {
    // Style rows
    allIds.forEach(function (id) {
        const row = document.getElementById('row-' + groupIdx + '-' + id);
        if (!row) return;
        if (String(id) === String(keepId)) {
            row.classList.add('bg-green-50', 'ring-1', 'ring-green-300');
            row.classList.remove('bg-red-50', 'ring-red-200', 'opacity-60');
        } else {
            row.classList.add('bg-red-50', 'opacity-60');
            row.classList.remove('bg-green-50', 'ring-1', 'ring-green-300');
        }
    });

    // Rebuild hidden delete_id inputs
    const container = document.getElementById('delete-inputs-' + groupIdx);
    container.innerHTML = '';
    allIds.forEach(function (id) {
        if (String(id) !== String(keepId)) {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'delete_ids[]';
            input.value = id;
            container.appendChild(input);
        }
    });
}

function confirmResolve(form, deleteCount) {
    const keepRadio = form.querySelector('input[name="keep_id"]:checked');
    if (!keepRadio) {
        alert('Pilih record yang ingin dipertahankan terlebih dahulu.');
        return false;
    }
    return confirm(
        'Yakin ingin menghapus ' + deleteCount + ' record duplikat?\n\n' +
        'Record ID ' + keepRadio.value + ' akan dipertahankan.\n' +
        'Tindakan ini tidak dapat dibatalkan.'
    );
}
</script>
@endsection
