@extends('layouts.admin')
@section('title', 'Profil Sekolah')

@section('content')
<p class="text-slate-500 -mt-2 mb-4">Edit halaman profil — perubahan langsung tampil di halaman depan.</p>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Halaman</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Aktif</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($profil as $p)
            <tr class="border-t">
                <td class="px-4 py-3 font-semibold">{{ $p->judul }}</td>
                <td class="px-4 py-3">/profil/{{ $p->slug }}</td>
                <td class="px-4 py-3">{{ $p->is_active ? 'Ya' : 'Tidak' }}</td>
                <td class="px-4 py-3"><a href="{{ route('admin.profil.edit', $p) }}" class="text-blue-700">Edit</a> | <a href="{{ route('profil.show', $p->slug) }}" target="_blank" class="text-green-700">Lihat</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
