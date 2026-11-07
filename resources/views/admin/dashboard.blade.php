@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<p class="text-slate-500 -mt-2 mb-4">Selamat datang, {{ auth()->user()->name }}. Tahun ajaran aktif: <strong>{{ $tahunAktif?->nama ?? '-' }}</strong></p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="bg-white rounded-xl shadow p-4"><p class="text-2xl font-bold">{{ $totalSiswa }}</p><p class="text-sm text-slate-500">Total Siswa</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-2xl font-bold">{{ $siswaAktif }}</p><p class="text-sm text-slate-500">Siswa Aktif</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-2xl font-bold">{{ $siswaBaru }}</p><p class="text-sm text-slate-500">Siswa Tahun Aktif</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-2xl font-bold">{{ $alumni }}</p><p class="text-sm text-slate-500">Alumni (Lulus)</p></div>
</div>

<div class="grid md:grid-cols-2 gap-4 mt-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Siswa per Jurusan</h2>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($perJurusan as $row)
                <li class="flex justify-between border-b last:border-0 py-1"><span>{{ $row->jurusan?->nama ?? '-' }}</span><strong>{{ $row->jumlah }}</strong></li>
            @endforeach
        </ul>
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('admin.siswa.index') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg">Data Siswa</a>
            <a href="{{ route('admin.buku-induk.index') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg">Buku Induk</a>
            <a href="{{ route('admin.siswa.export') }}" class="border border-green-700 text-green-700 text-sm px-3 py-2 rounded-lg">Export Excel</a>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Berita Terbaru @if($beritaDraft) <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">{{ $beritaDraft }} draft</span> @endif</h2>
        <ul class="mt-2 space-y-2 text-sm">
            @foreach ($beritaTerbaru as $b)
                <li class="border-b last:border-0 pb-2"><span class="text-xs px-2 py-0.5 rounded-full {{ $b->status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $b->status }}</span> {{ $b->judul }}</li>
            @endforeach
        </ul>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-5 mt-4">
    <h2 class="font-bold">Aktivitas Terakhir</h2>
    <ul class="mt-2 space-y-1 text-sm">
        @forelse ($logTerbaru as $log)
            <li class="border-b last:border-0 py-1"><span class="text-slate-500">{{ $log->created_at?->format('d M Y H:i') }}</span> — <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>: {{ $log->aksi }} — {{ $log->keterangan }}</li>
        @empty
            <li class="text-slate-500">Belum ada aktivitas tercatat.</li>
        @endforelse
    </ul>
</div>
@endsection
