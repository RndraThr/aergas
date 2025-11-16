@extends('layouts.app')

@section('title', 'Data PILOT - AERGAS')

@section('content')
<style>
  /* Auto-width table cells - fit content without truncation */
  .pilot-table {
    width: auto;
    min-width: 100%;
    table-layout: auto;
    white-space: nowrap;
  }

  .pilot-table th,
  .pilot-table td {
    padding: 0.5rem 0.75rem;
    white-space: nowrap;
    overflow: visible;
  }

  /* Ensure all cells stay on one line - no wrapping */
  .pilot-table .no-wrap {
    white-space: nowrap;
  }

  /* Border separators for column groups */
  .group-separator {
    border-left: 3px solid #d1d5db !important;
  }

  /* Column group colors for sub-headers */
  .group-material-sk {
    background-color: #dbeafe !important;
    color: #1e40af !important;
  }

  .group-material-sr {
    background-color: #dcfce7 !important;
    color: #15803d !important;
  }

  .group-evidence-sk {
    background-color: #f3e8ff !important;
    color: #7e22ce !important;
  }

  .group-evidence-sr {
    background-color: #e0e7ff !important;
    color: #4338ca !important;
  }

  .group-evidence-mgrt {
    background-color: #fed7aa !important;
    color: #c2410c !important;
  }

  .group-evidence-gasin {
    background-color: #fecaca !important;
    color: #b91c1c !important;
  }

  .group-review-cgp {
    background-color: #ccfbf1 !important;
    color: #0f766e !important;
  }

  .group-dokumen {
    background-color: #e5e7eb !important;
    color: #374151 !important;
  }
</style>

<div class="space-y-6">

  <div class="flex items-center gap-4">
    <a href="{{ route('pilot-comparison.index') }}" class="text-gray-600 hover:text-gray-800">
      <i class="fas fa-arrow-left mr-2"></i>Kembali
    </a>
    <div class="flex-1">
      <h1 class="text-3xl font-bold text-gray-800">Data PILOT</h1>
      <p class="text-gray-600 mt-1">Batch ID: <span class="font-mono text-sm">{{ $batch }}</span></p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('pilot-comparison.export', $batch) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
        <i class="fas fa-download mr-2"></i>Export Excel
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ session('success') }}</span>
    </div>
  @endif

  {{-- Summary Card --}}
  <div class="bg-white rounded-xl p-6 card-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-600">Total Records</p>
        <p class="text-3xl font-bold text-blue-600">{{ $summary->total ?? 0 }}</p>
      </div>
      <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
        <i class="fas fa-database text-blue-600 text-2xl"></i>
      </div>
    </div>
  </div>

  {{-- Search Filter --}}
  <div class="bg-white p-4 rounded-xl card-shadow">
    <form method="GET" action="{{ route('pilot-comparison.show', $batch) }}">
      <div class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari ID REFF atau Nama..."
               class="flex-1 px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          <i class="fas fa-search mr-2"></i>Cari
        </button>
      </div>
    </form>
  </div>

  {{-- PILOT Data Table --}}
  <div class="bg-white rounded-xl card-shadow">
    <div class="p-4 border-b">
      <h2 class="text-xl font-semibold text-gray-800">Detail Data PILOT (Semua Kolom)</h2>
      <p class="text-sm text-gray-500 mt-1">Scroll horizontal untuk melihat semua kolom</p>
    </div>

    @if($pilots->isEmpty())
      <div class="p-8 text-center text-gray-500">
        <i class="fas fa-inbox text-6xl mb-4"></i>
        <p class="text-lg">Tidak ada data yang sesuai dengan filter.</p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="pilot-table text-xs">
          <thead class="bg-gray-50 border-b sticky top-0">
            <tr>
              {{-- Basic Info --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">ID</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">ID REFF</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Nama</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">No. KTP</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">No. Ponsel</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Alamat</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">RT</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">RW</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Kota/Kab</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Kecamatan</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Kelurahan</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Padukuhan</th>

              {{-- Status --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Penetrasi/Pengembangan</th>

              {{-- Tanggal --}}
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">Tgl SK</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">Tgl SR</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">Tgl GAS IN</th>

              {{-- Keterangan --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Keterangan</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap">Batal</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Ket. Batal</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap">Anomali</th>

              {{-- Material SK (9 items) --}}
              <th class="px-3 py-2 text-center font-semibold text-blue-600 uppercase whitespace-nowrap group-separator" colspan="9">Material SK</th>

              {{-- Material SR (15 items) --}}
              <th class="px-3 py-2 text-center font-semibold text-green-600 uppercase whitespace-nowrap group-separator" colspan="15">Material SR</th>

              {{-- Evidence SK (5) --}}
              <th class="px-3 py-2 text-center font-semibold text-purple-600 uppercase whitespace-nowrap group-separator" colspan="5">Evidence SK</th>

              {{-- Evidence SR (6) --}}
              <th class="px-3 py-2 text-center font-semibold text-indigo-600 uppercase whitespace-nowrap group-separator" colspan="6">Evidence SR</th>

              {{-- Evidence MGRT (3) --}}
              <th class="px-3 py-2 text-center font-semibold text-orange-600 uppercase whitespace-nowrap group-separator" colspan="3">Evidence MGRT</th>

              {{-- Evidence Gas In (7) --}}
              <th class="px-3 py-2 text-center font-semibold text-red-600 uppercase whitespace-nowrap group-separator" colspan="7">Evidence Gas In</th>

              {{-- Review CGP (3) --}}
              <th class="px-3 py-2 text-center font-semibold text-teal-600 uppercase whitespace-nowrap group-separator" colspan="3">Review CGP</th>

              {{-- Dokumen (4) --}}
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap group-separator" colspan="4">Dokumen</th>
            </tr>
            <tr class="bg-gray-100">
              {{-- Basic Info --}}
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>
              <th class="px-3 py-2"></th>

              {{-- Material SK --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk group-separator">Elbow 3/4-1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Dbl Nipple 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Pipa Galv 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Elbow 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Ball Valve 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Nipple Slang 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Klem Pipa 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Sockdraft 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Sealtape</th>

              {{-- Material SR --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr group-separator">TS 63x20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Coupler 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Pipa PE 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Elbow PE 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Female TF 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Pipa Galv 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Klem Pipa 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Ball Valve 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Elbow 90 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Dbl Nipple 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Regulator</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Cassing</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Coupling</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Sealtape</th>

              {{-- Evidence SK --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk group-separator">BA Pasang</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Pneum Start</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Pneum Finish</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Valve SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Isometrik</th>

              {{-- Evidence SR --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr group-separator">Pneum Start</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Pneum Finish</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Jns Tapping</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Kedalaman</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Cassing</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Isometrik</th>

              {{-- Evidence MGRT --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt group-separator">Foto MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt">Foto Pondasi</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt">No Seri</th>

              {{-- Evidence Gas In --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin group-separator">BA Gas In</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Rangkaian</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Bubble Test</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Foto MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Kompor</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Stiker</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">No Seri</th>

              {{-- Review CGP --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp group-separator">SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp">SR</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp">Gas In</th>

              {{-- Dokumen --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen group-separator">BA Gas In</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Asbuilt SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Asbuilt SR</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Comment</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($pilots as $pilot)
              <tr class="hover:bg-gray-50">
                {{-- Basic Info --}}
                <td class="no-wrap text-gray-600">{{ $pilot->id }}</td>
                <td class="no-wrap"><span class="font-mono text-xs font-semibold text-blue-600">{{ $pilot->id_reff }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-700 font-medium">{{ $pilot->nama ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->nomor_kartu_identitas ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->nomor_ponsel ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->alamat ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->rt ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->rw ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->id_kota_kab ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->id_kecamatan ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->id_kelurahan ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->padukuhan ?? '-' }}</span></td>

                {{-- Status --}}
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->penetrasi_pengembangan ?? '-' }}</span></td>

                {{-- Tanggal --}}
                <td class="no-wrap text-center">
                  @if($pilot->tanggal_terpasang_sk)
                    <span class="text-xs font-semibold text-gray-700">{{ \Carbon\Carbon::parse($pilot->tanggal_terpasang_sk)->format('d/m/Y') }}</span>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->tanggal_terpasang_sr)
                    <span class="text-xs font-semibold text-gray-700">{{ \Carbon\Carbon::parse($pilot->tanggal_terpasang_sr)->format('d/m/Y') }}</span>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->tanggal_terpasang_gas_in)
                    <span class="text-xs font-semibold text-gray-700">{{ \Carbon\Carbon::parse($pilot->tanggal_terpasang_gas_in)->format('d/m/Y') }}</span>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>

                {{-- Keterangan --}}
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->keterangan ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->batal ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->keterangan_batal ?? '-' }}</span></td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->anomali ?? '-' }}</span></td>

                {{-- Material SK --}}
                <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_elbow_3_4_to_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_double_nipple_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_pipa_galvanize_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_elbow_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_ball_valve_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_nipple_slang_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_klem_pipa_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_sockdraft_galvanis_1_2 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sk_sealtape ?? '-' }}</span></td>

                {{-- Material SR --}}
                <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_ts_63x20mm ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_coupler_20mm ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_pipa_pe_20mm ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_elbow_pe_20mm ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_female_tf_pe_20mm ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_pipa_galvanize_3_4 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_klem_pipa_3_4 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_ball_valves_3_4 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_long_elbow_90_3_4 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_double_nipple_3_4 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_regulator ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_meter_gas_rumah_tangga ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_cassing_1 ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_coupling_mgrt ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->mat_sr_sealtape ?? '-' }}</span></td>

                {{-- Evidence SK --}}
                <td class="no-wrap text-center group-separator">
                  @if($pilot->ev_sk_foto_berita_acara_pemasangan)
                    <a href="{{ $pilot->ev_sk_foto_berita_acara_pemasangan }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sk_foto_pneumatik_start)
                    <a href="{{ $pilot->ev_sk_foto_pneumatik_start }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sk_foto_pneumatik_finish)
                    <a href="{{ $pilot->ev_sk_foto_pneumatik_finish }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sk_foto_valve_sk)
                    <a href="{{ $pilot->ev_sk_foto_valve_sk }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sk_foto_isometrik_sk)
                    <a href="{{ $pilot->ev_sk_foto_isometrik_sk }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>

                {{-- Evidence SR --}}
                <td class="no-wrap text-center group-separator">
                  @if($pilot->ev_sr_foto_pneumatik_start)
                    <a href="{{ $pilot->ev_sr_foto_pneumatik_start }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sr_foto_pneumatik_finish)
                    <a href="{{ $pilot->ev_sr_foto_pneumatik_finish }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sr_foto_jenis_tapping)
                    <a href="{{ $pilot->ev_sr_foto_jenis_tapping }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sr_foto_kedalaman)
                    <a href="{{ $pilot->ev_sr_foto_kedalaman }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sr_foto_cassing)
                    <a href="{{ $pilot->ev_sr_foto_cassing }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_sr_foto_isometrik_sr)
                    <a href="{{ $pilot->ev_sr_foto_isometrik_sr }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>

                {{-- Evidence MGRT --}}
                <td class="no-wrap text-center group-separator">
                  @if($pilot->ev_mgrt_foto_meter_gas_rumah_tangga)
                    <a href="{{ $pilot->ev_mgrt_foto_meter_gas_rumah_tangga }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_mgrt_foto_pondasi_mgrt)
                    <a href="{{ $pilot->ev_mgrt_foto_pondasi_mgrt }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->ev_mgrt_nomor_seri_mgrt ?? '-' }}</span></td>

                {{-- Evidence Gas In --}}
                <td class="no-wrap text-center group-separator">
                  @if($pilot->ev_gasin_berita_acara_gas_in)
                    <a href="{{ $pilot->ev_gasin_berita_acara_gas_in }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_gasin_rangkaian_meter_gas_pondasi)
                    <a href="{{ $pilot->ev_gasin_rangkaian_meter_gas_pondasi }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_gasin_foto_bubble_test)
                    <a href="{{ $pilot->ev_gasin_foto_bubble_test }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_gasin_foto_mgrt)
                    <a href="{{ $pilot->ev_gasin_foto_mgrt }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_gasin_foto_kompor_menyala_pelanggan)
                    <a href="{{ $pilot->ev_gasin_foto_kompor_menyala_pelanggan }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->ev_gasin_foto_stiker_sosialisasi)
                    <a href="{{ $pilot->ev_gasin_foto_stiker_sosialisasi }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->ev_gasin_nomor_seri_mgrt ?? '-' }}</span></td>

                {{-- Review CGP --}}
                <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">{{ $pilot->review_cgp_sk ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->review_cgp_sr ?? '-' }}</span></td>
                <td class="no-wrap text-center"><span class="text-xs text-gray-600">{{ $pilot->review_cgp_gas_in ?? '-' }}</span></td>

                {{-- Dokumen --}}
                <td class="no-wrap text-center group-separator">
                  @if($pilot->ba_gas_in)
                    <a href="{{ $pilot->ba_gas_in }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->asbuilt_sk)
                    <a href="{{ $pilot->asbuilt_sk }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap text-center">
                  @if($pilot->asbuilt_sr)
                    <a href="{{ $pilot->asbuilt_sr }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>
                  @else
                    <span class="text-xs text-gray-400">-</span>
                  @endif
                </td>
                <td class="no-wrap"><span class="text-xs text-gray-600">{{ $pilot->comment_cgp ?? '-' }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          {{-- Info: Showing X to Y of Z results --}}
          <div class="flex items-center">
            <span class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ $pilots->firstItem() ?? 0 }}</span>
              to
              <span class="font-medium">{{ $pilots->lastItem() ?? 0 }}</span>
              of
              <span class="font-medium">{{ $pilots->total() }}</span>
              results
            </span>
          </div>

          {{-- Navigation Buttons --}}
          <div class="flex items-center space-x-2">
            {{-- Previous Button --}}
            @if ($pilots->onFirstPage())
              <span class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed">
                Previous
              </span>
            @else
              <a href="{{ $pilots->appends(request()->query())->previousPageUrl() }}"
                 class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                Previous
              </a>
            @endif

            {{-- Page Numbers --}}
            @php
              $currentPage = $pilots->currentPage();
              $lastPage = $pilots->lastPage();
              $start = max(1, $currentPage - 2);
              $end = min($lastPage, $currentPage + 2);
            @endphp

            {{-- First page --}}
            @if ($start > 1)
              <a href="{{ $pilots->appends(request()->query())->url(1) }}"
                 class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                1
              </a>
              @if ($start > 2)
                <span class="px-2 text-sm text-gray-400">...</span>
              @endif
            @endif

            {{-- Page range --}}
            @for ($page = $start; $page <= $end; $page++)
              @if ($page == $currentPage)
                <span class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium bg-aergas-orange text-white transition-colors">
                  {{ $page }}
                </span>
              @else
                <a href="{{ $pilots->appends(request()->query())->url($page) }}"
                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                  {{ $page }}
                </a>
              @endif
            @endfor

            {{-- Last page --}}
            @if ($end < $lastPage)
              @if ($end < $lastPage - 1)
                <span class="px-2 text-sm text-gray-400">...</span>
              @endif
              <a href="{{ $pilots->appends(request()->query())->url($lastPage) }}"
                 class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                {{ $lastPage }}
              </a>
            @endif

            {{-- Next Button --}}
            @if ($pilots->hasMorePages())
              <a href="{{ $pilots->appends(request()->query())->nextPageUrl() }}"
                 class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                Next
              </a>
            @else
              <span class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed">
                Next
              </span>
            @endif
          </div>
        </div>
      </div>
    @endif
  </div>

</div>

@endsection
