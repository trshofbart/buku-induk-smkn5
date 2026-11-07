@extends('layouts.admin')
@section('title', 'Kelengkapan')

@section('content')
@if (session('status'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-4">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif

<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahun?->id == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <select name="semester" class="border rounded-lg px-3 py-2">
        @for ($i = 1; $i <= 8; $i++)<option value="{{ $i }}" @selected($semester == $i)>Semester {{ $i }}</option>@endfor
    </select>
    <select name="kelas_id" class="border rounded-lg px-3 py-2">
        <option value="">— Pilih Kelas —</option>
        @foreach ($daftarKelas as $k)<option value="{{ $k->id }}" @selected($kelas?->id == $k->id)>{{ $k->nama }} ({{ $k->jurusan?->nama }} — {{ $k->tahunAjaran?->nama }})</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Tampilkan</button>
</form>

@if ($kelas && $tahun)
    <div class="bg-white rounded-xl shadow p-5 mb-4 text-sm">
        <p><strong>Kelas:</strong> {{ $kelas->nama }} ({{ $kelas->jurusan?->nama }}) &nbsp;·&nbsp; <strong>Tahun Ajaran:</strong> {{ $tahun?->nama }} &nbsp;·&nbsp; <strong>Semester:</strong> {{ $semester }}</p>
        <p class="text-slate-500 mt-1">Isi kehadiran (hari), sikap, catatan wali kelas, dan status akhir/kenaikan. Data ini tampil di Cetak Buku Induk. Nilai sikap: Amat Baik / Baik / Cukup / Kurang.</p>
    </div>

    <form method="POST" action="{{ route('admin.kelengkapan.store') }}">
        @csrf
        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahun?->id }}">
        <input type="hidden" name="semester" value="{{ $semester }}">
        <div class="bg-white rounded-xl shadow overflow-x-auto mb-4">
            <table class="w-full text-sm min-w-[1100px]">
                <thead>
                    <tr class="bg-slate-100 text-left">
                        <th class="px-3 py-3">No</th><th class="px-3 py-3">NIS</th><th class="px-3 py-3">Nama Siswa</th>
                        <th class="px-2 py-3" style="width:70px;">Sakit</th><th class="px-2 py-3" style="width:70px;">Izin</th><th class="px-2 py-3" style="width:70px;">Alpa</th>
                        <th class="px-2 py-3" style="width:110px;">Kelakuan</th><th class="px-2 py-3" style="width:110px;">Kerapian</th><th class="px-2 py-3" style="width:110px;">Kerajinan</th><th class="px-2 py-3" style="width:110px;">Akhlak Mulia</th>
                        <th class="px-2 py-3">Catatan Wali Kelas</th><th class="px-2 py-3" style="width:150px;">Status Akhir / Kenaikan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($siswa as $i => $s)
                    @php $k = $kelengkapan->get($s->id); @endphp
                    <tr class="border-t align-top">
                        <td class="px-3 py-2">{{ $i + 1 }}</td>
                        <td class="px-3 py-2">{{ $s->nis }}</td>
                        <td class="px-3 py-2 font-semibold">{{ $s->nama }}</td>
                        <td class="px-2 py-2"><input type="number" min="0" max="365" name="kelengkapan[{{ $s->id }}][sakit]" value="{{ old('kelengkapan.'.$s->id.'.sakit', $k?->sakit ?? 0) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        <td class="px-2 py-2"><input type="number" min="0" max="365" name="kelengkapan[{{ $s->id }}][izin]" value="{{ old('kelengkapan.'.$s->id.'.izin', $k?->izin ?? 0) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        <td class="px-2 py-2"><input type="number" min="0" max="365" name="kelengkapan[{{ $s->id }}][alpa]" value="{{ old('kelengkapan.'.$s->id.'.alpa', $k?->alpa ?? 0) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        @foreach (['kelakuan', 'kerapian', 'kerajinan', 'akhlak'] as $kolom)
                            <td class="px-2 py-2">
                                <select name="kelengkapan[{{ $s->id }}][{{ $kolom }}]" class="w-full border rounded-lg px-2 py-1">
                                    <option value="">—</option>
                                    @foreach ($sikap as $nilai)<option value="{{ $nilai }}" @selected(old('kelengkapan.'.$s->id.'.'.$kolom, $k?->$kolom) == $nilai)>{{ $nilai }}</option>@endforeach
                                </select>
                            </td>
                        @endforeach
                        <td class="px-2 py-2"><input type="text" name="kelengkapan[{{ $s->id }}][catatan]" value="{{ old('kelengkapan.'.$s->id.'.catatan', $k?->catatan) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        <td class="px-2 py-2"><input type="text" name="kelengkapan[{{ $s->id }}][kenaikan]" value="{{ old('kelengkapan.'.$s->id.'.kenaikan', $k?->kenaikan) }}" placeholder="Naik ke Tingkat XI" class="w-full border rounded-lg px-2 py-1"></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-6 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (auth()->user()->canEdit() && $siswa->isNotEmpty())
            <button class="bg-green-700 text-white px-5 py-2 rounded-lg mb-6">Simpan Kelengkapan</button>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold mb-1">Kegiatan Ekstrakurikuler / Pengembangan Diri</h2>
        <p class="text-sm text-slate-500">Satu siswa bisa punya beberapa kegiatan dalam satu semester. Kegiatan ini tampil di tabel Ekstrakurikuler pada Cetak Buku Induk.</p>
        @if (auth()->user()->canEdit())
            <form method="POST" action="{{ route('admin.kelengkapan.ekstrakurikuler.store') }}" class="flex flex-wrap gap-2 mt-3">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahun?->id }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <select name="siswa_id" required class="border rounded-lg px-3 py-2">
                    <option value="">— Pilih Siswa —</option>
                    @foreach ($siswa as $s)<option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis }})</option>@endforeach
                </select>
                <input name="kegiatan" required placeholder="Kegiatan, contoh: Pramuka" class="border rounded-lg px-3 py-2 flex-1 min-w-[180px]">
                <input name="nilai" placeholder="Nilai (opsional)" class="border rounded-lg px-3 py-2" style="width:130px;">
                <input name="keterangan" placeholder="Keterangan (opsional)" class="border rounded-lg px-3 py-2 flex-1 min-w-[160px]">
                <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Tambah</button>
            </form>
        @endif
        <table class="w-full text-sm mt-4">
            <thead><tr class="bg-slate-100 text-left"><th class="px-3 py-2">Siswa</th><th class="px-3 py-2">Kegiatan</th><th class="px-3 py-2">Nilai</th><th class="px-3 py-2">Keterangan</th><th class="px-3 py-2">Aksi</th></tr></thead>
            <tbody>
            @forelse ($ekskul as $e)
                <tr class="border-t"><td class="px-3 py-2 font-semibold">{{ $e->siswa?->nama }}</td><td class="px-3 py-2">{{ $e->kegiatan }}</td><td class="px-3 py-2">{{ $e->nilai ?? '—' }}</td><td class="px-3 py-2">{{ $e->keterangan ?? '—' }}</td><td class="px-3 py-2">@if (auth()->user()->canEdit())<form method="POST" action="{{ route('admin.kelengkapan.ekstrakurikuler.destroy', $e) }}" onsubmit="return confirm('Hapus kegiatan ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Belum ada kegiatan ekstrakurikuler semester ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="bg-white rounded-xl shadow p-6 text-center text-slate-500">Pilih tahun ajaran, semester, dan kelas untuk mengisi kelengkapan.</div>
@endif
@endsection
