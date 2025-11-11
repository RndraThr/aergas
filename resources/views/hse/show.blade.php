@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('hse.daily-reports.index') }}"
                   class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Laporan Harian HSE</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('l, d F Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $report->getStatusBadgeClass() }}">
                    {{ ucfirst($report->status) }}
                </span>

                <!-- Export PDF Button -->
                <a href="{{ route('hse.daily-reports.pdf', $report->id) }}"
                   target="_blank"
                   class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </a>

                @if($report->canEdit())
                <a href="{{ route('hse.daily-reports.edit', $report->id) }}"
                   class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                @endif

                @if($report->canSubmit())
                <form action="{{ route('hse.daily-reports.submit', $report->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                            onclick="return confirm('Submit laporan ini?')">
                        <i class="fas fa-paper-plane mr-2"></i>Submit
                    </button>
                </form>
                @endif

                @if($report->canApprove() && auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form action="{{ route('hse.daily-reports.approve', $report->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                            onclick="return confirm('Approve laporan ini?')">
                        <i class="fas fa-check-circle mr-2"></i>Approve
                    </button>
                </form>
                @endif

                @if($report->status == 'submitted' && auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form action="{{ route('hse.daily-reports.reject', $report->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                            onclick="return confirm('Reject laporan ini?')">
                        <i class="fas fa-times-circle mr-2"></i>Reject
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informasi Proyek -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-info-circle text-aergas-orange mr-2"></i>
                        Informasi Proyek
                    </h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Laporan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cuaca</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="text-2xl mr-2">{!! $report->getCuacaIcon() !!}</span>
                                {{ ucfirst(str_replace('_', ' ', $report->cuaca)) }}
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Nama Proyek</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->nama_proyek }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pemberi Pekerjaan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->pemberi_pekerjaan }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Kontraktor</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->kontraktor }}</dd>
                        </div>
                        @if($report->sub_kontraktor)
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Sub Kontraktor</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->sub_kontraktor }}</dd>
                        </div>
                        @endif
                        @if($report->catatan)
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->catatan }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Pekerjaan Harian -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tasks text-aergas-orange mr-2"></i>
                        Pekerjaan Harian
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($report->pekerjaanHarian as $index => $pekerjaan)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-aergas-orange text-white rounded-full flex items-center justify-center font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">{{ $pekerjaan->jenis_pekerjaan }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $pekerjaan->lokasi_detail }}
                                </p>
                                @if($pekerjaan->google_maps_link)
                                <a href="{{ $pekerjaan->google_maps_link }}" target="_blank"
                                   class="text-sm text-blue-600 hover:text-blue-800 mt-1 inline-block">
                                    <i class="fas fa-external-link-alt mr-1"></i>Lihat di Google Maps
                                </a>
                                @endif
                                @if($pekerjaan->deskripsi_pekerjaan)
                                <p class="text-sm text-gray-700 mt-2">{{ $pekerjaan->deskripsi_pekerjaan }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">Tidak ada data pekerjaan</p>
                    @endforelse
                </div>
            </div>

            <!-- Tenaga Kerja -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-users text-aergas-orange mr-2"></i>
                        Tenaga Kerja
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan/Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($report->tenagaKerja as $index => $tk)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                            {{ $tk->kategori_team }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $tk->role_name }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $tk->jumlah_orang }} orang</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Tidak ada data tenaga kerja</td>
                                </tr>
                                @endforelse
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="3" class="px-4 py-3 text-sm text-gray-900 text-right">Total Pekerja:</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $report->total_pekerja }} orang</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Toolbox Meeting -->
            @if($report->toolboxMeeting)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-chalkboard-teacher text-aergas-orange mr-2"></i>
                        Toolbox Meeting (TBM)
                    </h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Waktu Pelaksanaan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($report->toolboxMeeting->waktu_mulai)->format('H:i') }} WIB</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jumlah Peserta</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $report->toolboxMeeting->jumlah_peserta }} orang</dd>
                        </div>
                    </dl>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-3">Materi TBM</dt>
                        <ol class="space-y-2">
                            @foreach($report->toolboxMeeting->materiList as $materi)
                            <li class="flex items-start space-x-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-aergas-orange text-white rounded-full flex items-center justify-center text-xs font-semibold">
                                    {{ $materi->urutan }}
                                </span>
                                <span class="text-sm text-gray-900">{{ $materi->materi_pembahasan }}</span>
                            </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
            @endif

            <!-- Program HSE -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-clipboard-list text-aergas-orange mr-2"></i>
                        Program HSE Harian
                    </h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-2">
                        @forelse($report->programHarian as $program)
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                            <span class="text-sm text-gray-900">{{ $program->nama_program }}</span>
                        </li>
                        @empty
                        <p class="text-gray-500 text-center py-4">Tidak ada program HSE</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- JKA Stats -->
            <div class="bg-gradient-to-br from-green-50 to-blue-50 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-3">Jam Kerja Aman (JKA)</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($report->jka_hari_ini) }} <span class="text-sm text-gray-600">jam</span></p>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <p class="text-xs text-gray-500">Kumulatif</p>
                        <p class="text-3xl font-bold text-green-600">{{ number_format($report->jka_kumulatif) }} <span class="text-sm text-gray-600">jam</span></p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    JKA = Total Pekerja × 8 jam
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-3">Timeline</h3>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-plus text-blue-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Dibuat</p>
                            <p class="text-sm text-gray-900">{{ $report->created_at->format('d M Y, H:i') }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $report->creator->name ?? 'System' }}</p>
                        </div>
                    </div>

                    @if($report->submitted_at)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-paper-plane text-green-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Submitted</p>
                            <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($report->submitted_at)->format('d M Y, H:i') }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $report->submitter->name ?? 'System' }}</p>
                        </div>
                    </div>
                    @endif

                    @if($report->approved_at)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Approved</p>
                            <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($report->approved_at)->format('d M Y, H:i') }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $report->approver->name ?? 'System' }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-gray-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Terakhir Update</p>
                            <p class="text-sm text-gray-900">{{ $report->updated_at->format('d M Y, H:i') }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $report->updater->name ?? 'System' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foto Dokumentasi -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">
                        <i class="fas fa-camera text-aergas-orange mr-2"></i>
                        Foto Dokumentasi
                    </h3>
                </div>
                <div class="p-6">
                    <!-- Info Box for Editing Photos -->
                    @if($report->canEdit() && $report->photos->count() > 0)
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                            <div class="flex-1">
                                <p class="text-sm text-blue-800">
                                    Untuk menambah, mengganti, atau menghapus foto dokumentasi, silakan gunakan tombol
                                    <strong>Edit</strong> di atas.
                                </p>
                            </div>
                            <a href="{{ route('hse.daily-reports.edit', $report->id) }}"
                               class="ml-3 px-3 py-1 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors whitespace-nowrap">
                                <i class="fas fa-edit mr-1"></i>Edit Laporan
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Photo Gallery -->
                    <div class="grid grid-cols-2 gap-4">
                        @forelse($report->photos as $photo)
                        <div class="relative group border border-gray-200 rounded-lg overflow-hidden">
                            <a href="{{ $photo->image_url }}" target="_blank" class="block">
                                <img src="{{ $photo->thumbnail_url }}"
                                     alt="{{ $photo->getCategoryLabel() }}"
                                     class="w-full h-48 object-cover">
                            </a>
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center">
                                <a href="{{ $photo->image_url }}"
                                   target="_blank"
                                   class="opacity-0 group-hover:opacity-100 bg-white text-gray-900 px-4 py-2 rounded-lg text-sm transition-opacity">
                                    <i class="fas fa-external-link-alt mr-2"></i>Buka Foto
                                </a>
                            </div>
                            <div class="p-2 bg-white">
                                <p class="text-xs font-semibold text-gray-900">{{ $photo->getCategoryLabel() }}</p>
                                @if($photo->keterangan)
                                <p class="text-xs text-gray-600 mt-1">{{ $photo->keterangan }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="fas fa-user mr-1"></i>{{ $photo->uploader->name ?? 'System' }}
                                </p>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-2 text-center py-8">
                            <i class="fas fa-images text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">Belum ada foto dokumentasi</p>
                            @if($report->canEdit())
                            <a href="{{ route('hse.daily-reports.edit', $report->id) }}"
                               class="inline-block mt-3 px-4 py-2 bg-aergas-orange text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                                <i class="fas fa-camera mr-2"></i>Upload Foto
                            </a>
                            @endif
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
