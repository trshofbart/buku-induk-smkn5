@extends('layouts.app')
@section('title', $halaman->judul.' — SMK Negeri 5 Surakarta')

@section('content')
<div class="grid md:grid-cols-4 gap-6">
    <aside class="bg-white rounded-xl shadow p-4 h-fit">
        <h2 class="font-bold mb-2">Profil Sekolah</h2>
        <ul class="space-y-2 text-sm">
            @foreach ($daftar as $d)
                <li><a href="{{ route('profil.show', $d->slug) }}" class="{{ $d->slug === $halaman->slug ? 'font-bold text-green-700' : 'text-slate-600' }}">{{ $d->judul }}</a></li>
            @endforeach
        </ul>
    </aside>
    <article class="md:col-span-3 bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold">{{ $halaman->judul }}</h1>
        @if ($halaman->gambar)
            <img src="{{ asset('storage/'.$halaman->gambar) }}" alt="{{ $halaman->judul }}" class="w-full max-h-80 object-cover rounded-lg mt-4">
        @endif
        <div class="prose max-w-none mt-4 text-slate-700">{!! $halaman->isi !!}</div>
    </article>
</div>
@endsection
