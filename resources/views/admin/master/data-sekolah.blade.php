@extends('layouts.admin')
@section('title', 'Data Sekolah')

@section('content')
<form method="POST" action="{{ route('admin.data-sekolah.update') }}" class="bg-white rounded-xl shadow p-5 space-y-3 max-w-2xl">
    @csrf @method('PUT')
    <div><label class="text-sm font-semibold">Nama Sekolah *</label><input name="nama_sekolah" value="{{ old('nama_sekolah', $sekolah->nama_sekolah) }}" required class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="text-sm font-semibold">NPSN</label><input name="npsn" value="{{ old('npsn', $sekolah->npsn) }}" class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="text-sm font-semibold">Alamat</label><textarea name="alamat" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('alamat', $sekolah->alamat) }}</textarea></div>
    <div class="grid md:grid-cols-2 gap-3">
        <div><label class="text-sm font-semibold">Telepon</label><input name="telepon" value="{{ old('telepon', $sekolah->telepon) }}" class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Email</label><input type="email" name="email" value="{{ old('email', $sekolah->email) }}" class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Website</label><input name="website" value="{{ old('website', $sekolah->website) }}" class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Kepala Sekolah</label><input name="kepala_sekolah" value="{{ old('kepala_sekolah', $sekolah->kepala_sekolah) }}" class="w-full border rounded-lg px-3 py-2"></div>
    </div>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button>
</form>
@endsection
