@extends('layouts.admin')
@section('title', 'Mata Pelajaran')

@section('content')
@if (session('status'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-4">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('admin.mapel.store') }}" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    @csrf
    <input name="nama" required placeholder="Nama mata pelajaran" class="border rounded-lg px-3 py-2 flex-1 min-w-[200px]">
    <select name="kelompok" required class="border rounded-lg px-3 py-2">
        @foreach (\App\Models\Mapel::KELOMPOK as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
    </select>
    <select name="jurusan_id" class="border rounded-lg px-3 py-2">
        <option value="">Semua Jurusan</option>
        @foreach ($jurusan as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach
    </select>
    <select name="tingkat" class="border rounded-lg px-3 py-2">
        <option value="">Semua Tingkat</option>
        <option value="X">X</option><option value="XI">XI</option><option value="XII">XII</option>
    </select>
    <input type="number" name="urutan" placeholder="Urutan" class="border rounded-lg px-3 py-2 w-24">
    <input type="number" step="0.01" min="0" max="100" name="kkm_default" placeholder="KKM (76)" class="border rounded-lg px-3 py-2 w-28">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah</button>
</form>
<p class="text-sm text-slate-500 mb-4">Daftar awal 20 mapel diambil dari sheet MAPEL file MASTER (TEI &amp; RPL A). Mapel tanpa jurusan berlaku untuk semua jurusan; tanpa tingkat berlaku untuk X, XI, dan XII. Bila sebuah jurusan punya mapel sendiri di suatu kelompok (mis. C3-nya sendiri), mapel itu <strong>menggantikan</strong> mapel umum di kelompok tersebut pada lembar cetak — jadi tambahkan seluruh daftar mapel kelompok itu untuk jurusan tersebut.</p>

<form method="GET" class="bg-white rounded-xl shadow p-4 flex gap-2 mb-4">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama mata pelajaran..." class="border rounded-lg px-3 py-2 flex-1">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
    @if (! empty($q))<a href="{{ route('admin.mapel.index') }}" class="border border-slate-300 px-4 py-2 rounded-lg">Reset</a>@endif
</form>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left">@include('admin._sort-th', ['label' => 'Kelompok', 'key' => 'kelompok'])@include('admin._sort-th', ['label' => 'Nama', 'key' => 'nama'])<th class="px-4 py-3">Jurusan</th>@include('admin._sort-th', ['label' => 'Tingkat', 'key' => 'tingkat'])@include('admin._sort-th', ['label' => 'Urutan', 'key' => 'urutan'])@include('admin._sort-th', ['label' => 'KKM', 'key' => 'kkm_default'])<th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($mapel as $m)
            <tr class="border-t"><td class="px-4 py-3">{{ $m->kelompokLabel() }}</td><td class="px-4 py-3 font-semibold">{{ $m->nama }} @if (! $m->tampil_cetak)<span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded text-xs font-normal">Disembunyikan dari cetak</span>@endif</td><td class="px-4 py-3">{{ $m->jurusan?->nama ?? 'Semua' }}</td><td class="px-4 py-3">{{ $m->tingkat ?? 'Semua' }}</td><td class="px-4 py-3">{{ $m->urutan }}</td><td class="px-4 py-3">{{ $m->kkm_default }}</td><td class="px-4 py-3"><div class="flex gap-3"><a href="{{ route('admin.mapel.edit', ['mapel' => $m->id, 'kembali' => request()->getRequestUri()]) }}" class="text-green-700">Edit</a><form method="POST" action="{{ route('admin.mapel.destroy', $m) }}" onsubmit="return confirm('Hapus mapel ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form></div></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $mapel->links() }}</div>
@endsection
