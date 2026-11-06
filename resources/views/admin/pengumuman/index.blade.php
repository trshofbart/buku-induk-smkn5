@extends('layouts.admin')
@section('title', 'Pengumuman')

@section('content')
<a href="{{ route('admin.pengumuman.create') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg inline-block mb-4">+ Tambah Pengumuman</a>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Aktif</th><th class="px-4 py-3">Aksi</th></tr></thead>
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
