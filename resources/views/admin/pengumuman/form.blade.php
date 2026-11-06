@extends('layouts.admin')
@section('title', $mode === 'create' ? 'Tambah Pengumuman' : 'Edit Pengumuman')

@section('content')
<form method="POST" action="{{ $mode === 'create' ? route('admin.pengumuman.store') : route('admin.pengumuman.update', $pengumuman) }}" class="bg-white rounded-xl shadow p-5 space-y-3 max-w-2xl">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif
    <div><label class="text-sm font-semibold">Judul *</label><input name="judul" value="{{ old('judul', $pengumuman->judul) }}" required class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="text-sm font-semibold">Isi *</label><textarea name="isi" rows="5" required class="w-full border rounded-lg px-3 py-2">{{ old('isi', $pengumuman->isi) }}</textarea></div>
    <div class="grid md:grid-cols-2 gap-3">
        <div><label class="text-sm font-semibold">Tanggal Mulai</label><input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $pengumuman->tanggal_mulai?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Tanggal Selesai</label><input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $pengumuman->tanggal_selesai?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $pengumuman->is_active ?? true))> Aktif (tampil di halaman depan)</label>
    <div class="flex gap-2"><button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button><a href="{{ route('admin.pengumuman.index') }}" class="border px-4 py-2 rounded-lg">Batal</a></div>
</form>
@endsection
