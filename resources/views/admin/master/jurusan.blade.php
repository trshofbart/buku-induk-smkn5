@extends('layouts.admin')
@section('title', 'Jurusan')

@section('content')
<form method="POST" action="{{ route('admin.jurusan.store') }}" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    @csrf
    <input name="kode" required placeholder="Kode, contoh: RPL" class="border rounded-lg px-3 py-2">
    <input name="nama" required placeholder="Nama jurusan" class="border rounded-lg px-3 py-2 flex-1">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah</button>
</form>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Jumlah Siswa</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($jurusan as $j)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $j->kode }}</td><td class="px-4 py-3">{{ $j->nama }}</td><td class="px-4 py-3">{{ $j->siswa_count }}</td><td class="px-4 py-3"><form method="POST" action="{{ route('admin.jurusan.destroy', $j) }}" onsubmit="return confirm('Hapus jurusan ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $jurusan->links() }}</div>
@endsection
