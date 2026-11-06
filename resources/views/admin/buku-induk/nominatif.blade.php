@extends('layouts.print')
@section('title', 'Data Nominatif'.($kelas ? ': '.$kelas->nama : ''))

@section('content')
<div class="kop">
    <h1>{{ $sekolah->nama_sekolah }}</h1>
    <p>{{ $sekolah->alamat }}</p>
</div>
<h2 style="text-align:center;">DATA NOMINATIF SISWA @if($kelas) — {{ $kelas->nama }} ({{ $kelas->tahunAjaran?->nama }}) @endif</h2>

<div class="no-print" style="margin-bottom:12px;">
    <form method="GET" action="{{ route('admin.buku-induk.nominatif') }}" style="display:flex; gap:8px;">
        <select name="kelas_id" style="flex:1; padding:8px; border:1px solid #999; border-radius:6px;">
            <option value="">— Pilih Kelas —</option>
            @foreach ($daftarKelas as $k)<option value="{{ $k->id }}" @selected($kelas?->id == $k->id)>{{ $k->nama }} — {{ $k->tahunAjaran?->nama }}</option>@endforeach
        </select>
        <button style="padding:8px 16px; background:#166534; color:#fff; border:none; border-radius:6px;">Tampilkan</button>
    </form>
</div>

@if ($kelas)
    <table>
        <tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama</th><th>JK</th><th>Tempat/Tgl Lahir</th><th>Status</th></tr>
        @forelse ($siswa as $i => $s)
            <tr><td>{{ $i + 1 }}</td><td>{{ $s->nis }}</td><td>{{ $s->nisn ?? '-' }}</td><td>{{ $s->nama }}</td><td>{{ $s->jenis_kelamin }}</td><td>{{ $s->tempat_lahir }}, {{ $s->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td><td>{{ $s->statusLabel() }}</td></tr>
        @empty
            <tr><td colspan="7">Belum ada siswa di kelas ini.</td></tr>
        @endforelse
    </table>
    <p>Jumlah siswa: {{ $siswa->count() }} orang.</p>
@else
    <p>Silakan pilih kelas terlebih dahulu.</p>
@endif
@endsection
