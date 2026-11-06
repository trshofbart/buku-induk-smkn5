@extends('layouts.admin')
@section('title', 'Kelas')

@section('content')
<form method="POST" action="{{ route('admin.kelas.store') }}" class="bg-white rounded-xl shadow p-4 grid md:grid-cols-6 gap-2 mb-4">
    @csrf
    <select name="tahun_ajaran_id" required class="border rounded-lg px-3 py-2">@foreach ($tahunAjaran as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach</select>
    <select name="jurusan_id" required class="border rounded-lg px-3 py-2">@foreach ($jurusan as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach</select>
    <select name="tingkat" required class="border rounded-lg px-3 py-2"><option>X</option><option>XI</option><option>XII</option><option>XIII</option></select>
    <input name="nama" required placeholder="Nama kelas, contoh: XII RPL A" class="border rounded-lg px-3 py-2">
    <input name="wali_kelas" placeholder="Wali kelas" class="border rounded-lg px-3 py-2">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah</button>
</form>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">Jurusan</th><th class="px-4 py-3">Tahun</th><th class="px-4 py-3">Wali Kelas</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($kelas as $k)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $k->nama }}</td><td class="px-4 py-3">{{ $k->jurusan?->nama }}</td><td class="px-4 py-3">{{ $k->tahunAjaran?->nama }}</td><td class="px-4 py-3">{{ $k->wali_kelas ?? '-' }}</td><td class="px-4 py-3"><form method="POST" action="{{ route('admin.kelas.destroy', $k) }}" onsubmit="return confirm('Hapus kelas ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $kelas->links() }}</div>
@endsection
