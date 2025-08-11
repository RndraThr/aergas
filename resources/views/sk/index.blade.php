{{-- resources/views/sk/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data SK - AERGAS')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Data SK</h1>
      <p class="text-gray-600">Daftar Sambungan Kompor</p>
    </div>
    <a href="{{ route('sk.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      + Buat SK
    </a>
  </div>

  {{-- Filter/Search --}}
  <form method="GET" action="{{ route('sk.index') }}" class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div class="md:col-span-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari reff/nomor SK/status…"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <button class="w-full px-4 py-2 bg-gray-800 text-white rounded hover:bg-black">Cari</button>
      </div>
    </div>
  </form>

  {{-- Tabel --}}
  <div class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">ID</th>
          <th class="px-4 py-3 text-left">Reff</th>
          <th class="px-4 py-3 text-left">Nama Pelanggan</th>
          <th class="px-4 py-3 text-left">Nomor SK</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Tgl Instalasi</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse ($sk as $row)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">{{ $row->id }}</td>
            <td class="px-4 py-3 font-mono">{{ $row->reff_id_pelanggan }}</td>
            <td class="px-4 py-3">
              {{ optional($row->calonPelanggan)->nama_pelanggan ?? '-' }}
            </td>
            <td class="px-4 py-3">{{ $row->nomor_sk ?? '-' }}</td>
            <td class="px-4 py-3">
              {{-- kalau ada accessor badge gunakan, kalau tidak tampilkan status biasa --}}
              @if(method_exists($row, 'getStatusBadgeAttribute') && !empty($row->status_badge))
                {!! $row->status_badge !!}
              @else
                <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">{{ $row->status ?? '-' }}</span>
              @endif
            </td>
            <td class="px-4 py-3">{{ optional($row->tanggal_instalasi)->format('Y-m-d') ?? ($row->tanggal_instalasi ?? '-') }}</td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('sk.show', $row->id) }}" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">Detail</a>
              {{-- aktifkan kalau view edit sudah ada --}}
              {{-- <a href="{{ route('sk.edit', $row->id) }}" class="px-3 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</a> --}}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada data SK.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $sk->links() }}
    </div>
  </div>
</div>
@endsection
