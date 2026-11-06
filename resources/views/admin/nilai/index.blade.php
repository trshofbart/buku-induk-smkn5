@extends('layouts.admin')
@section('title', 'Input Nilai')

@section('content')
@if (session('status'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-4">{{ session('status') }}</div>@endif

<form method="GET" class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-2 mb-4">
    <select name="tahun_ajaran_id" class="border rounded-lg px-3 py-2">
        @foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected($tahun?->id == $t->id)>{{ $t->nama }}</option>@endforeach
    </select>
    <select name="semester" class="border rounded-lg px-3 py-2">
        @for ($i = 1; $i <= 8; $i++)<option value="{{ $i }}" @selected($semester == $i)>Semester {{ $i }} (Tingkat {{ $i <= 2 ? 'X' : ($i <= 4 ? 'XI' : 'XII') }})</option>@endfor
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

@if ($kelas && $mapel)
    <div class="bg-white rounded-xl shadow p-5 mb-4 text-sm">
        <p><strong>Kelas:</strong> {{ $kelas->nama }} ({{ $kelas->jurusan?->nama }}) &nbsp;·&nbsp; <strong>Tahun Ajaran:</strong> {{ $tahun?->nama }} &nbsp;·&nbsp; <strong>Semester:</strong> {{ $semester }} (Tingkat {{ $tingkat }}) &nbsp;·&nbsp; <strong>Mapel:</strong> {{ $mapel->nama }} (KKM default {{ $mapel->kkm_default }})</p>
        <p class="text-slate-500 mt-1">Isi nilai angka 0–100. Predikat huruf dihitung otomatis (A &ge; 92, B 84–91, C 76–83, D &lt; 76). Menyimpan nilai memperbarui rata-rata ledger semester ini.</p>
    </div>

    <form method="POST" action="{{ route('admin.nilai.store') }}">
        @csrf
        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahun?->id }}">
        <input type="hidden" name="semester" value="{{ $semester }}">
        <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead><tr class="bg-slate-100 text-left"><th class="px-4 py-3">No</th><th class="px-4 py-3">NIS</th><th class="px-4 py-3">Nama Siswa</th><th class="px-4 py-3" style="width:110px;">KKM</th><th class="px-4 py-3" style="width:130px;">Nilai Angka</th><th class="px-4 py-3">Predikat</th><th class="px-4 py-3">Transkrip</th></tr></thead>
                <tbody>
                @forelse ($siswa as $i => $s)
                    @php $n = $nilai->get($s->id); @endphp
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $i + 1 }}</td>
                        <td class="px-4 py-2">{{ $s->nis }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $s->nama }}</td>
                        <td class="px-4 py-2"><input type="number" step="0.01" min="0" max="100" name="nilai[{{ $s->id }}][kkm]" value="{{ old('nilai.'.$s->id.'.kkm', $n?->kkm ?? $mapel->kkm_default) }}" class="w-full border rounded-lg px-2 py-1"></td>
                        <td class="px-4 py-2"><input type="number" step="0.01" min="0" max="100" name="nilai[{{ $s->id }}][angka]" value="{{ old('nilai.'.$s->id.'.angka', $n?->nilai_angka) }}" class="w-full border rounded-lg px-2 py-1" placeholder="0–100"></td>
                        <td class="px-4 py-2 font-bold">{{ $n?->nilai_huruf ?? '–' }}</td>
                        <td class="px-4 py-2"><a href="{{ route('admin.nilai.transkrip', $s) }}" class="text-green-700 underline">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
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
