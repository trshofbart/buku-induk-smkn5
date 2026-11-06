@extends('layouts.print')
@section('title', 'Buku Induk: '.$siswa->nama)

@php
    $ortu = $siswa->orangTua;
    $jk = $siswa->jenis_kelamin === 'L' ? 'Laki - laki' : 'Perempuan';
    $tglLahir = $siswa->tanggal_lahir
        ? trim(strtoupper($siswa->tempat_lahir ?? '').($siswa->tempat_lahir ? ', ' : '').$siswa->tanggal_lahir->locale('id')->translatedFormat('d F Y'))
        : ($siswa->tempat_lahir ?? '');
    $tglMasuk = $siswa->tanggal_masuk ? $siswa->tanggal_masuk->locale('id')->translatedFormat('d F Y') : '';
    $pekerjaanOrtu = collect([
        $ortu?->pekerjaan_ayah ? 'Ayah: '.$ortu->pekerjaan_ayah : null,
        $ortu?->pekerjaan_ibu ? 'Ibu: '.$ortu->pekerjaan_ibu : null,
    ])->filter()->implode('; ');
    // Kelompok mata pelajaran mengikuti HAL_PRINT file MASTER (jumlah baris per kelompok).
    $kelompokMapel = [
        ['judul' => 'A. Muatan Nasional', 'baris' => 6],
        ['judul' => 'B. Muatan Kewilayahan', 'baris' => 3],
        ['judul' => 'C. Muatan Peminatan Kejuruan', 'baris' => 0],
        ['judul' => 'C1. Dasar Bidang Keahlian', 'baris' => 3],
        ['judul' => 'C2. Dasar Program Keahlian', 'baris' => 3],
        ['judul' => 'C3. Kompetensi Keahlian', 'baris' => 5],
    ];
@endphp

@section('content')
<div class="kop">
    <h1>{{ $sekolah->nama_sekolah }}</h1>
    <p>{{ $sekolah->alamat }}</p>
    <p>Telp: {{ $sekolah->telepon ?? '-' }} &middot; Email: {{ $sekolah->email ?? '-' }}</p>
</div>

{{-- ============ BAGIAN 1: KETERANGAN TENTANG PESERTA DIDIK (HAL_PRINT baris 2-38) ============ --}}
<p class="judul-bagian">Keterangan Tentang Peserta Didik</p>
<table class="bio">
    <tr><td class="no">1.</td><td class="lbl">Nama Peserta Didik</td><td class="titik">:</td><td class="isi">{{ strtoupper($siswa->nama) }}</td></tr>
    <tr><td class="no">2.</td><td class="lbl">Nomor Induk Siswa</td><td class="titik">:</td><td class="isi">{{ $siswa->nis }}</td></tr>
    <tr><td class="no">3.</td><td class="lbl">Jenis Kelamin</td><td class="titik">:</td><td class="isi">{{ $jk }}</td></tr>
    <tr><td class="no">4.</td><td class="lbl">Tempat dan Tanggal Lahir</td><td class="titik">:</td><td class="isi">{{ $tglLahir }}</td></tr>
    <tr><td class="no">5.</td><td class="lbl">Agama</td><td class="titik">:</td><td class="isi">{{ $siswa->agama ?? '' }}</td></tr>
    <tr><td class="no">6.</td><td class="lbl">Alamat Peserta Didik</td><td class="titik">:</td><td class="isi">{{ $siswa->alamat ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">Telepon</td><td class="titik">:</td><td>{{ $siswa->no_hp ?? '' }}</td></tr>
    <tr><td class="no">7.</td><td class="lbl">Sekolah Asal</td><td class="titik">:</td><td class="isi">{{ $siswa->sekolah_asal ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">Alamat Sekolah</td><td class="titik">:</td><td>{{ $siswa->alamat_sekolah_asal ?? '' }}</td></tr>
    <tr><td class="no">8.</td><td class="lbl">Surat Tanda Tamat Belajar / Ijazah / Surat Tanda Kelulusan</td><td class="titik"></td><td></td></tr>
    <tr><td class="no"></td><td class="lbl sub">a. Tahun Lulus</td><td class="titik">:</td><td>{{ $siswa->tahun_lulus ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">b. No Ijazah</td><td class="titik">:</td><td>{{ $siswa->no_ijazah ?? '' }}</td></tr>
    <tr><td class="no">9.</td><td class="lbl">Diterima di sekolah ini,</td><td class="titik"></td><td></td></tr>
    <tr><td class="no"></td><td class="lbl sub">a. Di tingkat</td><td class="titik">:</td><td>{{ $siswa->diterima_tingkat ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">b. Pada tanggal</td><td class="titik">:</td><td>{{ $tglMasuk }}</td></tr>
    <tr><td class="no">10.</td><td class="lbl">Nama Orang Tua,</td><td class="titik"></td><td></td></tr>
    <tr><td class="no"></td><td class="lbl sub">a. Ayah</td><td class="titik">:</td><td class="isi">{{ $ortu?->nama_ayah ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">b. Ibu</td><td class="titik">:</td><td class="isi">{{ $ortu?->nama_ibu ?? '' }}</td></tr>
    <tr><td class="no">11.</td><td class="lbl">Alamat Orang Tua</td><td class="titik">:</td><td class="isi">{{ $ortu?->alamat_ortu ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">Telepon</td><td class="titik">:</td><td>{{ $ortu?->no_hp_ortu ?? '' }}</td></tr>
    <tr><td class="no">12.</td><td class="lbl">Pekerjaan Orang Tua</td><td class="titik">:</td><td>{{ $pekerjaanOrtu }}</td></tr>
    <tr><td class="no">13.</td><td class="lbl">Nama Wali</td><td class="titik">:</td><td class="isi">{{ $ortu?->nama_wali ?? '' }}</td></tr>
    <tr><td class="no">14.</td><td class="lbl">Alamat Wali</td><td class="titik">:</td><td class="isi">{{ $ortu?->alamat_wali ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">Telepon</td><td class="titik">:</td><td>{{ $ortu?->no_hp_wali ?? '' }}</td></tr>
    <tr><td class="no">15.</td><td class="lbl">Pekerjaan Wali</td><td class="titik">:</td><td>{{ $ortu?->pekerjaan_wali ?? '' }}</td></tr>
    <tr><td class="no">16.</td><td class="lbl">Mutasi</td><td class="titik">:</td><td></td></tr>
    <tr><td class="no"></td><td class="lbl sub">a. Pindah ke</td><td class="titik">:</td><td>{{ $siswa->mutasi_ke ?? '' }}</td></tr>
    <tr><td class="no"></td><td class="lbl sub">b. Pindahan dari</td><td class="titik">:</td><td>{{ $siswa->mutasi_dari ?? '' }}</td></tr>
</table>

{{-- ============ BAGIAN 2: LAPORAN PENILAIAN HASIL BELAJAR (HAL_PRINT baris 51-335) ============ --}}
<div class="halaman-baru"></div>
<p class="judul-bagian">Laporan Penilaian Hasil Belajar Peserta Didik</p>
<table class="identitas">
    <tr><td class="lbl">NAMA SISWA</td><td class="titik">:</td><td>{{ strtoupper($siswa->nama) }}</td></tr>
    <tr><td class="lbl">NOMOR INDUK SISWA</td><td class="titik">:</td><td>{{ $siswa->nis }}</td></tr>
    <tr><td class="lbl">NISN</td><td class="titik">:</td><td>{{ $siswa->nisn ?? '' }}</td></tr>
    <tr><td class="lbl">BIDANG STUDI KEAHLIAN</td><td class="titik">:</td><td></td></tr>
    <tr><td class="lbl">PROGRAM STUDI KEAHLIAN</td><td class="titik">:</td><td></td></tr>
    <tr><td class="lbl">KOMPETENSI KEAHLIAN</td><td class="titik">:</td><td>{{ strtoupper($siswa->jurusan?->nama ?? '') }}</td></tr>
</table>

@foreach (['X', 'XI', 'XII'] as $tingkat)
    @php $d = $tingkatData[$tingkat]; @endphp
    <p class="subjudul">TAHUN DIKLAT : {{ $d['tahun'] ?? '&ndash;' }} &nbsp;&nbsp; TINGKAT : {{ $tingkat }}</p>
    <table class="nilai">
        <thead>
            <tr>
                <th rowspan="2" style="width:26px;">NO</th>
                <th rowspan="2">MATA PELAJARAN</th>
                <th colspan="3">SEMESTER : {{ $tingkat === 'X' ? 1 : ($tingkat === 'XI' ? 3 : 5) }}</th>
                <th colspan="3">SEMESTER : {{ $tingkat === 'X' ? 2 : ($tingkat === 'XI' ? 4 : 6) }}</th>
            </tr>
            <tr>
                <th>KKM *)</th><th>NILAI ANGKA</th><th>HURUF</th>
                <th>KKM *)</th><th>NILAI ANGKA</th><th>HURUF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kelompokMapel as $kel)
                <tr class="grup"><td colspan="8">{{ $kel['judul'] }}</td></tr>
                @for ($i = 1; $i <= $kel['baris']; $i++)
                    <tr><td class="no">{{ $i }}</td><td class="tinggi"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td></tr>
                @endfor
            @endforeach
            <tr>
                <td colspan="2" style="text-align:right; font-weight:bold;">RATA-RATA</td>
                <td class="angka"></td><td class="angka"><strong>{{ $d['smt1']?->rata_rata ?? '' }}</strong></td><td class="angka"></td>
                <td class="angka"></td><td class="angka"><strong>{{ $d['smt2']?->rata_rata ?? '' }}</strong></td><td class="angka"></td>
            </tr>
        </tbody>
    </table>
@endforeach
<p class="catatan">*) KKM = Kriteria Ketuntasan Minimal. Baris mata pelajaran yang masih kosong dapat diisi manual — nilai per mata pelajaran belum tersimpan di sistem (saat ini sistem hanya menyimpan rata-rata per semester).</p>

{{-- Ekstrakurikuler --}}
<p class="subjudul">KEGIATAN EKSTRAKURIKULER / PENGEMBANGAN DIRI</p>
<table class="nilai">
    <thead><tr><th style="width:60px;">TINGKAT</th><th style="width:70px;">SEMESTER</th><th>KEGIATAN EKSTRAKURIKULER / PENGEMBANGAN DIRI</th><th style="width:60px;">NILAI</th><th style="width:25%;">KETERANGAN</th></tr></thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            @foreach ([1, 2] as $smt)
                @for ($i = 1; $i <= 3; $i++)
                    <tr>
                        @if ($i === 1)<td class="tengah" rowspan="3">{{ $tingkat }}</td><td class="tengah" rowspan="3">{{ $smt }}</td>@endif
                        <td class="tinggi">{{ $i }}.&nbsp;</td><td></td><td></td>
                    </tr>
                @endfor
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Kepribadian --}}
<p class="subjudul">KEPRIBADIAN</p>
<table class="nilai">
    <thead>
        <tr><th style="width:60px;">TINGKAT</th><th style="width:70px;">SEMESTER</th><th>KELAKUAN</th><th>KERAPIAN</th><th>KERAJINAN</th></tr>
        <tr><th></th><th></th><th style="font-weight:normal; font-size:10px;">Amat Baik / Baik / Cukup / Kurang</th><th style="font-weight:normal; font-size:10px;">Amat Baik / Baik / Cukup / Kurang</th><th style="font-weight:normal; font-size:10px;">Amat Baik / Baik / Cukup / Kurang</th></tr>
    </thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            @foreach ([1, 2] as $smt)
                <tr>
                    @if ($smt === 1)<td class="tengah" rowspan="2">{{ $tingkat }}</td>@endif
                    <td class="tengah">{{ $smt }}</td><td class="tinggi"></td><td></td><td></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Akhlak Mulia --}}
<p class="subjudul">AKHLAK MULIA</p>
<table class="nilai">
    <thead>
        <tr><th style="width:60px;">TINGKAT</th><th style="width:70px;">SEMESTER</th><th>AKHLAK MULIA</th></tr>
        <tr><th></th><th></th><th style="font-weight:normal; font-size:10px;">Amat Baik / Baik / Cukup / Kurang</th></tr>
    </thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            @foreach ([1, 2] as $smt)
                <tr>
                    @if ($smt === 1)<td class="tengah" rowspan="2">{{ $tingkat }}</td>@endif
                    <td class="tengah">{{ $smt }}</td><td class="tinggi"></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Ketidakhadiran --}}
<p class="subjudul">KETIDAKHADIRAN</p>
<table class="nilai">
    <thead>
        <tr><th style="width:60px;">TINGKAT</th><th style="width:70px;">SEMESTER</th><th>SAKIT (hari)</th><th>IJIN (hari)</th><th>TANPA KETERANGAN (hari)</th></tr>
    </thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            @foreach ([1, 2] as $smt)
                <tr>
                    @if ($smt === 1)<td class="tengah" rowspan="2">{{ $tingkat }}</td>@endif
                    <td class="tengah">{{ $smt }}</td><td class="tinggi"></td><td></td><td></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Status Akhir Pelajaran --}}
<p class="subjudul">STATUS AKHIR PELAJARAN</p>
<table class="nilai">
    <thead><tr><th style="width:60px;">TINGKAT</th><th style="width:70px;">SEMESTER</th><th>STATUS AKHIR PELAJARAN</th><th>NAMA WALI KELAS</th><th>TANDA TANGAN</th></tr></thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            @foreach ([1, 2] as $smt)
                <tr>
                    @if ($smt === 1)<td class="tengah" rowspan="2">{{ $tingkat }}</td>@endif
                    <td class="tengah">{{ $smt }}</td><td class="tinggi">Naik ke &hellip;&hellip;&hellip;&nbsp;&nbsp; Tingkat &hellip;&hellip;&hellip;</td><td></td><td></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Catatan Akhir Semester --}}
<p class="judul-bagian">Catatan Akhir Semester</p>
<p class="subjudul">Kegiatan belajar di dunia usaha / industri dan instansi relevan</p>
<table class="nilai">
    <thead><tr><th style="width:60px;">TINGKAT</th><th>NAMA DU/DI INSTANSI RELEVAN</th><th>LOKASI</th><th>JENIS KEGIATAN</th><th>LAMA DAN WAKTU PELAKSANAAN</th></tr></thead>
    <tbody>
        <tr><td class="tengah">XI</td><td class="tinggi"></td><td></td><td></td><td></td></tr>
        <tr><td class="tengah"></td><td class="tinggi"></td><td></td><td></td><td></td></tr>
    </tbody>
</table>

<p class="subjudul">CATATAN UNTUK ORANG TUA / WALI</p>
<table class="nilai">
    <thead><tr><th style="width:60px;">TINGKAT</th><th>CATATAN</th></tr></thead>
    <tbody>
        @foreach (['X', 'XI', 'XII'] as $tingkat)
            <tr><td class="tengah">{{ $tingkat }}</td><td style="height:34px;"></td></tr>
        @endforeach
    </tbody>
</table>

{{-- Tanda tangan --}}
<p style="text-align:right; margin-top:28px;">Surakarta, {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
<table class="ttd">
    <tr><td style="width:33%;">Kepala Sekolah</td><td style="width:33%;">Wali Kelas</td><td>Orang Tua / Wali</td></tr>
    <tr><td class="ruang"></td><td class="ruang"></td><td class="ruang"></td></tr>
    <tr><td><strong>({{ $sekolah->kepala_sekolah ?? '........................' }})</strong></td><td>(........................)</td><td>(........................)</td></tr>
</table>
@endsection
