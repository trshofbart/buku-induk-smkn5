@extends('layouts.admin')
@section('title', 'Detail Siswa: '.$siswa->nama)

@section('content')
<div class="flex flex-wrap gap-2 mb-4">
    @if (auth()->user()->canEdit())
        <a href="{{ route('admin.siswa.edit', $siswa) }}" class="bg-green-700 text-white text-sm px-3 py-2 rounded-lg">Edit</a>
    @endif
    <a href="{{ route('admin.siswa.cetak', $siswa) }}" class="bg-white border text-sm px-3 py-2 rounded-lg">Cetak</a>
    <a href="{{ route('admin.nilai.transkrip', $siswa) }}" class="bg-white border text-sm px-3 py-2 rounded-lg">Transkrip Nilai</a>
    <form method="GET" action="{{ route('admin.siswa.rapor', $siswa) }}" class="inline-flex gap-1 bg-white border rounded-lg px-2 py-1 text-sm items-center">
        <label class="text-slate-500">Rapor</label>
        <select name="semester" class="border rounded px-1 py-1">@for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}">Smt {{ $i }}</option>@endfor</select>
        <button class="bg-green-700 text-white px-2 py-1 rounded">Cetak</button>
    </form>
    <a href="{{ route('admin.siswa.index') }}" class="bg-white border text-sm px-3 py-2 rounded-lg">&larr; Kembali</a>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Biodata</h2>
        <dl class="mt-2 text-sm space-y-1">
            <dt class="text-slate-500">NIS / NISN</dt><dd class="font-semibold">{{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</dd>
            <dt class="text-slate-500">Nama</dt><dd class="font-semibold">{{ $siswa->nama }}</dd>
            <dt class="text-slate-500">Jenis Kelamin</dt><dd>{{ $siswa->jenisKelaminLabel() }}</dd>
            <dt class="text-slate-500">Tempat, Tanggal Lahir</dt><dd>{{ $siswa->tempat_lahir }}, {{ $siswa->tanggal_lahir?->format('d M Y') ?? '-' }}</dd>
            <dt class="text-slate-500">Agama</dt><dd>{{ $siswa->agama ?? '-' }}</dd>
            <dt class="text-slate-500">Alamat</dt><dd>{{ $siswa->alamat ?? '-' }}</dd>
            <dt class="text-slate-500">No HP</dt><dd>{{ $siswa->no_hp ?? '-' }}</dd>
            <dt class="text-slate-500">Jurusan / Kelas</dt><dd>{{ $siswa->jurusan?->nama }} / {{ $siswa->kelas?->nama ?? '-' }}</dd>
            <dt class="text-slate-500">Tahun Ajaran</dt><dd>{{ $siswa->tahunAjaran?->nama }}</dd>
            <dt class="text-slate-500">Status</dt><dd>{{ $siswa->statusLabel() }}</dd>
            <dt class="text-slate-500">Sekolah Asal</dt><dd>{{ $siswa->sekolah_asal ?? '-' }} — {{ $siswa->alamat_sekolah_asal ?? '-' }}</dd>
            <dt class="text-slate-500">Tahun Lulus / No Ijazah</dt><dd>{{ $siswa->tahun_lulus ?? '-' }} / {{ $siswa->no_ijazah ?? '-' }}</dd>
            <dt class="text-slate-500">Diterima di Tingkat</dt><dd>{{ $siswa->diterima_tingkat ?? '-' }} — {{ $siswa->tanggal_masuk?->format('d M Y') ?? '-' }}</dd>
            <dt class="text-slate-500">Mutasi</dt><dd>Pindah ke: {{ $siswa->mutasi_ke ?? '-' }}; dari: {{ $siswa->mutasi_dari ?? '-' }}</dd>
        </dl>
    </div>
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold">Orang Tua / Wali</h2>
            <dl class="mt-2 text-sm space-y-1">
                <dt class="text-slate-500">Ayah</dt><dd>{{ $siswa->orangTua?->nama_ayah ?? '-' }} ({{ $siswa->orangTua?->pekerjaan_ayah ?? '-' }})</dd>
                <dt class="text-slate-500">Ibu</dt><dd>{{ $siswa->orangTua?->nama_ibu ?? '-' }} ({{ $siswa->orangTua?->pekerjaan_ibu ?? '-' }})</dd>
                <dt class="text-slate-500">Wali</dt><dd>{{ $siswa->orangTua?->nama_wali ?? '-' }} ({{ $siswa->orangTua?->pekerjaan_wali ?? '-' }})</dd>
                <dt class="text-slate-500">Alamat Orang Tua</dt><dd>{{ $siswa->orangTua?->alamat_ortu ?? '-' }}</dd>
                <dt class="text-slate-500">Alamat Wali</dt><dd>{{ $siswa->orangTua?->alamat_wali ?? '-' }}</dd>
                <dt class="text-slate-500">No HP Orang Tua</dt><dd>{{ $siswa->orangTua?->no_hp_ortu ?? '-' }}</dd>
                <dt class="text-slate-500">No HP Wali</dt><dd>{{ $siswa->orangTua?->no_hp_wali ?? '-' }}</dd>
            </dl>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold">Riwayat Kelas</h2>
            <ul class="mt-2 text-sm space-y-1">
                @forelse ($siswa->riwayatKelas as $r)
                    <li>{{ $r->kelas?->nama }} — {{ $r->tahunAjaran?->nama }} ({{ $r->status_naik ?? '-' }})</li>
                @empty
                    <li class="text-slate-500">Belum ada riwayat kelas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4 mt-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Ledger (Semester 1–8)</h2>
        <table class="w-full text-sm mt-2">
            <thead><tr class="bg-slate-100 text-left"><th class="px-2 py-1">Semester</th><th class="px-2 py-1">Rata-rata</th><th class="px-2 py-1">Peringkat</th></tr></thead>
            <tbody>
            @forelse ($siswa->ledger as $l)
                <tr class="border-t"><td class="px-2 py-1">{{ $l->semester }}</td><td class="px-2 py-1">{{ $l->rata_rata }}</td><td class="px-2 py-1">{{ $l->peringkat ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-2 py-3 text-slate-500">Belum ada data ledger.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-bold">Transkrip</h2>
        <ul class="mt-2 text-sm space-y-1">
            @forelse ($siswa->transkrip as $t)
                <li>{{ $t->nomor_transkrip ?? '-' }} — {{ $t->tahunAjaran?->nama }} — terbit {{ $t->tanggal_terbit?->format('d M Y') ?? '-' }}</li>
            @empty
                <li class="text-slate-500">Belum ada transkrip.</li>
            @endforelse
        </ul>
        <h2 class="font-bold mt-4">Dokumen</h2>
        <ul class="mt-2 text-sm space-y-1">
            @forelse ($siswa->dokumen as $d)
                <li>{{ $d->jenisLabel() }} — {{ $d->nama_file }}</li>
            @empty
                <li class="text-slate-500">Belum ada dokumen. (Upload dokumen direncanakan Fase 2.)</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
