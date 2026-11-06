@extends('layouts.admin')
@section('title', 'Data Siswa')

@section('content')
<div class="flex flex-wrap gap-2 mb-4">
    @if (auth()->user()->canEdit())
        <a href="{{ route('admin.siswa.create') }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg">+ Tambah Siswa</a>
        <a href="{{ route('admin.siswa.import') }}" class="bg-white border text-sm px-3 py-2 rounded-lg">Import CSV</a>
    @endif
    <a href="{{ route('admin.siswa.export', request()->query()) }}" class="bg-white border text-sm px-3 py-2 rounded-lg">Export CSV</a>
</div>

<form method="GET" class="bg-white rounded-xl shadow p-4 grid md:grid-cols-6 gap-2 mb-4">
    <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Cari NIS / NISN / Nama..." class="border rounded-lg px-3 py-2 md:col-span-2">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        <option value="">Semua Tahun Ajaran</option>
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected(($filter['tahun_ajaran_id'] ?? '') == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <select name="jurusan_id" class="border rounded-lg px-3 py-2">
        <option value="">Semua Jurusan</option>
        @foreach ($jurusan as $j)<option value="{{ $j->id }}" @selected(($filter['jurusan_id'] ?? '') == $j->id)>{{ $j->nama }}</option>@endforeach
    </select>
    <select name="status" class="border rounded-lg px-3 py-2">
        <option value="">Semua Status</option>
        @foreach (\App\Models\Siswa::statusList() as $k => $v)<option value="{{ $k }}" @selected(($filter['status'] ?? '') === $k)>{{ $v }}</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-3 py-2 rounded-lg">Filter</button>
</form>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">NIS / NISN</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Jurusan / Kelas</th><th class="px-4 py-3">Tahun</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @forelse ($siswa as $s)
            <tr class="border-t">
                <td class="px-4 py-3">{{ $s->nis }}<br><span class="text-slate-500 text-xs">{{ $s->nisn }}</span></td>
                <td class="px-4 py-3 font-semibold">{{ $s->nama }}</td>
                <td class="px-4 py-3">{{ $s->jurusan?->kode }} / {{ $s->kelas?->nama ?? '-' }}</td>
                <td class="px-4 py-3">{{ $s->tahunAjaran?->nama }}</td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $s->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-slate-200' }}">{{ $s->statusLabel() }}</span></td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <a href="{{ route('admin.siswa.show', $s) }}" class="text-green-700 font-semibold">Detail</a>
                    @if (auth()->user()->canEdit())
                        | <a href="{{ route('admin.siswa.edit', $s) }}" class="text-blue-700">Edit</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada siswa yang cocok. Ubah filter atau tambah siswa baru.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $siswa->links() }}</div>
@endsection
