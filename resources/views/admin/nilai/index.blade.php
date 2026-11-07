@extends('layouts.admin')
@section('title', 'Input Nilai')

@section('content')
@if (session('status'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-4">{{ session('status') }}</div>@endif

@if (auth()->user()->canEdit())
    <div class="flex flex-wrap items-center gap-3 px-4 py-3 rounded-lg mb-4 text-sm {{ $inputDibuka ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
        <span class="font-bold">{{ $inputDibuka ? 'Input nilai DIBUKA' : 'Input nilai DITUTUP' }}</span>
        <span>{{ $inputDibuka ? 'Admin bisa menyimpan dan mengimport nilai.' : 'Simpan dan import nilai dikunci sampai dibuka kembali.' }}</span>
        <form method="POST" action="{{ route('admin.nilai.toggle-input') }}" class="ml-auto">@csrf<button class="bg-white border px-3 py-1 rounded-lg font-semibold">{{ $inputDibuka ? 'Tutup Input Nilai' : 'Buka Input Nilai' }}</button></form>
    </div>
@endif

<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahun?->id == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <select name="tingkat" class="border rounded-lg px-3 py-2">
        @foreach (['X', 'XI', 'XII'] as $tk)<option value="{{ $tk }}" @selected($tingkat == $tk)>Tingkat {{ $tk }}</option>@endforeach
    </select>
    <select name="semester" class="border rounded-lg px-3 py-2">
        @foreach (['X' => [1, 2], 'XI' => [3, 4], 'XII' => [5, 6]][$tingkat] as $i)<option value="{{ $i }}" @selected($semester == $i)>Semester {{ $i }}</option>@endforeach
    </select>
    <select name="kelas_id" class="border rounded-lg px-3 py-2">
        <option value="">— Pilih Kelas —</option>
        @foreach ($daftarKelas as $k)<option value="{{ $k->id }}" @selected($kelas?->id == $k->id)>{{ $k->nama }} ({{ $k->jurusan?->nama }} — {{ $k->tahunAjaran?->nama }})</option>@endforeach
    </select>
    <select name="mapel_id" class="border rounded-lg px-3 py-2">
        <option value="">— Pilih Mata Pelajaran —</option>
        @foreach ($daftarMapel as $m)<option value="{{ $m->id }}" @selected($mapel?->id == $m->id)>{{ $m->kelompokLabel() }} — {{ $m->nama }}</option>@endforeach
    </select>
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Tampilkan</button>
</form>

@if ($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif
@if (session('import_galat'))
    <div class="bg-amber-100 text-amber-900 px-4 py-3 rounded-lg mb-4 text-sm">
        <p class="font-bold">Sebagian baris tidak bisa diimport:</p>
        <ul class="list-disc pl-5 mt-1">@foreach (session('import_galat') as $g)<li>{{ $g }}</li>@endforeach</ul>
    </div>
@endif

@if ($kelas && $tahun)
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <h2 class="font-bold">Import Nilai dari File (CSV / Excel)</h2>
        <p class="text-sm text-slate-500 mt-1">Kolom file mengikuti contoh file MASTER: <strong>NO, NIS, NISN, NAMA</strong>, lalu satu kolom per mata pelajaran berisi nilai angka (Pengetahuan). Unduh template di bawah — daftar siswa kelas ini sudah terisi, tinggal isi nilai per mapel. File Excel (.xlsx) maupun CSV bisa dipakai.</p>
        <div class="flex flex-wrap items-center gap-3 mt-3">
            <a href="{{ route('admin.nilai.template', ['tahun_ajaran_id' => $tahun->id, 'semester' => $semester, 'kelas_id' => $kelas->id]) }}" class="bg-white border border-green-700 text-green-700 px-4 py-2 rounded-lg text-sm">Unduh Template CSV</a>
            <a href="{{ route('admin.nilai.leger', ['tahun_ajaran_id' => $tahun->id, 'semester' => $semester, 'kelas_id' => $kelas->id]) }}" class="bg-white border border-green-700 text-green-700 px-4 py-2 rounded-lg text-sm">Unduh Leger (CSV)</a>
            @if (auth()->user()->canEdit())
                <form method="POST" action="{{ route('admin.nilai.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahun->id }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
                    <input type="file" name="file" required accept=".csv,.xlsx,.xls,.txt" class="border rounded-lg px-3 py-2 text-sm">
                    <button class="bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Import Nilai</button>
                </form>
            @endif
        </div>
    </div>
@endif

@if ($kelas && $mapel)
    <div class="bg-white rounded-xl shadow p-5 mb-4 text-sm">
        <p><strong>Kelas:</strong> {{ $kelas->nama }} ({{ $kelas->jurusan?->nama }}) &nbsp;·&nbsp; <strong>Tahun Ajaran:</strong> {{ $tahun?->nama }} &nbsp;·&nbsp; <strong>Semester:</strong> {{ $semester }} (Tingkat {{ $tingkat }}) &nbsp;·&nbsp; <strong>Mapel:</strong> {{ $mapel->nama }} (KKM default {{ $mapel->kkm_default }})</p>
        <p class="text-slate-500 mt-1">Isi nilai angka 0–100. Menyimpan nilai memperbarui rata-rata ledger semester ini.</p>
    </div>

    <form method="POST" action="{{ route('admin.nilai.store') }}">
        @csrf
        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahun?->id }}">
        <input type="hidden" name="semester" value="{{ $semester }}">
        <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">No</th><th class="px-4 py-3">NIS</th><th class="px-4 py-3">Nama Siswa</th><th class="px-4 py-3" style="width:110px;">KKM</th><th class="px-4 py-3" style="width:130px;">Nilai Angka</th><th class="px-4 py-3">Transkrip</th></tr></thead>
                <tbody>
                @forelse ($siswa as $i => $s)
                    @php $n = $nilai->get($s->id); @endphp
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $i + 1 }}</td>
                        <td class="px-4 py-2">{{ $s->nis }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $s->nama }}</td>
                        <td class="px-4 py-2"><input type="number" step="0.01" min="0" max="100" name="nilai[{{ $s->id }}][kkm]" value="{{ old('nilai.'.$s->id.'.kkm', $n?->kkm ?? $mapel->kkm_default) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        <td class="px-4 py-2"><input type="number" step="0.01" min="0" max="100" name="nilai[{{ $s->id }}][angka]" value="{{ old('nilai.'.$s->id.'.angka', $n?->nilai_angka) }}" class="w-full border rounded-lg px-2 py-1" placeholder="0–100"></td>
                        <td class="px-4 py-2"><a href="{{ route('admin.nilai.transkrip', $s) }}" class="text-green-700 underline">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (auth()->user()->canEdit() && $siswa->isNotEmpty())
            <button class="bg-green-700 text-white px-5 py-2 rounded-lg mt-4">Simpan Nilai</button>
        @endif
    </form>
@elseif ($kelas)
    <div class="bg-white rounded-xl shadow p-6 text-center text-slate-500">Pilih mata pelajaran untuk menampilkan daftar siswa.</div>
@else
    <div class="bg-white rounded-xl shadow p-6 text-center text-slate-500">Pilih tahun ajaran, semester, dan kelas untuk mulai menginput nilai.</div>
@endif
@endsection
