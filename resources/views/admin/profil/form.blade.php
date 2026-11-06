@extends('layouts.admin')
@section('title', 'Edit Profil: '.$profil->judul)

@section('content')
<form method="POST" action="{{ route('admin.profil.update', $profil) }}" class="bg-white rounded-xl shadow p-5 space-y-3 max-w-3xl">
    @csrf @method('PUT')
    <div><label class="text-sm font-semibold">Judul *</label><input name="judul" value="{{ old('judul', $profil->judul) }}" required class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="text-sm font-semibold">Isi Halaman *</label><textarea name="isi" rows="12" required class="w-full border rounded-lg px-3 py-2">{{ old('isi', $profil->isi) }}</textarea><p class="text-xs text-slate-500">Boleh memakai tag HTML sederhana (&lt;p&gt;, &lt;strong&gt;, &lt;ol&gt;, &lt;ul&gt;, &lt;li&gt;).</p></div>
    <div class="grid md:grid-cols-2 gap-3">
        <div><label class="text-sm font-semibold">Urutan di Menu</label><input type="number" name="urutan" value="{{ old('urutan', $profil->urutan) }}" class="w-full border rounded-lg px-3 py-2"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $profil->is_active))> Tampilkan di halaman depan</label>
    </div>
    <div class="flex gap-2"><button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button><a href="{{ route('admin.profil.index') }}" class="border px-4 py-2 rounded-lg">Batal</a></div>
</form>
@endsection
