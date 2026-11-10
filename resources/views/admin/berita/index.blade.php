@extends('layouts.admin')
@section('title', 'Kelola Berita')

@section('content')
<a href="{{ route('admin.berita.create') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg inline-block mb-4">+ Tulis Berita</a>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead><tr class="bg-slate-100 text-left">@include('admin._sort-th', ['label' => 'Judul', 'key' => 'judul'])<th class="px-4 py-3">Kategori</th>@include('admin._sort-th', ['label' => 'Status', 'key' => 'status'])@include('admin._sort-th', ['label' => 'Terbit', 'key' => 'published_at'])<th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @forelse ($berita as $b)
            <tr class="border-t">
                <td class="px-4 py-3 font-semibold">{{ $b->judul }}</td>
                <td class="px-4 py-3">{{ $b->kategori?->nama ?? '-' }}</td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $b->status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $b->status }}</span></td>
                <td class="px-4 py-3">{{ $b->published_at?->format('d M Y') ?? '-' }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <a href="{{ route('admin.berita.edit', $b) }}" class="text-blue-700">Edit</a>
                    @if ($b->status === 'publish') | <a href="{{ route('berita.show', $b->slug) }}" target="_blank" class="text-green-700">Lihat</a> @endif
                    | <form method="POST" action="{{ route('admin.berita.destroy', $b) }}" class="inline" onsubmit="return confirm('Hapus berita ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada berita.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $berita->links() }}</div>
@endsection
