@extends('layouts.print')
@section('title', 'Transkrip Nilai: '.$siswa->nama)

@section('content')
<div class="kop">
    <h1>{{ $sekolah->nama_sekolah }}</h1>
    <p>{{ $sekolah->alamat }}</p>
    <p>Telp: {{ $sekolah->telepon ?? '-' }} &middot; Email: {{ $sekolah->email ?? '-' }}</p>
</div>

<p style="text-align:center; font-weight:bold; font-size:13px; text-transform:uppercase; margin:10px 0 2px;">Transkrip Nilai</p>
<p style="text-align:center; margin:0 0 10px;">{{ strtoupper($siswa->nama) }} &middot; NIS {{ $siswa->nis }} &middot; NISN {{ $siswa->nisn ?? '-' }}<br>
{{ $siswa->jurusan?->nama }} / {{ $siswa->kelas?->nama ?? '-' }} &middot; {{ $siswa->tahunAjaran?->nama }}
@if ($transkrip) &middot; No. Transkrip {{ $transkrip->nomor_transkrip ?? '-' }} (terbit {{ $transkrip->tanggal_terbit?->locale('id')->translatedFormat('d F Y') ?? '-' }}) @endif</p>

@include('admin.siswa._laporan-nilai', ['siswa' => $siswa, 'laporan' => $laporan])

<p style="text-align:right; margin-top:28px;">Surakarta, {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
<table class="ttd">
    <tr><td style="width:50%;">Kepala Sekolah</td><td>Wali Kelas</td></tr>
    <tr><td class="ruang"></td><td class="ruang"></td></tr>
    <tr><td><strong>({{ $sekolah->kepala_sekolah ?? '........................' }})</strong></td><td>(........................)</td></tr>
</table>

<div class="no-print" style="margin-top:24px;">
    <a href="{{ route('admin.siswa.show', $siswa) }}" style="margin-left:0;">&larr; Kembali ke Detail Siswa</a>
    <a href="{{ route('admin.nilai.index', ['kelas_id' => $siswa->kelas_id, 'tahun_ajaran_id' => $siswa->tahun_ajaran_id]) }}" style="margin-left:16px;">Input Nilai</a>
</div>
@endsection
