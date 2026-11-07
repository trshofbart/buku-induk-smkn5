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
        <div><label class="text-sm font-semibold">Bidang Studi Keahlian *</label><input name="bidang_studi_keahlian" value="{{ old('bidang_studi_keahlian', $sekolah->bidang_studi_keahlian) }}" required class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Tempat Tanda Tangan Rapor</label><input name="tempat_ttd" value="{{ old('tempat_ttd', $sekolah->tempat_ttd) }}" placeholder="Surakarta" class="w-full border rounded-lg px-3 py-2"></div>
        <div><label class="text-sm font-semibold">Tanggal Rapor</label><input type="date" name="tanggal_rapor" value="{{ old('tanggal_rapor', $sekolah->tanggal_rapor?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
    </div>
    <label class="flex items-start gap-2 text-sm bg-slate-50 border rounded-lg px-3 py-2">
        <input type="checkbox" name="input_nilai_dibuka" value="1" {{ old('input_nilai_dibuka', $sekolah->input_nilai_dibuka) ? 'checked' : '' }} class="mt-0.5">
        <span><strong>Input nilai dibuka</strong> — bila tidak dicentang, Admin tidak bisa menyimpan atau mengimport nilai (kunci ala e-Rapor) sampai dibuka kembali.</span>
    </label>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button>
</form>
@endsection
