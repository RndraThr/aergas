@extends('layouts.app')

@section('title', 'Import Data Calon Pelanggan')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Import Data Calon Pelanggan</h1>
    <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
      <i class="fas fa-arrow-left mr-2"></i>
      Kembali
    </a>
  </div>

  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h4 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
      <i class="fas fa-info-circle"></i>
      Informasi Import
    </h4>
    <p class="text-sm text-blue-800 mb-3">
      Fitur ini digunakan untuk <strong>mengupdate data pelanggan yang sudah ada</strong> secara massal (bulk update).
    </p>
    <div class="text-sm text-blue-800 space-y-2">
      <div><strong>Cara Penggunaan:</strong></div>
      <ol class="list-decimal list-inside space-y-1 ml-2">
        <li>File Excel/CSV harus memiliki kolom <code class="bg-blue-100 px-1 rounded">reff_id</code> sebagai identifier (wajib)</li>
        <li>Tambahkan kolom lain yang ingin diupdate (nama kolom harus sesuai dengan database)</li>
        <li>Hanya baris dengan <code class="bg-blue-100 px-1 rounded">reff_id</code> yang ditemukan yang akan diupdate</li>
        <li><strong>Kolom yang kosong akan diabaikan</strong> - nilai lama di database tetap dipertahankan</li>
        <li>Kolom yang tidak ada di Excel tidak akan diubah</li>
      </ol>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="font-semibold text-lg mb-4">Format File Excel/CSV</h3>

    <div class="mb-4">
      <h4 class="font-medium text-gray-700 mb-2">Kolom Wajib:</h4>
      <div class="bg-gray-50 p-3 rounded border">
        <code class="text-sm font-mono">reff_id</code>
        <span class="text-gray-600 text-sm ml-2">- ID Pelanggan (digunakan untuk mencari data yang akan diupdate)</span>
      </div>
    </div>

    <div class="mb-4">
      <h4 class="font-medium text-gray-700 mb-2">Kolom Opsional (dapat diupdate):</h4>
      <div class="bg-gray-50 p-4 rounded border">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm font-mono">
          @foreach($allowedColumns as $column)
            <div class="flex items-center">
              <i class="fas fa-check text-green-600 mr-2 text-xs"></i>
              <code>{{ $column }}</code>
            </div>
          @endforeach
        </div>
      </div>
      <p class="text-xs text-gray-600 mt-2 italic">
        * Anda dapat menggunakan semua atau hanya sebagian kolom di atas sesuai kebutuhan
      </p>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
      <h4 class="font-medium text-yellow-900 mb-2 flex items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        Contoh Format Excel
      </h4>
      <div class="overflow-x-auto">
        <table class="min-w-full text-xs border border-gray-300">
          <thead class="bg-gray-100">
            <tr>
              <th class="border border-gray-300 px-2 py-1">reff_id</th>
              <th class="border border-gray-300 px-2 py-1">nama_pelanggan</th>
              <th class="border border-gray-300 px-2 py-1">rt</th>
              <th class="border border-gray-300 px-2 py-1">rw</th>
              <th class="border border-gray-300 px-2 py-1">no_bagi</th>
              <th class="border border-gray-300 px-2 py-1">kelurahan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border border-gray-300 px-2 py-1">447100</td>
              <td class="border border-gray-300 px-2 py-1">Budi Santoso</td>
              <td class="border border-gray-300 px-2 py-1">003</td>
              <td class="border border-gray-300 px-2 py-1">027</td>
              <td class="border border-gray-300 px-2 py-1">24100048703</td>
              <td class="border border-gray-300 px-2 py-1">Sinduadi</td>
            </tr>
            <tr>
              <td class="border border-gray-300 px-2 py-1">447101</td>
              <td class="border border-gray-300 px-2 py-1">Siti Aminah</td>
              <td class="border border-gray-300 px-2 py-1">005</td>
              <td class="border border-gray-300 px-2 py-1">028</td>
              <td class="border border-gray-300 px-2 py-1">24100048704</td>
              <td class="border border-gray-300 px-2 py-1">Caturtunggal</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="text-xs text-yellow-800 mt-3 space-y-1">
        <p><strong>Catatan Penting:</strong></p>
        <ul class="list-disc list-inside ml-2 space-y-1">
          <li>Contoh di atas hanya mengupdate beberapa kolom. Anda bisa menambahkan kolom lain sesuai kebutuhan.</li>
          <li><strong>Kolom kosong = tidak diupdate.</strong> Misalnya baris 2 kolom <code class="bg-yellow-100 px-1">rt</code> kosong, maka nilai <code class="bg-yellow-100 px-1">rt</code> lama di database tetap dipertahankan.</li>
          <li>Jika ingin menghapus nilai (set NULL), gunakan fitur edit manual di halaman detail pelanggan.</li>
        </ul>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('customers.import-bulk-data') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4" id="importForm">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">
        <i class="fas fa-file-excel mr-1"></i>
        Pilih File (.xlsx / .xls / .csv)
      </label>
      <input type="file" name="file" accept=".xlsx,.xls,.csv" class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required id="fileInput">
      @error('file')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
      @enderror
      <p class="text-xs text-gray-500 mt-1">Maksimal ukuran file: 5MB</p>
    </div>

    <div class="border-t border-gray-200 pt-4">
      <label class="flex items-start gap-3 cursor-pointer group">
        <input type="checkbox" name="force_update" value="1" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <div class="flex-1">
          <div class="text-sm font-medium text-gray-900 group-hover:text-blue-700">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i>
            Force Update (Timpa Data yang Sudah Ada)
          </div>
          <div class="text-xs text-gray-600 mt-1">
            <strong>Tidak dicentang (default):</strong> Hanya update kolom yang <strong>masih kosong/NULL</strong> di database. Kolom yang sudah berisi akan di-skip.<br>
            <strong>Dicentang:</strong> Update <strong>semua kolom</strong> yang ada di Excel, termasuk menimpa data yang sudah ada di database.
          </div>
        </div>
      </label>
    </div>

    <div class="flex items-center gap-3">
      <button type="submit" name="mode" value="preview" class="submit-btn px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors flex items-center gap-2 font-medium">
        <i class="fas fa-eye"></i>
        <span class="btn-text">Preview (Dry Run)</span>
        <span class="btn-loading hidden">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Memproses...</span>
        </span>
      </button>

      <button type="submit" name="mode" value="commit" class="submit-btn px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 font-medium">
        <i class="fas fa-upload"></i>
        <span class="btn-text">Commit (Langsung Simpan)</span>
        <span class="btn-loading hidden">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Menyimpan...</span>
        </span>
      </button>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded p-3 text-xs text-gray-700">
      <p><strong>💡 Tips:</strong></p>
      <ul class="list-disc list-inside space-y-1 mt-1">
        <li><strong>Preview:</strong> Menampilkan perubahan yang akan dilakukan <em>tanpa</em> menyimpan ke database</li>
        <li><strong>Commit:</strong> Langsung menyimpan perubahan ke database</li>
      </ul>
    </div>
  </form>

  <!-- PREVIEW RESULTS -->
  @if (session('import_preview'))
    @php
      $p = session('import_preview');
      $s = $p['summary'] ?? [];
    @endphp
    <div class="p-6 bg-yellow-50 border-2 border-yellow-400 rounded-lg shadow-lg">
      <div class="flex items-start justify-between mb-4">
        <div>
          <h2 class="font-semibold text-xl text-yellow-900 flex items-center gap-2">
            <i class="fas fa-eye"></i>
            Preview Mode - Data Belum Tersimpan
          </h2>
          <p class="text-sm text-yellow-800 mt-1">{{ $p['message'] }}</p>
        </div>
      </div>

      <!-- Summary Cards -->
      @if(!empty($s))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
          <div class="bg-white rounded-lg p-4 border-2 border-blue-300">
            <div class="text-3xl font-bold text-blue-600">{{ $s['total_rows'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Total Baris</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-green-300">
            <div class="text-3xl font-bold text-green-600">{{ $s['updated'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Akan Diupdate</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-yellow-300">
            <div class="text-3xl font-bold text-yellow-600">{{ $s['skipped'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Akan Dilewati</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-red-300">
            <div class="text-3xl font-bold text-red-600">{{ $s['not_found_count'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Tidak Ditemukan</div>
          </div>
        </div>

        <!-- Force Update Status Indicator -->
        <div class="mb-4 p-3 rounded-lg border-2 {{ $s['force_update'] ? 'bg-orange-50 border-orange-400' : 'bg-blue-50 border-blue-400' }}">
          <div class="flex items-center gap-2 text-sm font-semibold {{ $s['force_update'] ? 'text-orange-900' : 'text-blue-900' }}">
            <i class="fas {{ $s['force_update'] ? 'fa-exclamation-triangle' : 'fa-shield-alt' }}"></i>
            <span>Mode Update: {{ $s['force_update'] ? 'Force Update (Timpa Semua)' : 'Update Hanya Field Kosong' }}</span>
          </div>
          <p class="text-xs {{ $s['force_update'] ? 'text-orange-800' : 'text-blue-800' }} mt-1 ml-6">
            @if($s['force_update'])
              Semua kolom di Excel akan menimpa data yang sudah ada di database.
            @else
              Hanya kolom yang masih kosong/NULL di database yang akan diupdate. Kolom yang sudah ada nilainya akan di-skip.
            @endif
          </p>
        </div>

        <!-- Preview Changes - Data yang Akan Diupdate -->
        @if (!empty($s['updated_details']) && count($s['updated_details']) > 0)
          @php
            $hasUpdates = collect($s['updated_details'])->filter(function($d) {
              return $d['field_count'] > 0;
            })->count();
          @endphp

          @if($hasUpdates > 0)
            <details class="mb-4 bg-white rounded-lg p-4 border-2 border-green-400" open>
              <summary class="cursor-pointer text-sm font-semibold text-green-800 hover:text-green-900 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                Data yang Akan Diupdate ({{ $hasUpdates }})
              </summary>
              <div class="mt-4 overflow-x-auto max-h-96 overflow-y-auto border border-green-200 rounded">
                <table class="min-w-full text-xs border-collapse">
                  <thead class="bg-green-100 sticky top-0 z-10">
                    <tr>
                      <th class="border border-green-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                      <th class="border border-green-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Reff ID</th>
                      <th class="border border-green-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Nama Pelanggan</th>
                      <th class="border border-green-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Perubahan Data</th>
                      <th class="border border-green-300 px-2 py-2 text-center font-semibold whitespace-nowrap">Total Field</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white">
                    @foreach($s['updated_details'] as $detail)
                      @if($detail['field_count'] > 0)
                        <tr class="hover:bg-green-50">
                          <td class="border border-green-200 px-2 py-2 text-center whitespace-nowrap">{{ $detail['row'] }}</td>
                          <td class="border border-green-200 px-2 py-2 font-mono whitespace-nowrap">{{ $detail['reff_id'] }}</td>
                          <td class="border border-green-200 px-2 py-2 whitespace-nowrap">{{ $detail['nama_pelanggan'] }}</td>
                          <td class="border border-green-200 px-2 py-2">
                            @if(!empty($detail['changes']))
                              <div class="space-y-1">
                                @foreach($detail['changes'] as $field => $change)
                                  <div class="flex items-start gap-2 py-1 border-b border-green-100 last:border-0">
                                    <span class="font-semibold text-gray-700 min-w-[80px]">{{ $field }}:</span>
                                    <div class="flex items-center gap-1 flex-1">
                                      <span class="text-red-600 line-through">{{ $change['old'] ?: '(kosong)' }}</span>
                                      <i class="fas fa-arrow-right text-gray-400 text-[8px]"></i>
                                      <span class="text-green-600 font-semibold">{{ $change['new'] }}</span>
                                    </div>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          </td>
                          <td class="border border-green-200 px-2 py-2 text-center whitespace-nowrap">
                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">
                              {{ $detail['field_count'] }}
                            </span>
                          </td>
                        </tr>
                      @endif
                    @endforeach
                  </tbody>
                </table>
              </div>
            </details>
          @endif

          <!-- Preview Changes - Data yang Di-skip -->
          @php
            $hasSkipped = collect($s['updated_details'])->filter(function($d) {
              return !empty($d['skipped_count']) && $d['skipped_count'] > 0;
            })->count();
          @endphp

          @if($hasSkipped > 0)
            <details class="mb-4 bg-white rounded-lg p-4 border-2 border-orange-400">
              <summary class="cursor-pointer text-sm font-semibold text-orange-800 hover:text-orange-900 flex items-center gap-2">
                <i class="fas fa-ban"></i>
                Data yang Di-skip ({{ $hasSkipped }})
              </summary>
              <div class="mt-4 overflow-x-auto max-h-96 overflow-y-auto border border-orange-200 rounded">
                <table class="min-w-full text-xs border-collapse">
                  <thead class="bg-orange-100 sticky top-0 z-10">
                    <tr>
                      <th class="border border-orange-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                      <th class="border border-orange-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Reff ID</th>
                      <th class="border border-orange-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Nama Pelanggan</th>
                      <th class="border border-orange-300 px-2 py-2 text-left font-semibold whitespace-nowrap">Field yang Di-skip</th>
                      <th class="border border-orange-300 px-2 py-2 text-center font-semibold whitespace-nowrap">Total Field</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white">
                    @foreach($s['updated_details'] as $detail)
                      @if(!empty($detail['skipped_count']) && $detail['skipped_count'] > 0)
                        <tr class="hover:bg-orange-50 {{ !empty($detail['all_skipped']) ? 'bg-yellow-50' : '' }}">
                          <td class="border border-orange-200 px-2 py-2 text-center whitespace-nowrap">{{ $detail['row'] }}</td>
                          <td class="border border-orange-200 px-2 py-2 font-mono whitespace-nowrap">{{ $detail['reff_id'] }}</td>
                          <td class="border border-orange-200 px-2 py-2 whitespace-nowrap">{{ $detail['nama_pelanggan'] }}</td>
                          <td class="border border-orange-200 px-2 py-2">
                            @if(!empty($detail['all_skipped']))
                              <div class="flex items-center gap-1 text-yellow-700 italic mb-2 pb-2 border-b border-yellow-200">
                                <i class="fas fa-exclamation-triangle text-xs"></i>
                                <span class="font-semibold">Semua field di-skip - Aktifkan Force Update</span>
                              </div>
                            @endif

                            @if(!empty($detail['skipped_fields']) && count($detail['skipped_fields']) > 0)
                              <div class="space-y-1">
                                @foreach($detail['skipped_fields'] as $skipped)
                                  <div class="flex items-start gap-2 py-1 border-b border-orange-100 last:border-0">
                                    <span class="font-semibold text-gray-700 min-w-[80px]">{{ $skipped['field'] }}:</span>
                                    <div class="flex-1">
                                      <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-500">DB:</span>
                                        <span class="font-semibold text-gray-900">{{ $skipped['existing_value'] }}</span>
                                      </div>
                                      <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-500">Excel:</span>
                                        <span class="text-orange-600 line-through">{{ $skipped['excel_value'] }}</span>
                                      </div>
                                    </div>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          </td>
                          <td class="border border-orange-200 px-2 py-2 text-center whitespace-nowrap">
                            <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded-full font-semibold">
                              {{ $detail['skipped_count'] }}
                            </span>
                          </td>
                        </tr>
                      @endif
                    @endforeach
                  </tbody>
                </table>
              </div>
            </details>
          @endif
        @endif

        <!-- Reff ID Tidak Ditemukan (Preview) -->
        @if (!empty($s['not_found_reff_ids']) && count($s['not_found_reff_ids']) > 0)
          <details class="mb-4 bg-white rounded-lg p-4 border-2 border-red-400">
            <summary class="cursor-pointer text-sm font-semibold text-red-800 hover:text-red-900 flex items-center gap-2">
              <i class="fas fa-exclamation-circle"></i>
              Reff ID Tidak Ditemukan ({{ count($s['not_found_reff_ids']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-60 overflow-y-auto border border-red-200 rounded">
              <table class="min-w-full text-xs border-collapse">
                <thead class="bg-red-100 sticky top-0 z-10">
                  <tr>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Reff ID</th>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Alasan</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  @foreach($s['not_found_reff_ids'] as $item)
                    <tr class="hover:bg-red-50">
                      <td class="border border-red-200 px-3 py-2 text-center whitespace-nowrap">{{ $item['row'] }}</td>
                      <td class="border border-red-200 px-3 py-2 font-mono font-semibold text-red-700 whitespace-nowrap">{{ $item['reff_id'] }}</td>
                      <td class="border border-red-200 px-3 py-2 text-gray-700">{{ $item['reason'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endif

        <!-- Commit Form -->
        <form method="POST" action="{{ route('customers.import-bulk-data') }}" class="bg-white border-2 border-blue-400 rounded-lg p-4">
          @csrf
          <input type="hidden" name="mode" value="commit">
          <input type="hidden" name="temp_file" value="{{ $p['temp_file'] ?? '' }}">
          <input type="hidden" name="force_update" value="{{ $p['force_update'] ? '1' : '0' }}">

          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-semibold text-gray-900 text-lg">Lanjutkan Import?</h3>
              <p class="text-sm text-gray-600">Klik tombol di bawah untuk menyimpan perubahan ke database</p>
            </div>
            <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 font-semibold text-lg shadow-lg">
              <i class="fas fa-check-circle"></i>
              <span>Commit & Simpan</span>
            </button>
          </div>
        </form>
      @endif
    </div>
  @endif

  @if (session('import_results'))
    @php
      $r = session('import_results');
      $s = $r['summary'] ?? [];
    @endphp
    <div class="p-6 {{ $r['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-lg shadow-sm">
      <h2 class="font-semibold text-lg mb-3 {{ $r['success'] ? 'text-green-900' : 'text-red-900' }}">
        <i class="fas fa-{{ $r['success'] ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
        {{ $r['success'] ? 'Import Berhasil!' : 'Import Gagal' }}
      </h2>

      <p class="text-sm {{ $r['success'] ? 'text-green-800' : 'text-red-800' }} mb-4">
        {{ $r['message'] }}
      </p>

      @if($r['success'] && !empty($s))
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
          <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-sm">
            <div class="text-3xl font-bold text-blue-600">{{ $s['total_rows'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Total Baris</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-sm">
            <div class="text-3xl font-bold text-green-600">{{ $s['updated'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Berhasil Update</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-yellow-300 shadow-sm">
            <div class="text-3xl font-bold text-yellow-600">{{ $s['skipped'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Dilewati</div>
          </div>
          <div class="bg-white rounded-lg p-4 border-2 border-red-300 shadow-sm">
            <div class="text-3xl font-bold text-red-600">{{ $s['not_found_count'] ?? 0 }}</div>
            <div class="text-xs text-gray-600 mt-1">Tidak Ditemukan</div>
          </div>
        </div>

        <!-- Data Berhasil Diupdate -->
        @if (!empty($s['updated_details']) && count($s['updated_details']) > 0)
          <details class="mb-4 bg-white rounded-lg p-4 border border-green-300" open>
            <summary class="cursor-pointer text-sm font-semibold text-green-800 hover:text-green-900 flex items-center gap-2">
              <i class="fas fa-check-circle"></i>
              Data Berhasil Diupdate ({{ count($s['updated_details']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-96 overflow-y-auto border border-green-200 rounded">
              <table class="min-w-full text-xs border-collapse">
                <thead class="bg-green-100 sticky top-0 z-10">
                  <tr>
                    <th class="border border-green-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                    <th class="border border-green-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Reff ID</th>
                    <th class="border border-green-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Nama Pelanggan</th>
                    <th class="border border-green-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Kolom yang Diupdate</th>
                    <th class="border border-green-300 px-3 py-2 text-center font-semibold whitespace-nowrap">Jumlah</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  @foreach($s['updated_details'] as $detail)
                    <tr class="hover:bg-green-50">
                      <td class="border border-green-200 px-3 py-2 text-center whitespace-nowrap">{{ $detail['row'] }}</td>
                      <td class="border border-green-200 px-3 py-2 font-mono whitespace-nowrap">{{ $detail['reff_id'] }}</td>
                      <td class="border border-green-200 px-3 py-2 whitespace-nowrap">{{ $detail['nama_pelanggan'] }}</td>
                      <td class="border border-green-200 px-3 py-2 text-gray-700">{{ $detail['updated_fields'] }}</td>
                      <td class="border border-green-200 px-3 py-2 text-center whitespace-nowrap">
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">{{ $detail['field_count'] }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endif

        <!-- Reff ID Tidak Ditemukan -->
        @if (!empty($s['not_found_reff_ids']) && count($s['not_found_reff_ids']) > 0)
          <details class="mb-4 bg-white rounded-lg p-4 border border-red-300">
            <summary class="cursor-pointer text-sm font-semibold text-red-800 hover:text-red-900 flex items-center gap-2">
              <i class="fas fa-exclamation-circle"></i>
              Reff ID Tidak Ditemukan ({{ count($s['not_found_reff_ids']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-60 overflow-y-auto border border-red-200 rounded">
              <table class="min-w-full text-xs border-collapse">
                <thead class="bg-red-100 sticky top-0 z-10">
                  <tr>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Reff ID</th>
                    <th class="border border-red-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Alasan</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  @foreach($s['not_found_reff_ids'] as $item)
                    <tr class="hover:bg-red-50">
                      <td class="border border-red-200 px-3 py-2 text-center whitespace-nowrap">{{ $item['row'] }}</td>
                      <td class="border border-red-200 px-3 py-2 font-mono font-semibold text-red-700 whitespace-nowrap">{{ $item['reff_id'] }}</td>
                      <td class="border border-red-200 px-3 py-2 text-gray-700">{{ $item['reason'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endif

        <!-- Reff ID Kosong -->
        @if (!empty($s['missing_reff_ids']) && count($s['missing_reff_ids']) > 0)
          <details class="mb-4 bg-white rounded-lg p-4 border border-orange-300">
            <summary class="cursor-pointer text-sm font-semibold text-orange-800 hover:text-orange-900 flex items-center gap-2">
              <i class="fas fa-times-circle"></i>
              Baris dengan Reff ID Kosong ({{ count($s['missing_reff_ids']) }})
            </summary>
            <div class="mt-4 overflow-x-auto max-h-60 overflow-y-auto border border-orange-200 rounded">
              <table class="min-w-full text-xs border-collapse">
                <thead class="bg-orange-100 sticky top-0 z-10">
                  <tr>
                    <th class="border border-orange-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Baris</th>
                    <th class="border border-orange-300 px-3 py-2 text-left font-semibold whitespace-nowrap">Alasan</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  @foreach($s['missing_reff_ids'] as $item)
                    <tr class="hover:bg-orange-50">
                      <td class="border border-orange-200 px-3 py-2 text-center whitespace-nowrap">{{ $item['row'] }}</td>
                      <td class="border border-orange-200 px-3 py-2 text-gray-700">{{ $item['reason'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endif

        <!-- All Errors/Warnings -->
        @if (!empty($s['errors']) && count($s['errors']) > 0)
          <details class="bg-white rounded-lg p-4 border border-yellow-300">
            <summary class="cursor-pointer text-sm font-semibold text-yellow-800 hover:text-yellow-900 flex items-center gap-2">
              <i class="fas fa-exclamation-triangle"></i>
              Semua Error/Warning ({{ count($s['errors']) }})
            </summary>
            <ul class="mt-4 text-xs text-yellow-800 space-y-1 max-h-60 overflow-y-auto bg-yellow-50 p-3 rounded">
              @foreach($s['errors'] as $error)
                <li class="flex items-start gap-2 py-1 border-b border-yellow-200 last:border-0">
                  <i class="fas fa-circle text-yellow-500 text-[6px] mt-1.5"></i>
                  <span>{{ $error }}</span>
                </li>
              @endforeach
            </ul>
          </details>
        @endif
      @else
        <!-- Error State -->
        <div class="bg-white rounded p-4 border border-red-300">
          <p class="text-sm text-red-800 font-medium">{{ $r['message'] }}</p>
          @if (!empty($r['errors']))
            <ul class="mt-3 text-sm text-red-700 space-y-1">
              @foreach($r['errors'] as $error)
                <li class="flex items-start gap-2">
                  <i class="fas fa-times-circle text-red-500 mt-0.5"></i>
                  <span>{{ $error }}</span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @endif
    </div>
  @endif
</div>

{{-- @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');

    if (form) {
        form.addEventListener('submit', function(e) {
            const clickedButton = e.submitter;

            if (clickedButton && clickedButton.classList.contains('submit-btn')) {
                // Disable all submit buttons
                const allButtons = form.querySelectorAll('.submit-btn');
                allButtons.forEach(btn => btn.disabled = true);

                // Show loading state on clicked button
                const btnText = clickedButton.querySelector('.btn-text');
                const btnLoading = clickedButton.querySelector('.btn-loading');

                if (btnText) btnText.classList.add('hidden');
                if (btnLoading) btnLoading.classList.remove('hidden');
            }
        });
    }

    // Auto-scroll to preview/results section if exists
    const previewSection = document.querySelector('[class*="bg-yellow-50"]');
    const resultsSection = document.querySelector('[class*="bg-green-50"], [class*="bg-red-50"]');

    if (previewSection) {
        setTimeout(() => {
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    } else if (resultsSection) {
        setTimeout(() => {
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
});
</script>
@endpush --}}
@endsection
