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
    {{-- Sidebar desktop — fixed: tetap terlihat saat konten di-scroll --}}
    <aside class="hidden md:flex flex-col w-64 bg-green-900 text-white shrink-0 sticky top-0 h-screen">
        <a href="{{ route('admin.dashboard') }}" class="px-5 py-4 border-b border-green-800">
            <span class="block font-bold">Buku Induk SMKN 5</span>
            <span class="block text-xs text-green-300">Panel Pegawai — {{ auth()->user()->roleLabel() }}</span>
        </a>
        @php
            // Struktur menu sidebar: tiap parent bisa dilipat/dibuka (sub menu).
            // 'patterns' dipakai untuk membuka parent saat salah satu anaknya aktif.
            $sidebarMenu = [
                [
                    'label' => 'Buku Induk & Siswa',
                    'patterns' => ['admin.siswa.*', 'admin.buku-induk.*', 'admin.nilai.*', 'admin.kelengkapan.*'],
                    'items' => [
                        ['label' => 'Data Siswa', 'route' => 'admin.siswa.index', 'pattern' => 'admin.siswa.*'],
                        ['label' => 'Buku Induk', 'route' => 'admin.buku-induk.index', 'pattern' => 'admin.buku-induk.*'],
                        ['label' => 'Data Nominatif', 'route' => 'admin.buku-induk.nominatif', 'pattern' => 'admin.buku-induk.nominatif'],
                        ['label' => 'Input Nilai', 'route' => 'admin.nilai.index', 'pattern' => 'admin.nilai.*'],
                        ['label' => 'Kelengkapan', 'route' => 'admin.kelengkapan.index', 'pattern' => 'admin.kelengkapan.*'],
                    ],
                ],
                [
                    'label' => 'Konten Publik',
                    'patterns' => ['admin.berita.*', 'admin.profil.*', 'admin.pengumuman.*'],
                    'items' => [
                        ['label' => 'Berita', 'route' => 'admin.berita.index', 'pattern' => 'admin.berita.*'],
                        ['label' => 'Profil Sekolah', 'route' => 'admin.profil.index', 'pattern' => 'admin.profil.*'],
                        ['label' => 'Pengumuman', 'route' => 'admin.pengumuman.index', 'pattern' => 'admin.pengumuman.*'],
                    ],
                ],
            ];
            if (auth()->user()->isAdminTu()) {
                $sidebarMenu[] = [
                    'label' => 'Master & Pengguna',
                    'patterns' => ['admin.tahun-ajaran.*', 'admin.jurusan.*', 'admin.kelas.*', 'admin.mapel.*', 'admin.data-sekolah.*', 'admin.pengguna.*', 'admin.log.*'],
                    'items' => [
                        ['label' => 'Tahun Ajaran', 'route' => 'admin.tahun-ajaran.index', 'pattern' => 'admin.tahun-ajaran.*'],
                        ['label' => 'Jurusan', 'route' => 'admin.jurusan.index', 'pattern' => 'admin.jurusan.*'],
                        ['label' => 'Kelas', 'route' => 'admin.kelas.index', 'pattern' => 'admin.kelas.*'],
                        ['label' => 'Mata Pelajaran', 'route' => 'admin.mapel.index', 'pattern' => 'admin.mapel.*'],
                        ['label' => 'Mapel per Jurusan', 'route' => 'admin.mapel.perjurusan', 'pattern' => 'admin.mapel.perjurusan'],
                        ['label' => 'Data Sekolah', 'route' => 'admin.data-sekolah.edit', 'pattern' => 'admin.data-sekolah.*'],
                        ['label' => 'Pengguna', 'route' => 'admin.pengguna.index', 'pattern' => 'admin.pengguna.*'],
                        ['label' => 'Log Aktivitas', 'route' => 'admin.log.index', 'pattern' => 'admin.log.*'],
                    ],
                ];
            }
        @endphp
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-green-800 {{ request()->routeIs('admin.dashboard') ? 'bg-green-800' : '' }}">Dashboard</a>
            @foreach ($sidebarMenu as $i => $section)
                @php $terbuka = request()->routeIs($section['patterns']); @endphp
                <div>
                    <button type="button" data-submenu-toggle aria-controls="submenu-{{ $i }}" aria-expanded="{{ $terbuka ? 'true' : 'false' }}" class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-xs uppercase text-green-400 hover:text-green-200">
                        <span>{{ $section['label'] }}</span>
                        <svg class="chevron w-3.5 h-3.5 transition-transform {{ $terbuka ? 'rotate-180' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </button>
                    <div id="submenu-{{ $i }}" class="{{ $terbuka ? '' : 'hidden' }} space-y-1">
                        @foreach ($section['items'] as $item)
                            <a href="{{ route($item['route']) }}" class="block px-3 py-2 rounded-lg hover:bg-green-800 {{ request()->routeIs($item['pattern']) ? 'bg-green-800 font-semibold' : '' }}">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
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
    // Sub menu sidebar: klik parent untuk melipat/membuka anak-anaknya.
    document.querySelectorAll('[data-submenu-toggle]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var panel = document.getElementById(tombol.getAttribute('aria-controls'));
            var tertutup = panel.classList.toggle('hidden');
            tombol.setAttribute('aria-expanded', String(!tertutup));
            tombol.querySelector('.chevron').classList.toggle('rotate-180', !tertutup);
        });
    });
</script>
</body>
</html>
