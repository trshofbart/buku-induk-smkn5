@extends('layouts.admin')
@section('title', 'Import Siswa dari Excel')

@section('content')
<div class="bg-white rounded-xl shadow p-5 max-w-2xl">
    <p class="text-sm text-slate-600">Import massal dari file Excel lama (MASTER .xlsm): isi template di bawah, satu file per jurusan/kelas. Baris yang gagal akan dilaporkan dan tidak menggagalkan baris lain. NIS yang sudah ada akan ditolak.</p>
    <p class="text-sm mt-2">Contoh format: <a href="{{ route('admin.siswa.import.template') }}" class="text-green-700 font-semibold">unduh template-import-siswa.xlsx</a></p>

    <form method="POST" action="{{ route('admin.siswa.import.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
        @csrf
        <div><label class="text-sm font-semibold">Tahun Ajaran *</label><select name="tahun_ajaran_id" required class="w-full border rounded-lg px-3 py-2">@foreach ($tahunAjaran as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach</select></div>
        <div><label class="text-sm font-semibold">Jurusan *</label><select name="jurusan_id" required class="w-full border rounded-lg px-3 py-2">@foreach ($jurusan as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach</select></div>
        <div><label class="text-sm font-semibold">File Excel / CSV * (maks 5 MB)</label><input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="w-full border rounded-lg px-3 py-2"></div>
        <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Import</button>
        <a href="{{ route('admin.siswa.index') }}" class="border px-4 py-2 rounded-lg">Batal</a>
    </form>

    <div class="bg-slate-100 rounded-lg p-3 mt-5 text-xs text-slate-600">
        <p class="font-semibold">Header yang dikenali:</p>
        <p class="break-all">nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, no_hp, tahun_ajaran, jurusan, kelas, status, nama_ayah, nama_ibu, no_hp_ortu</p>
    </div>
</div>
@endsection
