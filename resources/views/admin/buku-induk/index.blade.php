@extends('layouts.admin')
@section('title', 'Buku Induk')

@section('content')
<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-col sm:flex-row gap-2 mb-4">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2 flex-1">
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahun?->id == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Pilih Tahun Ajaran</button>
</form>

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Per Jurusan — {{ $tahun?->nama }}</h2>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($jurusan as $j)
                <li class="flex justify-between border-b last:border-0 py-1"><span>{{ $j->nama }} ({{ $j->kode }})</span><strong>{{ $j->siswa_count }} siswa</strong></li>
            @endforeach
        </ul>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Jalan Pintas</h2>
        <div class="flex flex-col gap-2 mt-3 text-sm">
            <a href="{{ route('admin.buku-induk.nominatif') }}" class="text-green-700 font-semibold">Data Nominatif &rarr;</a>
            <a href="{{ route('admin.nilai.index') }}" class="text-green-700 font-semibold">Input Nilai &rarr;</a>
            <a href="{{ route('admin.siswa.export') }}" class="text-green-700 font-semibold">Export CSV Data Siswa &rarr;</a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-5 mt-4">
    <h2 class="font-bold">Kelas — {{ $tahun?->nama }}</h2>
    <div class="grid md:grid-cols-3 gap-2 mt-3 text-sm">
        @forelse ($kelas as $k)
            <a href="{{ route('admin.buku-induk.per-kelas', $k) }}" class="border rounded-lg px-3 py-2 block">
                <span class="font-semibold">{{ $k->nama }}</span><br>
                <span class="text-slate-500">{{ $k->jurusan?->nama }} &middot; {{ $k->tahunAjaran?->nama }}</span>
            </a>
        @empty
            <p class="text-slate-500">Belum ada kelas untuk tahun ajaran ini.</p>
        @endforelse
    </div>
</div>
@endsection
