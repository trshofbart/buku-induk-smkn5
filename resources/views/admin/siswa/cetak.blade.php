@extends('layouts.print')
@section('title', 'Cetak Data Siswa: '.$siswa->nama)

@section('content')
<div class="kop">
    <h1>{{ $sekolah->nama_sekolah }}</h1>
    <p>{{ $sekolah->alamat }}</p>
    <p>Telp: {{ $sekolah->telepon ?? '-' }} &middot; Email: {{ $sekolah->email ?? '-' }}</p>
</div>
<h2 style="text-align:center;">DATA SISWA — BUKU INDUK</h2>
<table>
    <tr><th style="width:30%;">NIS / NISN</th><td>{{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</td></tr>
    <tr><th>Nama</th><td>{{ $siswa->nama }}</td></tr>
    <tr><th>Jenis Kelamin</th><td>{{ $siswa->jenisKelaminLabel() }}</td></tr>
    <tr><th>Tempat, Tanggal Lahir</th><td>{{ $siswa->tempat_lahir }}, {{ $siswa->tanggal_lahir?->format('d F Y') ?? '-' }}</td></tr>
    <tr><th>Agama</th><td>{{ $siswa->agama ?? '-' }}</td></tr>
    <tr><th>Alamat</th><td>{{ $siswa->alamat ?? '-' }}</td></tr>
    <tr><th>Jurusan / Kelas</th><td>{{ $siswa->jurusan?->nama }} / {{ $siswa->kelas?->nama ?? '-' }}</td></tr>
    <tr><th>Tahun Ajaran</th><td>{{ $siswa->tahunAjaran?->nama }}</td></tr>
    <tr><th>Status</th><td>{{ $siswa->statusLabel() }}</td></tr>
    <tr><th>Ayah</th><td>{{ $siswa->orangTua?->nama_ayah ?? '-' }} ({{ $siswa->orangTua?->pekerjaan_ayah ?? '-' }})</td></tr>
    <tr><th>Ibu</th><td>{{ $siswa->orangTua?->nama_ibu ?? '-' }} ({{ $siswa->orangTua?->pekerjaan_ibu ?? '-' }})</td></tr>
</table>

<h3>Ledger</h3>
<table>
    <tr><th>Semester</th><th>Rata-rata</th><th>Peringkat</th></tr>
    @forelse ($siswa->ledger as $l)
        <tr><td>{{ $l->semester }}</td><td>{{ $l->rata_rata }}</td><td>{{ $l->peringkat ?? '-' }}</td></tr>
    @empty
        <tr><td colspan="3">Belum ada data ledger.</td></tr>
    @endforelse
</table>

<p style="margin-top:32px; text-align:right;">Surakarta, {{ now()->format('d F Y') }}<br><br><br>({{ $sekolah->kepala_sekolah ?? 'Kepala Sekolah' }})</p>
@endsection
