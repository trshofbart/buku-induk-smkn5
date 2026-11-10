@extends('layouts.admin')
@section('title', 'Ubah Mata Pelajaran')

@section('content')
@if ($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif

<div class="bg-white rounded-xl shadow p-5 max-w-2xl">
    <h1 class="font-bold text-lg mb-1">Ubah Mata Pelajaran</h1>
    <p class="text-sm text-slate-500 mb-4">Mengubah nama, kelompok, jurusan, tingkat, urutan, atau KKM. Urutan menentukan posisi mapel di lembar cetak (angka kecil tampil lebih atas).</p>

    <form method="POST" action="{{ route('admin.mapel.update', $mapel) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="kembali" value="{{ $kembali }}">

        <div>
            <label class="block text-sm font-semibold mb-1">Nama mata pelajaran</label>
            <input name="nama" required value="{{ old('nama', $mapel->nama) }}" class="border rounded-lg px-3 py-2 w-full">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Kelompok</label>
                <select name="kelompok" required class="border rounded-lg px-3 py-2 w-full">
                    @foreach (\App\Models\Mapel::KELOMPOK as $k => $v)
                        <option value="{{ $k }}" @selected(old('kelompok', $mapel->kelompok) == $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Jurusan</label>
                <select name="jurusan_id" class="border rounded-lg px-3 py-2 w-full">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $j)
                        <option value="{{ $j->id }}" @selected(old('jurusan_id', $mapel->jurusan_id) == $j->id)>{{ $j->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Tingkat</label>
                <select name="tingkat" class="border rounded-lg px-3 py-2 w-full">
                    <option value="">Semua Tingkat</option>
                    @foreach (['X', 'XI', 'XII'] as $t)
                        <option value="{{ $t }}" @selected(old('tingkat', $mapel->tingkat) == $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Urutan</label>
                <input type="number" min="0" name="urutan" value="{{ old('urutan', $mapel->urutan) }}" class="border rounded-lg px-3 py-2 w-full">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">KKM</label>
                <input type="number" step="0.01" min="0" max="100" name="kkm_default" value="{{ old('kkm_default', $mapel->kkm_default) }}" class="border rounded-lg px-3 py-2 w-full">
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan Perubahan</button>
            <a href="{{ $kembali ?? route('admin.mapel.index') }}" class="border border-slate-300 px-4 py-2 rounded-lg">Batal</a>
        </div>
    </form>
</div>
@endsection
