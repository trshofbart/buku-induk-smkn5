@extends('layouts.admin')
@section('title', 'Buku Induk: '.$kelas->nama)

@section('content')
<p class="text-slate-500 -mt-2 mb-4">{{ $kelas->jurusan?->nama }} &middot; Tahun Ajaran {{ $kelas->tahunAjaran?->nama }} &middot; Wali Kelas: {{ $kelas->wali_kelas ?? '-' }}</p>
<form method="GET" class="flex gap-2 mb-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari NIS / Nama..." class="flex-1 border rounded-lg px-3 py-2">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
</form>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">No</th><th class="px-4 py-3">NIS</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">JK</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @forelse ($siswa as $i => $s)
            <tr class="border-t"><td class="px-4 py-3">{{ $siswa->firstItem() + $i }}</td><td class="px-4 py-3">{{ $s->nis }}</td><td class="px-4 py-3 font-semibold">{{ $s->nama }}</td><td class="px-4 py-3">{{ $s->jenis_kelamin }}</td><td class="px-4 py-3">{{ $s->statusLabel() }}</td><td class="px-4 py-3"><a href="{{ route('admin.siswa.show', $s) }}" class="text-green-700 font-semibold">Detail</a></td></tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $siswa->links() }}</div>
<a href="{{ route('admin.buku-induk.index') }}" class="inline-block mt-4 text-green-700 font-semibold">&larr; Kembali ke Buku Induk</a>
@endsection
