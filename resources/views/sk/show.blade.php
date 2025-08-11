{{-- resources/views/sk/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail SK - AERGAS')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-3xl font-bold text-gray-800">Detail SK #{{ $sk->id }}</h1>
    <div class="flex gap-2">
      <a href="{{ route('sk.index') }}" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
      {{-- <a href="{{ route('sk.edit', $sk->id) }}" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a> --}}
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white p-4 rounded-xl card-shadow">
      <h2 class="font-semibold mb-3">Info Umum</h2>
      <dl class="text-sm">
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Reff</dt><dd class="col-span-2 font-mono">{{ $sk->reff_id_pelanggan }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Nomor SK</dt><dd class="col-span-2">{{ $sk->nomor_sk ?? '-' }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Status</dt><dd class="col-span-2">{{ $sk->status ?? '-' }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Tgl Instalasi</dt><dd class="col-span-2">{{ $sk->tanggal_instalasi ?? '-' }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Catatan</dt><dd class="col-span-2">{{ $sk->notes ?? '-' }}</dd></div>
      </dl>
    </div>

    <div class="bg-white p-4 rounded-xl card-shadow">
      <h2 class="font-semibold mb-3">Pelanggan</h2>
      @php($cp = $sk->calonPelanggan)
      <dl class="text-sm">
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Nama</dt><dd class="col-span-2">{{ $cp->nama_pelanggan ?? '-' }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Alamat</dt><dd class="col-span-2">{{ $cp->alamat ?? '-' }}</dd></div>
        <div class="grid grid-cols-3 py-1"><dt class="text-gray-500">Telepon</dt><dd class="col-span-2">{{ $cp->no_telepon ?? '-' }}</dd></div>
      </dl>
    </div>
  </div>
</div>
@endsection
