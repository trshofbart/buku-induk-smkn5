@extends('layouts.app')
@section('title', 'Login Pegawai — SMK Negeri 5 Surakarta')

@section('content')
<div class="max-w-md mx-auto bg-white rounded-2xl shadow p-6 md:p-8 mt-6">
    <h1 class="text-2xl font-bold">Login Pegawai</h1>
    <p class="text-sm text-slate-500 mt-1">Halaman ini khusus pegawai SMK Negeri 5 Surakarta. Tidak ada pendaftaran publik — akun dibuat oleh Admin TU.</p>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm mt-4">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="mt-5 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full border rounded-lg px-3 py-2" placeholder="nama@smkn5.sch.id">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1" for="password">Password</label>
            <input id="password" type="password" name="password" required class="w-full border rounded-lg px-3 py-2" placeholder="Password">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="w-full bg-green-700 text-white font-semibold py-2.5 rounded-lg">Masuk</button>
    </form>

    <div class="bg-slate-100 rounded-lg p-3 mt-5 text-xs text-slate-500">
        <p class="font-semibold text-slate-700">Akun contoh (seeder — ganti setelah install):</p>
        <p>Admin TU: admin@smkn5.sch.id / password</p>
        <p>Kepala Sekolah: kepsek@smkn5.sch.id / password</p>
        <p>Guru: guru@smkn5.sch.id / password</p>
    </div>
</div>
@endsection
