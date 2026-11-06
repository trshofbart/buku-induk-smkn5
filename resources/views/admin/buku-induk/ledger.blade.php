@extends('layouts.admin')
@section('title', 'Ledger Semester '.$semester)

@section('content')
<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahunAktif == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <select name="semester" class="border rounded-lg px-3 py-2">
        @for ($i = 1; $i <= 8; $i++)<option value="{{ $i }}" @selected($semester == $i)>Semester {{ $i }}</option>@endfor
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Tampilkan</button>
</form>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">Rata-rata</th><th class="px-4 py-3">Peringkat</th></tr></thead>
        <tbody>
        @forelse ($data as $l)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $l->siswa?->nama }} <span class="text-slate-500 font-normal">({{ $l->siswa?->nis }})</span></td><td class="px-4 py-3">{{ $l->siswa?->kelas?->nama ?? '-' }}</td><td class="px-4 py-3">{{ $l->rata_rata }}</td><td class="px-4 py-3">{{ $l->peringkat ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada data ledger untuk semester ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $data->links() }}</div>

@if (auth()->user()->canEdit())
    <div class="bg-white rounded-xl shadow p-5 mt-4 max-w-xl">
        <h2 class="font-bold">Tambah / Ubah Ledger</h2>
        <form method="POST" action="{{ route('admin.buku-induk.ledger.store') }}" class="grid md:grid-cols-2 gap-3 mt-3">
            @csrf
            <div><label class="text-sm font-semibold">ID Siswa *</label><input type="number" name="siswa_id" required class="w-full border rounded-lg px-3 py-2" placeholder="Lihat ID di Detail Siswa"></div>
            <div><label class="text-sm font-semibold">Tahun Ajaran *</label><select name="tahun_ajaran_id" class="w-full border rounded-lg px-3 py-2">@foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahunAktif == $t->id)>{{ $t->nama }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Semester *</label><select name="semester" class="w-full border rounded-lg px-3 py-2">@for ($i = 1; $i <= 8; $i++)<option value="{{ $i }}" @selected($semester == $i)>{{ $i }}</option>@endfor</select></div>
            <div><label class="text-sm font-semibold">Rata-rata</label><input type="number" step="0.01" min="0" max="100" name="rata_rata" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Peringkat</label><input type="number" min="1" name="peringkat" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Catatan</label><input name="catatan" class="w-full border rounded-lg px-3 py-2"></div>
            <button class="bg-green-700 text-white px-4 py-2 rounded-lg md:col-span-2">Simpan Ledger</button>
        </form>
    </div>
@endif
@endsection
