@extends('layouts.app')
@section('title', $berita->judul.' — SMK Negeri 5 Surakarta')

@section('content')
<article class="bg-white rounded-xl shadow p-6 max-w-3xl mx-auto">
    <p class="text-sm text-green-700 font-semibold">{{ $berita->kategori?->nama ?? 'Berita' }} &middot; {{ $berita->published_at?->format('d M Y') }} &middot; {{ $berita->views }} kali dibaca</p>
    <h1 class="text-2xl md:text-3xl font-bold mt-2 leading-tight">{{ $berita->judul }}</h1>
    <p class="text-sm text-slate-500 mt-1">Oleh {{ $berita->penulis?->name ?? 'Admin' }}</p>
    @if ($berita->foto)
        <img src="{{ asset('storage/'.$berita->foto) }}" alt="{{ $berita->judul }}" class="w-full max-h-96 object-cover rounded-lg mt-4">
    @endif
    <div class="prose max-w-none mt-5 text-slate-700">{!! $berita->isi !!}</div>
    <a href="{{ route('berita.index') }}" class="inline-block mt-6 text-green-700 font-semibold">&larr; Kembali ke daftar berita</a>
</article>

@if ($terkait->isNotEmpty())
    <section class="max-w-3xl mx-auto mt-8">
        <h2 class="font-bold text-lg">Berita Terkait</h2>
        <div class="grid md:grid-cols-2 gap-4 mt-3">
            @foreach ($terkait as $t)
                <a href="{{ route('berita.show', $t->slug) }}" class="bg-white rounded-xl shadow p-4 block">
                    <p class="text-xs text-green-700 font-semibold">{{ $t->kategori?->nama }}</p>
                    <p class="font-bold mt-1">{{ $t->judul }}</p>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection
