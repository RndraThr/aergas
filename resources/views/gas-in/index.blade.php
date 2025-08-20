@extends('layouts.app')

@section('title', 'Data Gas In - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Data Gas In</h1>
      <p class="text-gray-600 mt-1">Daftar Commissioning Gas In</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('gas-in.index') }}" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <i class="fas fa-sync-alt mr-1"></i>Refresh
      </a>
      <a href="{{ route('gas-in.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
        <i class="fas fa-plus mr-2"></i>Buat Gas In
      </a>
    </div>
  </div>

  <form method="get" action="{{ route('gas-in.index') }}" class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-4">
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Cari Reff ID, Customer, atau Status..."
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-green-500">
      </div>
      <div>
        <select name="status" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-green-500">
          <option value="">Semua Status</option>
          <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="ready_for_tracer" {{ request('status') == 'ready_for_tracer' ? 'selected' : '' }}>Ready for Tracer</option>
          <option value="tracer_approved" {{ request('status') == 'tracer_approved' ? 'selected' : '' }}>Tracer Approved</option>
          <option value="cgp_approved" {{ request('status') == 'cgp_approved' ? 'selected' : '' }}>CGP Approved</option>
          <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="rejected" {{ str_contains(request('status'), 'rejected') ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div>
        <button class="w-full px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
          <i class="fas fa-search mr-1"></i>Cari
        </button>
      </div>
    </div>
  </form>

  @php
    $totalCount = $gasIn->total();
    $draftCount = $gasIn->where('status', 'draft')->count();
    $readyCount = $gasIn->where('status', 'ready_for_tracer')->count();
    $completedCount = $gasIn->where('status', 'completed')->count();
  @endphp

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-green-600">{{ $totalCount }}</div>
      <div class="text-sm text-gray-600">Total Gas In</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-yellow-600">{{ $draftCount }}</div>
      <div class="text-sm text-gray-600">Draft</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-purple-600">{{ $readyCount }}</div>
      <div class="text-sm text-gray-600">Ready for Review</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-green-600">{{ $completedCount }}</div>
      <div class="text-sm text-gray-600">Completed</div>
    </div>
  </div>

  <div class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reff ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Gas In</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @forelse ($gasIn as $row)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->id }}</td>
            <td class="px-4 py-3 text-sm font-medium text-green-600">
              <a href="{{ route('gas-in.show', $row->id) }}" class="hover:text-green-800">
                {{ $row->reff_id_pelanggan }}
              </a>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ $row->calonPelanggan->nama_pelanggan ?? '-' }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              @if($row->createdBy)
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-2">
                    <span class="text-xs font-medium text-green-600">
                      {{ strtoupper(substr($row->createdBy->name, 0, 1)) }}
                    </span>
                  </div>
                  <span class="text-sm">{{ $row->createdBy->name }}</span>
                </div>
              @else
                <span class="text-gray-400 text-sm">-</span>
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ $row->tanggal_gas_in ? $row->tanggal_gas_in->format('d/m/Y') : '-' }}
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs font-medium rounded-full
                @class([
                  'bg-gray-100 text-gray-700' => $row->status === 'draft',
                  'bg-blue-100 text-blue-800' => $row->status === 'ready_for_tracer',
                  'bg-yellow-100 text-yellow-800' => $row->status === 'approved_scheduled',
                  'bg-purple-100 text-purple-800' => $row->status === 'tracer_approved',
                  'bg-amber-100 text-amber-800' => $row->status === 'cgp_approved',
                  'bg-red-100 text-red-800' => str_contains($row->status,'rejected'),
                  'bg-green-100 text-green-800' => $row->status === 'completed',
                ])
              ">{{ ucwords(str_replace('_', ' ', $row->status)) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-1">
                <a href="{{ route('gas-in.show',$row->id) }}"
                   class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                   title="Lihat Detail">
                  <i class="fas fa-eye mr-1"></i>Detail
                </a>
                @if($row->status === 'draft')
                  <a href="{{ route('gas-in.edit',$row->id) }}"
                     class="px-3 py-1.5 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200"
                     title="Edit Gas In">
                    <i class="fas fa-edit mr-1"></i>Edit
                  </a>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              <div class="flex flex-col items-center">
                <i class="fas fa-gas-pump text-4xl text-gray-300 mb-3"></i>
                <p class="text-lg font-medium mb-1">Belum ada data Gas In</p>
                <p class="text-sm">Silakan buat Gas In baru untuk memulai</p>
                <a href="{{ route('gas-in.create') }}" class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                  <i class="fas fa-plus mr-2"></i>Buat Gas In Pertama
                </a>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($gasIn->hasPages())
    <div class="bg-white rounded-lg card-shadow p-4">
      {{ $gasIn->links() }}
    </div>
  @endif
</div>
@endsection
