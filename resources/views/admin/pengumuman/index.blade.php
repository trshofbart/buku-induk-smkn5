@extends('layouts.admin')
@section('title', 'Pengumuman')

@section('content')
<a href="{{ route('admin.pengumuman.create') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg inline-block mb-4">+ Tambah Pengumuman</a>
<form method="GET" class="bg-white rounded-xl shadow p-4 flex gap-2 mb-4">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari judul pengumuman..." class="border rounded-lg px-3 py-2 flex-1">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
    @if (! empty($q))<a href="{{ route('admin.pengumuman.index') }}" class="border border-slate-300 px-4 py-2 rounded-lg">Reset</a>@endif
</form>
<div class="flex justify-end mb-2">
    @include('admin._per-page')
</div>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left">@include('admin._sort-th', ['label' => 'Judul', 'key' => 'judul'])@include('admin._sort-th', ['label' => 'Periode', 'key' => 'tanggal_mulai'])@include('admin._sort-th', ['label' => 'Aktif', 'key' => 'is_active'])<th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @forelse ($pengumuman as $p)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $p->judul }}</td><td class="px-4 py-3">{{ $p->tanggal_mulai?->format('d M Y') ?? '-' }} s.d. {{ $p->tanggal_selesai?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $p->is_active ? 'Ya' : 'Tidak' }}</td><td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('admin.pengumuman.edit', $p) }}" class="text-blue-700">Edit</a> | <form method="POST" action="{{ route('admin.pengumuman.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus pengumuman ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form></td></tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada pengumuman.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $pengumuman->links() }}</div>
@endsection
