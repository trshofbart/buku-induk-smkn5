<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#166534">
    <title>@yield('title', 'Admin') — Buku Induk SMKN 5</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/icon-192.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
    {{-- Sidebar desktop --}}
    <aside class="hidden md:flex flex-col w-64 bg-green-900 text-white shrink-0">
        <a href="{{ route('admin.dashboard') }}" class="px-5 py-4 border-b border-green-800">
            <span class="block font-bold">Buku Induk SMKN 5</span>
            <span class="block text-xs text-green-300">Panel Pegawai — {{ auth()->user()->roleLabel() }}</span>
        </a>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Dashboard</a>
            <p class="px-3 pt-3 pb-1 text-xs uppercase text-green-400">Buku Induk &amp; Siswa</p>
            <a href="{{ route('admin.siswa.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Data Siswa</a>
            <a href="{{ route('admin.buku-induk.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Buku Induk</a>
            <a href="{{ route('admin.buku-induk.nominatif') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Data Nominatif</a>
            <a href="{{ route('admin.nilai.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Input Nilai</a>
            <p class="px-3 pt-3 pb-1 text-xs uppercase text-green-400">Konten Publik</p>
            <a href="{{ route('admin.berita.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Berita</a>
            <a href="{{ route('admin.profil.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Profil Sekolah</a>
            <a href="{{ route('admin.pengumuman.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Pengumuman</a>
            @if (auth()->user()->isAdminTu())
                <p class="px-3 pt-3 pb-1 text-xs uppercase text-green-400">Master &amp; Pengguna</p>
                <a href="{{ route('admin.tahun-ajaran.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Tahun Ajaran</a>
                <a href="{{ route('admin.jurusan.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Jurusan</a>
                <a href="{{ route('admin.kelas.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Kelas</a>
                <a href="{{ route('admin.mapel.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Mata Pelajaran</a>
                <a href="{{ route('admin.data-sekolah.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Data Sekolah</a>
                <a href="{{ route('admin.pengguna.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Pengguna</a>
                <a href="{{ route('admin.log.index') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800">Log Aktivitas</a>
            @endif
        </nav>
        <div class="px-5 py-4 border-t border-green-800 text-sm">
            <p class="font-semibold">{{ auth()->user()->name }}</p>
            <p class="text-green-300 text-xs">{{ auth()->user()->email }}</p>
            <div class="flex gap-3 mt-2">
                <a href="{{ route('home') }}" class="text-green-200 underline">Lihat Website</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-green-200 underline">Logout</button></form>
            </div>
        </div>
    </aside>

    {{-- Konten --}}
    <div class="flex-1 min-w-0 flex flex-col pb-20 md:pb-0">
        <header class="md:hidden bg-green-800 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-40">
            <a href="{{ route('admin.dashboard') }}" class="font-bold">Buku Induk SMKN 5</a>
            <span class="text-xs bg-green-700 px-2 py-1 rounded">{{ auth()->user()->roleLabel() }}</span>
        </header>
        <main class="p-4 md:p-6 w-full max-w-6xl mx-auto">
            <h1 class="text-xl md:text-2xl font-bold mb-4">@yield('title', 'Admin')</h1>
            @if (session('status'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm mb-4">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @if (session('import_gagal'))
                <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg text-sm mb-4">
                    <p class="font-semibold">Baris yang gagal di-import:</p>
                    <ul class="list-disc pl-5">@foreach (session('import_gagal') as $g)<li>{{ $g }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

{{-- Navigasi bawah khusus HP --}}
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow z-40 flex text-xs">
    <a href="{{ route('admin.dashboard') }}" class="flex-1 text-center py-3">Dashboard</a>
    <a href="{{ route('admin.siswa.index') }}" class="flex-1 text-center py-3">Siswa</a>
    <a href="{{ route('admin.buku-induk.index') }}" class="flex-1 text-center py-3">Buku Induk</a>
    <a href="{{ route('admin.berita.index') }}" class="flex-1 text-center py-3">Berita</a>
    <form method="POST" action="{{ route('logout') }}" class="flex-1 text-center py-3">@csrf<button>Logout</button></form>
</nav>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }
</script>
</body>
</html>
