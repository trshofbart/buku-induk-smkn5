@extends('layouts.app')
@section('title', 'Beranda — SMK Negeri 5 Surakarta')

@section('content')
{{-- Hero --}}
<section class="bg-green-800 text-white rounded-2xl px-6 py-10 md:px-10">
    <h1 class="text-2xl md:text-4xl font-bold leading-tight">SMK Negeri 5 Surakarta</h1>
    <p class="mt-3 text-green-100 max-w-2xl">Sekolah menengah kejuruan negeri di Kota Surakarta. Membentuk lulusan yang kompeten, berkarakter, dan siap kerja.</p>
    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('profil.index') }}" class="bg-white text-green-800 font-semibold px-4 py-2 rounded-lg">Lihat Profil Sekolah</a>
        <a href="{{ route('berita.index') }}" class="border border-white px-4 py-2 rounded-lg">Berita Terbaru</a>
    </div>
    <div class="mt-8 grid grid-cols-3 gap-3 max-w-lg">
        <div class="bg-green-700 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold">{{ $statistik['siswa_aktif'] }}</p>
            <p class="text-xs text-green-200">Siswa Aktif</p>
        </div>
        <div class="bg-green-700 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold">{{ $statistik['alumni'] }}</p>
            <p class="text-xs text-green-200">Alumni (Lulus)</p>
        </div>
        <div class="bg-green-700 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold">{{ $statistik['berita'] }}</p>
            <p class="text-xs text-green-200">Berita</p>
        </div>
    </div>
</section>

{{-- Berita terbaru --}}
<section class="mt-8">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold">Berita Terbaru</h2>
        <a href="{{ route('berita.index') }}" class="text-green-700 text-sm font-semibold">Lihat semua &rarr;</a>
    </div>
    @if ($berita->isEmpty())
        <p class="text-slate-500 mt-3">Belum ada berita yang diterbitkan.</p>
    @else
        <div class="grid md:grid-cols-3 gap-4 mt-4">
            @foreach ($berita as $b)
                <a href="{{ route('berita.show', $b->slug) }}" class="bg-white rounded-xl shadow overflow-hidden block">
                    @if ($b->foto)
                        <img src="{{ asset('storage/'.$b->foto) }}" alt="{{ $b->judul }}" class="w-full h-40 object-cover">
                    @else
                        <div class="w-full h-40 bg-green-100 flex items-center justify-center text-green-700 font-bold">SMKN 5</div>
                    @endif
                    <div class="p-4">
                        <p class="text-xs text-green-700 font-semibold">{{ $b->kategori?->nama ?? 'Berita' }} &middot; {{ $b->published_at?->format('d M Y') }}</p>
                        <h3 class="font-bold mt-1 leading-snug">{{ $b->judul }}</h3>
                        <p class="text-sm text-slate-500 mt-2">{{ $b->ringkasan }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

{{-- Pengumuman + Profil singkat --}}
<section class="mt-8 grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-lg font-bold">Pengumuman</h2>
        @forelse ($pengumuman as $p)
            <div class="border-b last:border-0 py-3">
                <p class="font-semibold">{{ $p->judul }}</p>
                <p class="text-sm text-slate-500">{{ Str::limit($p->isi, 120) }}</p>
            </div>
        @empty
            <p class="text-slate-500 mt-2 text-sm">Belum ada pengumuman aktif.</p>
        @endforelse
        <a href="{{ route('pengumuman.index') }}" class="text-green-700 text-sm font-semibold">Semua pengumuman &rarr;</a>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-lg font-bold">Profil Sekolah</h2>
        <ul class="mt-2 space-y-2 text-sm">
            @foreach ($profil as $hal)
                <li><a href="{{ route('profil.show', $hal->slug) }}" class="text-green-700 font-semibold">{{ $hal->judul }} &rarr;</a></li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
