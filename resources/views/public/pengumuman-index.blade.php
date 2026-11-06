@extends('layouts.app')
@section('title', 'Pengumuman — SMK Negeri 5 Surakarta')

@section('content')
<h1 class="text-2xl font-bold">Pengumuman</h1>
@if ($pengumuman->isEmpty())
    <p class="text-slate-500 mt-4">Belum ada pengumuman aktif.</p>
@else
    <div class="space-y-4 mt-5">
        @foreach ($pengumuman as $p)
            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-xs text-slate-500">{{ $p->tanggal_mulai?->format('d M Y') }} @if($p->tanggal_selesai) s.d. {{ $p->tanggal_selesai->format('d M Y') }} @endif</p>
                <h2 class="font-bold text-lg mt-1">{{ $p->judul }}</h2>
                <p class="text-slate-600 mt-1">{{ $p->isi }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $pengumuman->links() }}</div>
@endif
@endsection
