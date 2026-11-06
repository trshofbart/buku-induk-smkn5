@extends('layouts.app')
@section('title', 'Berita — SMK Negeri 5 Surakarta')

@section('content')
<h1 class="text-2xl font-bold">Berita</h1>

<form method="GET" class="mt-4 flex flex-col sm:flex-row gap-2">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari berita..." class="flex-1 border rounded-lg px-3 py-2">
    <select name="kategori" class="border rounded-lg px-3 py-2">
        <option value="">Semua Kategori</option>
        @foreach ($kategori as $k)
            <option value="{{ $k->slug }}" @selected($kategoriAktif === $k->slug)>{{ $k->nama }}</option>
        @endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
</form>

@if ($berita->isEmpty())
    <p class="text-slate-500 mt-6">Tidak ada berita yang cocok.</p>
@else
    <div class="grid md:grid-cols-3 gap-4 mt-5">
        @foreach ($berita as $b)
            <a href="{{ route('berita.show', $b->slug) }}" class="bg-white rounded-xl shadow overflow-hidden block">
                @if ($b->foto)
                    <img src="{{ asset('storage/'.$b->foto) }}" alt="{{ $b->judul }}" class="w-full h-40 object-cover">
                @else
                    <div class="w-full h-40 bg-green-100 flex items-center justify-center text-green-700 font-bold">SMKN 5</div>
                @endif
                <div class="p-4">
                    <p class="text-xs text-green-700 font-semibold">{{ $b->kategori?->nama ?? 'Berita' }} &middot; {{ $b->published_at?->format('d M Y') }}</p>
                    <h2 class="font-bold mt-1 leading-snug">{{ $b->judul }}</h2>
                    <p class="text-sm text-slate-500 mt-2">{{ $b->ringkasan }}</p>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $berita->links() }}</div>
@endif
@endsection
