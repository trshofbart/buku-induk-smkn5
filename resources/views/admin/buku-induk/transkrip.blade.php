@extends('layouts.admin')
@section('title', 'Database Transkrip')

@section('content')
<form method="GET" class="flex gap-2 mb-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIS..." class="flex-1 border rounded-lg px-3 py-2">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        <option value="">Semua Tahun</option>
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected(request('tahun_ajaran_id') == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
</form>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Nomor Transkrip</th><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Tahun</th><th class="px-4 py-3">Tanggal Terbit</th></tr></thead>
        <tbody>
        @forelse ($data as $t)
            <tr class="border-t"><td class="px-4 py-3">{{ $t->nomor_transkrip ?? '-' }}</td><td class="px-4 py-3 font-semibold">{{ $t->siswa?->nama }} <span class="text-slate-500 font-normal">({{ $t->siswa?->nis }})</span></td><td class="px-4 py-3">{{ $t->tahunAjaran?->nama }}</td><td class="px-4 py-3">{{ $t->tanggal_terbit?->format('d M Y') ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada transkrip.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $data->links() }}</div>

@if (auth()->user()->canEdit())
    <div class="bg-white rounded-xl shadow p-5 mt-4 max-w-xl">
        <h2 class="font-bold">Tambah Transkrip</h2>
        <form method="POST" action="{{ route('admin.buku-induk.transkrip.store') }}" class="grid md:grid-cols-2 gap-3 mt-3">
            @csrf
            <div><label class="text-sm font-semibold">ID Siswa *</label><input type="number" name="siswa_id" required class="w-full border rounded-lg px-3 py-2" placeholder="Lihat ID di Detail Siswa"></div>
            <div><label class="text-sm font-semibold">Tahun Ajaran *</label><select name="tahun_ajaran_id" class="w-full border rounded-lg px-3 py-2">@foreach ($tahunAjaran as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Nomor Transkrip</label><input name="nomor_transkrip" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Tanggal Terbit</label><input type="date" name="tanggal_terbit" class="w-full border rounded-lg px-3 py-2"></div>
            <div class="md:col-span-2"><label class="text-sm font-semibold">Keterangan</label><input name="keterangan" class="w-full border rounded-lg px-3 py-2"></div>
            <button class="bg-green-700 text-white px-4 py-2 rounded-lg md:col-span-2">Simpan Transkrip</button>
        </form>
    </div>
@endif
@endsection
