@extends('layouts.app')

@section('title', 'PILOT Comparison - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">PILOT Data Import</h1>
      <p class="text-gray-600 mt-1">Import dan kelola data PILOT dari Google Sheets</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('pilot-comparison.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="fas fa-upload mr-2"></i>Import PILOT Data
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ session('error') }}</span>
    </div>
  @endif

  {{-- List of Upload Batches --}}
  <div class="bg-white rounded-xl card-shadow">
    <div class="p-4 border-b">
      <h2 class="text-xl font-semibold text-gray-800">Riwayat Upload PILOT</h2>
    </div>

    @if($batches->isEmpty())
      <div class="p-8 text-center text-gray-500">
        <i class="fas fa-inbox text-6xl mb-4"></i>
        <p class="text-lg">Belum ada data PILOT.</p>
        <p class="text-sm mt-2">Import data PILOT dari Google Sheets untuk memulai.</p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Batch ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Uploaded By</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Upload Date</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Total Records</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($batches as $batch)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <span class="font-mono text-xs text-gray-600">{{ Str::limit($batch->batch_id, 20) }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-sm text-gray-700">{{ $batch->uploader->name ?? 'Unknown' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-sm text-gray-600">{{ $batch->created_at->format('d M Y H:i') }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm font-semibold">
                    {{ $batch->total_records }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex gap-2 justify-center">
                    <a href="{{ route('pilot-comparison.show', $batch->batch_id) }}"
                       class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                      <i class="fas fa-eye mr-1"></i>View
                    </a>
                    <form action="{{ route('pilot-comparison.destroy', $batch->batch_id) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus batch ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="p-4 border-t">
        {{ $batches->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
