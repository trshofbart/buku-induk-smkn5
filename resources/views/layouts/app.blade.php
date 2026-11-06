<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Website SMK Negeri 5 Surakarta — Profil, Berita, dan layanan Buku Induk siswa.">
    <meta name="theme-color" content="#166534">
    <title>@yield('title', 'SMK Negeri 5 Surakarta')</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/icon-192.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">
    <header class="bg-green-800 text-white sticky top-0 z-40 shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="bg-white text-green-800 font-bold rounded-lg w-10 h-10 flex items-center justify-center text-lg">5</span>
                <span>
                    <span class="block font-bold leading-tight">SMK Negeri 5 Surakarta</span>
                    <span class="block text-xs text-green-200">Buku Induk &amp; Data Siswa</span>
                </span>
            </a>
            <nav class="hidden md:flex items-center gap-5 text-sm">
                <a href="{{ route('home') }}" class="hover:text-green-200">Beranda</a>
                <a href="{{ route('profil.index') }}" class="hover:text-green-200">Profil</a>
                <a href="{{ route('berita.index') }}" class="hover:text-green-200">Berita</a>
                <a href="{{ route('pengumuman.index') }}" class="hover:text-green-200">Pengumuman</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="bg-white text-green-800 font-semibold px-3 py-1.5 rounded-lg">Admin</a>
                @else
                    <a href="{{ route('login') }}" class="bg-white text-green-800 font-semibold px-3 py-1.5 rounded-lg">Login Pegawai</a>
                @endauth
            </nav>
            <a href="{{ route('login') }}" class="md:hidden bg-white text-green-800 text-sm font-semibold px-3 py-1.5 rounded-lg">Login</a>
        </div>
        <nav class="md:hidden border-t border-green-700 px-4 py-2 flex gap-4 text-sm overflow-x-auto">
            <a href="{{ route('home') }}" class="whitespace-nowrap">Beranda</a>
            <a href="{{ route('profil.index') }}" class="whitespace-nowrap">Profil</a>
            <a href="{{ route('berita.index') }}" class="whitespace-nowrap">Berita</a>
            <a href="{{ route('pengumuman.index') }}" class="whitespace-nowrap">Pengumuman</a>
        </nav>
    </header>

    @if (session('status'))
        <div class="max-w-6xl mx-auto w-full px-4 mt-4">
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
        </div>
    @endif

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    <footer class="bg-slate-900 text-slate-300 text-sm">
        <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col md:flex-row justify-between gap-3">
            <div>
                <p class="font-semibold text-white">SMK Negeri 5 Surakarta</p>
                <p>Jl. Adi Sucipto No. 33, Surakarta, Jawa Tengah</p>
            </div>
            <div class="text-slate-400">
                <p>Halaman Buku Induk &amp; Data Siswa hanya untuk pegawai setelah login.</p>
                <p>&copy; {{ date('Y') }} SMKN 5 Surakarta</p>
            </div>
        </div>
    </footer>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        }
    </script>
</body>
</html>
