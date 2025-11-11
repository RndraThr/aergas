@extends('layouts.app')

@section('title', 'Import RT/RW')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Import RT/RW Calon Pelanggan</h1>
    <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
      <i class="fas fa-arrow-left mr-2"></i>
      Kembali
    </a>
  </div>

  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h4 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
      <i class="fas fa-info-circle"></i>
      Format File Excel
    </h4>
    <p class="text-sm text-blue-800 mb-2">File Excel harus memiliki kolom berikut:</p>
    <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
      <li><strong>reff_id</strong> - ID Pelanggan (wajib)</li>
      <li><strong>rt</strong> - RT (opsional)</li>
      <li><strong>rw</strong> - RW (opsional)</li>
    </ul>
    <p class="text-xs text-blue-700 mt-3 italic">
      Contoh: reff_id = 447100, rt = 003, rw = 027
    </p>
  </div>

  <form method="POST" action="{{ route('customers.import-rt-rw') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">File (.xlsx / .xls / .csv)</label>
      <input type="file" name="file" accept=".xlsx,.xls,.csv" class="border rounded px-3 py-2 w-full" required>
      @error('file')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
      <i class="fas fa-upload"></i>
      <span>Upload & Import</span>
    </button>
  </form>

  @if (session('import_results'))
    @php($r = session('import_results'))
    <div class="p-4 {{ $r['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-lg">
      <h2 class="font-semibold mb-2 {{ $r['success'] ? 'text-green-900' : 'text-red-900' }}">
        <i class="fas fa-{{ $r['success'] ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
        {{ $r['success'] ? 'Import Berhasil!' : 'Import Gagal' }}
      </h2>

      @if($r['success'])
        <div class="space-y-1 text-sm {{ $r['success'] ? 'text-green-800' : 'text-red-800' }}">
          <div>Data Diupdate: <b>{{ $r['updated'] }}</b></div>
          <div>Data Dilewati: <b>{{ $r['skipped'] }}</b></div>
        </div>

        @if (!empty($r['errors']) && count($r['errors']) > 0)
          <details class="mt-3">
            <summary class="cursor-pointer text-sm font-medium text-yellow-800">Detail Error ({{ count($r['errors']) }})</summary>
            <ul class="mt-2 text-xs text-yellow-700 list-disc list-inside space-y-1">
              @foreach($r['errors'] as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </details>
        @endif
      @else
        <p class="text-sm text-red-800">{{ $r['message'] }}</p>
        @if (!empty($r['errors']))
          <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($r['errors'] as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        @endif
      @endif
    </div>
  @endif
</div>
@endsection
