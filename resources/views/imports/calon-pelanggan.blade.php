@extends('layouts.app')

@section('title', 'Import Calon Pelanggan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
  <h1 class="text-2xl font-bold">Import Calon Pelanggan</h1>

  <div class="flex items-center gap-3">
    <a href="{{ route('imports.calon-pelanggan.template') }}" class="px-3 py-2 bg-gray-100 rounded">Download Template</a>
  </div>

  <form method="POST" action="{{ route('imports.calon-pelanggan.import') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">File (.xlsx/.csv)</label>
      <input type="file" name="file" accept=".xlsx,.csv" class="border rounded px-3 py-2 w-full" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Mode</label>
      <select name="mode" class="border rounded px-3 py-2 w-full">
        <option value="dry-run" selected>Dry-run</option>
        <option value="commit">Commit</option>
      </select>
    </div>
    <div class="flex items-center gap-2">
      <input type="checkbox" id="save_report" name="save_report" value="1" class="rounded">
      <label for="save_report">Simpan report JSON</label>
    </div>
    <button class="px-4 py-2 bg-blue-600 text-white rounded">Proses</button>
  </form>

  @if (session('import_results'))
    @php($r = session('import_results'))
    <div class="p-4 bg-green-50 rounded">
      <h2 class="font-semibold mb-2">Hasil</h2>
      <div>Created: <b>{{ $r['success'] }}</b></div>
      <div>Updated: <b>{{ $r['updated'] }}</b></div>
      <div>Failed: <b>{{ count($r['failed']) }}</b></div>
      @if (!empty($r['report_path']))
        <div class="mt-2">
          <a class="px-3 py-1 bg-gray-100 rounded" href="{{ route('imports.report.download', ['path' => $r['report_path']]) }}">Download Report</a>
        </div>
      @endif
    </div>
  @endif
</div>
@endsection
