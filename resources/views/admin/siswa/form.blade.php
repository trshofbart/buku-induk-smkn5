@extends('layouts.admin')
@section('title', $mode === 'create' ? 'Tambah Siswa' : 'Edit Siswa: '.$siswa->nama)

@section('content')
<form method="POST" action="{{ $mode === 'create' ? route('admin.siswa.store') : route('admin.siswa.update', $siswa) }}" class="bg-white rounded-xl shadow p-5 space-y-5 max-w-3xl">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <div>
        <h2 class="font-bold">Biodata Siswa</h2>
        <div class="grid md:grid-cols-2 gap-3 mt-3">
            <div><label class="text-sm font-semibold">NIS *</label><input name="nis" value="{{ old('nis', $siswa->nis) }}" required class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">NISN</label><input name="nisn" value="{{ old('nisn', $siswa->nisn) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div class="md:col-span-2"><label class="text-sm font-semibold">Nama Lengkap *</label><input name="nama" value="{{ old('nama', $siswa->nama) }}" required class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Jenis Kelamin *</label><select name="jenis_kelamin" class="w-full border rounded-lg px-3 py-2"><option value="L" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'L')>Laki-laki</option><option value="P" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'P')>Perempuan</option></select></div>
            <div><label class="text-sm font-semibold">Agama</label><input name="agama" value="{{ old('agama', $siswa->agama) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Tempat Lahir</label><input name="tempat_lahir" value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $siswa->tanggal_lahir?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div class="md:col-span-2"><label class="text-sm font-semibold">Alamat</label><textarea name="alamat" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('alamat', $siswa->alamat) }}</textarea></div>
            <div><label class="text-sm font-semibold">No HP Siswa</label><input name="no_hp" value="{{ old('no_hp', $siswa->no_hp) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Tanggal Masuk</label><input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', $siswa->tanggal_masuk?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
        </div>
    </div>

    <div>
        <h2 class="font-bold">Kelas &amp; Status</h2>
        <div class="grid md:grid-cols-2 gap-3 mt-3">
            <div><label class="text-sm font-semibold">Tahun Ajaran *</label><select name="tahun_ajaran_id" class="w-full border rounded-lg px-3 py-2">@foreach ($tahunAjaran as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', $siswa->tahun_ajaran_id) == $t->id)>{{ $t->nama }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Jurusan *</label><select name="jurusan_id" class="w-full border rounded-lg px-3 py-2">@foreach ($jurusan as $j)<option value="{{ $j->id }}" @selected(old('jurusan_id', $siswa->jurusan_id) == $j->id)>{{ $j->nama }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Kelas</label><select name="kelas_id" class="w-full border rounded-lg px-3 py-2"><option value="">— Belum ada kelas —</option>@foreach ($kelas as $k)<option value="{{ $k->id }}" @selected(old('kelas_id', $siswa->kelas_id) == $k->id)>{{ $k->nama }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Status *</label><select name="status" class="w-full border rounded-lg px-3 py-2">@foreach (\App\Models\Siswa::statusList() as $k => $v)<option value="{{ $k }}" @selected(old('status', $siswa->status) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="text-sm font-semibold">Tanggal Keluar (bila pindah/keluar/lulus)</label><input type="date" name="tanggal_keluar" value="{{ old('tanggal_keluar', $siswa->tanggal_keluar?->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Keterangan</label><input name="keterangan" value="{{ old('keterangan', $siswa->keterangan) }}" class="w-full border rounded-lg px-3 py-2"></div>
        </div>
    </div>

    <div>
        <h2 class="font-bold">Orang Tua / Wali</h2>
        <div class="grid md:grid-cols-2 gap-3 mt-3">
            <div><label class="text-sm font-semibold">Nama Ayah</label><input name="nama_ayah" value="{{ old('nama_ayah', $siswa->orangTua?->nama_ayah) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Pekerjaan Ayah</label><input name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $siswa->orangTua?->pekerjaan_ayah) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Nama Ibu</label><input name="nama_ibu" value="{{ old('nama_ibu', $siswa->orangTua?->nama_ibu) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Pekerjaan Ibu</label><input name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $siswa->orangTua?->pekerjaan_ibu) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">Nama Wali</label><input name="nama_wali" value="{{ old('nama_wali', $siswa->orangTua?->nama_wali) }}" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="text-sm font-semibold">No HP Orang Tua</label><input name="no_hp_ortu" value="{{ old('no_hp_ortu', $siswa->orangTua?->no_hp_ortu) }}" class="w-full border rounded-lg px-3 py-2"></div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Simpan</button>
        <a href="{{ route('admin.siswa.index') }}" class="border px-4 py-2 rounded-lg">Batal</a>
    </div>
</form>
@endsection
