@extends('layouts.admin')
@section('title', 'Mapel per Jurusan')

@section('content')
@if (session('status'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-4">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif

<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    <select name="jurusan_id" class="border rounded-lg px-3 py-2">
        @foreach ($daftarJurusan as $j)<option value="{{ $j->id }}" @selected($jurusan->id == $j->id)>{{ $j->nama }} ({{ $j->kode }})</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Tampilkan</button>
    <a href="{{ route('admin.mapel.index') }}" class="border border-green-700 text-green-700 px-4 py-2 rounded-lg">Semua Mata Pelajaran</a>
</form>

<p class="text-sm text-slate-500 mb-4">Di bawah ini adalah daftar mata pelajaran <strong>C1 (Dasar Bidang Keahlian)</strong>, <strong>C2 (Dasar Program Keahlian)</strong> dan <strong>C3 (Kompetensi Keahlian)</strong> yang akan tercetak di Buku Induk untuk jurusan <strong>{{ $jurusan->nama }}</strong>. Mapel bertanda <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Umum</span> dipakai bersama semua jurusan; mapel bertanda <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Khusus {{ $jurusan->kode }}</span> hanya untuk jurusan ini. Mapel khusus yang disalin dari daftar umum menggantikan mapel umum asalnya; mapel khusus lain (mis. dari import data) ditambahkan di samping mapel umum.</p>

@if (! $punyaKhusus)
<form method="POST" action="{{ route('admin.mapel.salin') }}" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
    @csrf
    <input type="hidden" name="jurusan_id" value="{{ $jurusan->id }}">
    <p class="text-sm text-amber-900">Jurusan ini masih memakai daftar umum. Bila daftar C1/C2/C3 jurusan ini berbeda, salin dulu daftar umum ke jurusan ini, lalu ubah atau hapus namanya sesuai daftar jurusan.</p>
    <button class="bg-amber-600 text-white px-4 py-2 rounded-lg mt-2 text-sm">Salin Daftar Umum ke {{ $jurusan->nama }}</button>
</form>
@endif

<form method="POST" action="{{ route('admin.mapel.store') }}" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    @csrf
    <input type="hidden" name="jurusan_id" value="{{ $jurusan->id }}">
    <input name="nama" required placeholder="Nama mata pelajaran C1/C2/C3 untuk {{ $jurusan->nama }}" class="border rounded-lg px-3 py-2 flex-1 min-w-[200px]">
    <select name="kelompok" required class="border rounded-lg px-3 py-2">
        <option value="C1">C1 — Dasar Bidang Keahlian</option>
        <option value="C2">C2 — Dasar Program Keahlian</option>
        <option value="C3">C3 — Kompetensi Keahlian</option>
    </select>
    <select name="tingkat" class="border rounded-lg px-3 py-2">
        <option value="">Semua Tingkat</option>
        <option value="X">X</option><option value="XI">XI</option><option value="XII">XII</option>
    </select>
    <input type="number" name="urutan" placeholder="Urutan" class="border rounded-lg px-3 py-2 w-24">
    <input type="number" step="0.01" min="0" max="100" name="kkm_default" placeholder="KKM (76)" class="border rounded-lg px-3 py-2 w-28">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah ke {{ $jurusan->kode }}</button>
</form>

@foreach (['X', 'XI', 'XII'] as $tingkat)
<div class="bg-white rounded-xl shadow p-5 mb-4">
    <h2 class="font-bold">Tingkat {{ $tingkat }}</h2>
    @foreach (['C1' => 'C1 — Dasar Bidang Keahlian', 'C2' => 'C2 — Dasar Program Keahlian', 'C3' => 'C3 — Kompetensi Keahlian'] as $kel => $label)
        <h3 class="font-semibold text-sm mt-4 mb-2">{{ $label }}</h3>
        @if (($daftar[$tingkat][$kel] ?? collect())->isEmpty())
            <p class="text-sm text-slate-400">Belum ada mapel {{ $kel }} untuk tingkat ini.</p>
        @else
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-2">Nama</th><th class="px-4 py-2">Tingkat</th><th class="px-4 py-2">KKM</th><th class="px-4 py-2">Jenis</th><th class="px-4 py-2">Aksi</th></tr></thead>
            <tbody>
            @foreach ($daftar[$tingkat][$kel] as $m)
                <tr class="border-t">
                    <td class="px-4 py-2 font-semibold">{{ $m->nama }}</td>
                    <td class="px-4 py-2">{{ $m->tingkat ?? 'Semua' }}</td>
                    <td class="px-4 py-2">{{ $m->kkm_default }}</td>
                    <td class="px-4 py-2">@if ($m->jurusan_id)<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Khusus {{ $jurusan->kode }}</span>@else<span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Umum</span>@endif</td>
                    <td class="px-4 py-2">@if ($m->jurusan_id)<form method="POST" action="{{ route('admin.mapel.destroy', $m) }}" onsubmit="return confirm('Hapus mapel ini dari jurusan {{ $jurusan->nama }}?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form>@else<span class="text-slate-400 text-xs">—</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    @endforeach
</div>
@endforeach
@endsection
