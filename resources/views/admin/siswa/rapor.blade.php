@extends('layouts.print')
@section('title', 'Rapor Semester '.$semester.': '.$siswa->nama)

@section('content')
<div class="kop">
    <h1>{{ $sekolah->nama_sekolah }}</h1>
    <p>{{ $sekolah->alamat }}</p>
    <p>Telp: {{ $sekolah->telepon ?? '-' }} &middot; Email: {{ $sekolah->email ?? '-' }}</p>
</div>

<p class="judul-bagian">Rapor Peserta Didik</p>
<table class="identitas">
    <tr><td class="lbl">NAMA SISWA</td><td class="titik">:</td><td>{{ strtoupper($siswa->nama) }}</td></tr>
    <tr><td class="lbl">NOMOR INDUK SISWA</td><td class="titik">:</td><td>{{ $siswa->nis }}</td></tr>
    <tr><td class="lbl">NISN</td><td class="titik">:</td><td>{{ $siswa->nisn ?? '' }}</td></tr>
    <tr><td class="lbl">KELAS</td><td class="titik">:</td><td>{{ $siswa->kelas?->nama ?? '' }}</td></tr>
    <tr><td class="lbl">BIDANG STUDI KEAHLIAN</td><td class="titik">:</td><td>{{ strtoupper($sekolah->bidang_studi_keahlian ?? 'Teknologi dan Rekayasa') }}</td></tr>
    <tr><td class="lbl">PROGRAM STUDI KEAHLIAN</td><td class="titik">:</td><td>{{ strtoupper($siswa->jurusan?->program_studi ?? $sekolah->bidang_studi_keahlian ?? 'Teknologi dan Rekayasa') }}</td></tr>
    <tr><td class="lbl">KOMPETENSI KEAHLIAN</td><td class="titik">:</td><td>{{ strtoupper($siswa->jurusan?->nama ?? '') }}</td></tr>
    <tr><td class="lbl">SEMESTER</td><td class="titik">:</td><td>{{ $semester }} (Tingkat {{ $tingkat }})</td></tr>
    <tr><td class="lbl">TAHUN AJARAN</td><td class="titik">:</td><td>{{ $tahun?->nama ?? '' }}</td></tr>
</table>

<p class="subjudul">A. NILAI HASIL BELAJAR</p>
<table class="nilai">
    <thead>
        <tr><th style="width:26px;">NO</th><th>MATA PELAJARAN</th><th style="width:60px;">KKM</th><th style="width:80px;">NILAI ANGKA</th></tr>
    </thead>
    <tbody>
        @php $no = 0; @endphp
        @foreach ($mapel->groupBy('kelompok') as $kelompok => $daftar)
            <tr class="grup"><td colspan="4">{{ \App\Models\Mapel::KELOMPOK[$kelompok] ?? $kelompok }}</td></tr>
            @foreach ($daftar as $m)
                @php $n = $nilai->get($m->id); $no++; @endphp
                <tr>
                    <td class="no">{{ $no }}</td>
                    <td>{{ $m->nama }}</td>
                    <td class="angka">{{ rtrim(rtrim($n?->kkm ?? $m->kkm_default, '0'), '.') }}</td>
                    <td class="angka">{{ $n ? rtrim(rtrim($n->nilai_angka, '0'), '.') : '&ndash;' }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td colspan="3" style="text-align:right; font-weight:bold;">RATA-RATA</td>
            <td class="angka"><strong>{{ $ledger?->rata_rata ?? '' }}</strong></td>
        </tr>
    </tbody>
</table>
<p class="catatan">*) KKM = Kriteria Ketuntasan Minimal.</p>

<p class="subjudul">B. KETIDAKHADIRAN</p>
<table class="nilai">
    <thead><tr><th>SAKIT (hari)</th><th>IJIN (hari)</th><th>TANPA KETERANGAN (hari)</th></tr></thead>
    <tbody><tr><td class="tengah tinggi">{{ $kelengkapan?->sakit ?? '' }}</td><td class="tengah">{{ $kelengkapan?->izin ?? '' }}</td><td class="tengah">{{ $kelengkapan?->alpa ?? '' }}</td></tr></tbody>
</table>

<p class="subjudul">C. KEGIATAN EKSTRAKURIKULER / PENGEMBANGAN DIRI</p>
<table class="nilai">
    <thead><tr><th style="width:26px;">NO</th><th>KEGIATAN</th><th style="width:80px;">NILAI</th><th style="width:30%;">KETERANGAN</th></tr></thead>
    <tbody>
        @forelse ($ekskul as $i => $e)
            <tr><td class="no">{{ $i + 1 }}</td><td>{{ $e->kegiatan }}</td><td class="tengah">{{ $e->nilai ?? '' }}</td><td>{{ $e->keterangan ?? '' }}</td></tr>
        @empty
            <tr><td class="no">1</td><td class="tinggi"></td><td></td><td></td></tr>
            <tr><td class="no">2</td><td class="tinggi"></td><td></td><td></td></tr>
            <tr><td class="no">3</td><td class="tinggi"></td><td></td><td></td></tr>
        @endforelse
    </tbody>
</table>

<p class="subjudul">D. CATATAN WALI KELAS</p>
<table class="nilai">
    <tbody><tr><td style="height:48px;">{{ $kelengkapan?->catatan ?? '' }}</td></tr></tbody>
</table>

<p style="text-align:right; margin-top:28px;">{{ $sekolah->tempat_ttd ?? 'Surakarta' }}, {{ ($sekolah->tanggal_rapor ?? now())->locale('id')->translatedFormat('d F Y') }}</p>
<table class="ttd">
    <tr><td style="width:50%;">Kepala Sekolah</td><td>Wali Kelas</td></tr>
    <tr><td class="ruang"></td><td class="ruang"></td></tr>
    <tr><td><strong>({{ $sekolah->kepala_sekolah ?? '........................' }})</strong></td><td>(........................)</td></tr>
</table>
@endsection
