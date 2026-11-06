@extends('layouts.app')
@section('title', 'Profil Sekolah — SMK Negeri 5 Surakarta')

@section('content')
<h1 class="text-2xl font-bold">Profil Sekolah</h1>
<p class="text-slate-500 mt-1">Kenali SMK Negeri 5 Surakarta lebih dekat.</p>
<div class="grid md:grid-cols-2 gap-4 mt-5">
    @foreach ($profil as $hal)
        <a href="{{ route('profil.show', $hal->slug) }}" class="bg-white rounded-xl shadow p-5 block">
            <h2 class="font-bold text-lg">{{ $hal->judul }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ Str::limit(strip_tags($hal->isi), 140) }}</p>
            <span class="text-green-700 text-sm font-semibold">Baca selengkapnya &rarr;</span>
        </a>
    @endforeach
</div>
@endsection
