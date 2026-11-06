@extends('layouts.admin')
@section('title', 'Tahun Ajaran')

@section('content')
<form method="POST" action="{{ route('admin.tahun-ajaran.store') }}" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    @csrf
    <input name="nama" required placeholder="Contoh: 2026-2027" class="border rounded-lg px-3 py-2 flex-1">
    <input type="date" name="tanggal_mulai" class="border rounded-lg px-3 py-2">
    <input type="date" name="tanggal_selesai" class="border rounded-lg px-3 py-2">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah</button>
</form>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Tahun Ajaran</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Aktif</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($tahun as $t)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $t->nama }}</td><td class="px-4 py-3">{{ $t->tanggal_mulai?->format('d M Y') ?? '-' }} s.d. {{ $t->tanggal_selesai?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $t->is_active ? 'Ya' : 'Tidak' }}</td><td class="px-4 py-3 whitespace-nowrap">@if(!$t->is_active)<form method="POST" action="{{ route('admin.tahun-ajaran.aktifkan', $t) }}" class="inline">@csrf<button class="text-green-700 font-semibold">Jadikan Aktif</button></form> | @endif<form method="POST" action="{{ route('admin.tahun-ajaran.destroy', $t) }}" class="inline" onsubmit="return confirm('Hapus tahun ajaran ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tahun->links() }}</div>
@endsection
