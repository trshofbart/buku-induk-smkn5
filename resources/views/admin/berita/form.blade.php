@extends('layouts.admin')
@section('title', $mode === 'create' ? 'Tulis Berita' : 'Edit Berita')

@section('content')
<form method="POST" action="{{ $mode === 'create' ? route('admin.berita.store') : route('admin.berita.update', $berita) }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-5 space-y-3 max-w-3xl">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif
    <div><label class="text-sm font-semibold">Judul *</label><input name="judul" value="{{ old('judul', $berita->judul) }}" required class="w-full border rounded-lg px-3 py-2"></div>
    <div class="grid md:grid-cols-2 gap-3">
        <div><label class="text-sm font-semibold">Kategori</label><select name="kategori_berita_id" class="w-full border rounded-lg px-3 py-2"><option value="">— Tanpa kategori —</option>@foreach ($kategori as $k)<option value="{{ $k->id }}" @selected(old('kategori_berita_id', $berita->kategori_berita_id) == $k->id)>{{ $k->nama }}</option>@endforeach</select></div>
        <div><label class="text-sm font-semibold">Status *</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="draft" @selected(old('status', $berita->status) === 'draft')>Draft (tidak tampil di publik)</option><option value="publish" @selected(old('status', $berita->status) === 'publish')>Publish (tampil di halaman depan)</option></select></div>
    </div>
    <div><label class="text-sm font-semibold">Ringkasan</label><textarea name="ringkasan" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('ringkasan', $berita->ringkasan) }}</textarea></div>
    <div><label class="text-sm font-semibold">Isi Berita *</label><textarea name="isi" rows="10" required class="w-full border rounded-lg px-3 py-2">{{ old('isi', $berita->isi) }}</textarea><p class="text-xs text-slate-500">Boleh memakai tag HTML sederhana (&lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;li&gt;).</p></div>
    <div><label class="text-sm font-semibold">Foto (jpg/png/webp, maks 2 MB)</label><input type="file" name="foto" accept="image/*" class="w-full border rounded-lg px-3 py-2">@if($berita->foto)<p class="text-xs text-slate-500 mt-1">Foto saat ini: {{ $berita->foto }}</p>@endif</div>
    <div><label class="text-sm font-semibold">Tanggal Terbit</label><input type="datetime-local" name="published_at" value="{{ old('published_at', $berita->published_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded-lg px-3 py-2"></div>
    <div class="flex gap-2"><button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button><a href="{{ route('admin.berita.index') }}" class="border px-4 py-2 rounded-lg">Batal</a></div>
</form>
@endsection
