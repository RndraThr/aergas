{{-- resources/views/sk/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit SK')
@section('content')
  <div class="p-6 bg-white rounded-xl card-shadow">
    <h1 class="text-2xl font-bold mb-2">Edit SK #{{ $sk->id }}</h1>
    <p>Placeholder edit. (Akan diisi nanti)</p>
    <a href="{{ route('sk.index') }}" class="mt-4 inline-block px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
  </div>
@endsection
