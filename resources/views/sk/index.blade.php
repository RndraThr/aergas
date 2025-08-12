{{-- resources/views/sk/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data SK - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Data SK</h1>
      <p class="text-gray-600 mt-1">Daftar SK terbaru</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('sk.index') }}" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">Refresh</a>
      <a href="{{ route('sk.index', ['q'=>request('q')]) }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.index') }}" class="hidden"></a>

      <a href="{{ route('sk.index') }}" class="hidden"></a>
      <a href="{{ route('sk.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Buat SK</a>
    </div>
  </div>

  <form method="get" action="{{ route('sk.index') }}" class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Reff ID / Nomor SK / Status"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <button class="w-full px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Cari</button>
      </div>
    </div>
  </form>

  <div class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reff ID</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nomor SK</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          <th class="px-4 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($sk as $row)
          <tr>
            <td class="px-4 py-2 text-sm text-gray-700">{{ $row->id }}</td>
            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row->reff_id_pelanggan }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ $row->nomor_sk ?? '-' }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ $row->tanggal_instalasi ?? '-' }}</td>
            <td class="px-4 py-2">
              <span class="px-2 py-0.5 rounded text-xs
                @class([
                  'bg-gray-100 text-gray-700' => $row->status === 'draft',
                  'bg-blue-100 text-blue-800' => $row->status === 'ready_for_tracer',
                  'bg-yellow-100 text-yellow-800' => $row->status === 'scheduled',
                  'bg-purple-100 text-purple-800' => $row->status === 'tracer_approved',
                  'bg-amber-100 text-amber-800' => $row->status === 'cgp_approved',
                  'bg-red-100 text-red-800' => str_contains($row->status,'rejected'),
                  'bg-green-100 text-green-800' => $row->status === 'completed',
                ])
              ">{{ strtoupper($row->status) }}</span>
            </td>
            <td class="px-4 py-2 text-right">
              <a href="{{ route('sk.show',$row->id) }}" class="px-3 py-1.5 text-sm bg-gray-100 rounded hover:bg-gray-200">Detail</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Belum ada data</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>
    {{ $sk->links() }}
  </div>
</div>
@endsection
