@extends('layouts.admin')
@section('title', 'Pengguna (Akun Pegawai)')

@section('content')
<p class="text-sm text-slate-500 -mt-2 mb-4">Hanya Admin TU yang bisa menambah akun. Tidak ada pendaftaran publik.</p>
<form method="POST" action="{{ route('admin.pengguna.store') }}" class="bg-white rounded-xl shadow p-4 grid md:grid-cols-6 gap-2 mb-4">
    @csrf
    <input name="name" required placeholder="Nama pegawai" class="border rounded-lg px-3 py-2">
    <input type="email" name="email" required placeholder="Email" class="border rounded-lg px-3 py-2">
    <input type="password" name="password" required minlength="8" placeholder="Password (min 8)" class="border rounded-lg px-3 py-2">
    <select name="role" class="border rounded-lg px-3 py-2"><option value="guru">Guru</option><option value="kepala_sekolah">Kepala Sekolah</option><option value="admin_tu">Admin TU</option></select>
    <input name="nip" placeholder="NIP (opsional)" class="border rounded-lg px-3 py-2">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">+ Buat Akun</button>
</form>

<form method="GET" class="bg-white rounded-xl shadow p-4 flex gap-2 mb-4">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama / email pegawai..." class="border rounded-lg px-3 py-2 flex-1">
    <button class="bg-green-700 text-white px-4 py-2 rounded-lg">Cari</button>
    @if (! empty($q))<a href="{{ route('admin.pengguna.index') }}" class="border border-slate-300 px-4 py-2 rounded-lg">Reset</a>@endif
</form>
<div class="flex justify-end mb-2">
    @include('admin._per-page')
</div>
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-slate-100 text-left">@include('admin._sort-th', ['label' => 'Nama', 'key' => 'name'])@include('admin._sort-th', ['label' => 'Email', 'key' => 'email'])@include('admin._sort-th', ['label' => 'Role', 'key' => 'role'])@include('admin._sort-th', ['label' => 'Status', 'key' => 'is_active'])<th class="px-4 py-3">Aksi</th></tr></thead>
        <tbody>
        @foreach ($pengguna as $u)
            <tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $u->name }}</td><td class="px-4 py-3">{{ $u->email }}</td><td class="px-4 py-3">{{ $u->roleLabel() }}</td><td class="px-4 py-3">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-4 py-3 whitespace-nowrap"><form method="POST" action="{{ route('admin.pengguna.toggle', $u) }}" class="inline">@csrf<button class="text-blue-700">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form> | <form method="POST" action="{{ route('admin.pengguna.destroy', $u) }}" class="inline" onsubmit="return confirm('Hapus akun ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form>
            <form method="POST" action="{{ route('admin.pengguna.password', $u) }}" class="mt-2 flex gap-1">
                @csrf
                <input type="password" name="password" required minlength="8" placeholder="Password baru (min 8)" class="border rounded px-2 py-1 w-36">
                <input type="password" name="password_confirmation" required minlength="8" placeholder="Ulangi password" class="border rounded px-2 py-1 w-36">
                <button class="bg-green-700 text-white px-3 py-1 rounded">Ganti Password</button>
            </form></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $pengguna->links() }}</div>
@endsection
