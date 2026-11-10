{{-- Header kolom yang bisa di-sort (klik untuk urut naik/turun).
    Param: $label (teks header), $key (nama kolom sort), $class (opsional).
    Dipakai di semua tabel daftar admin; controller memakai
    Controller::terapkanSort() dengan daftar kolom yang diizinkan. --}}
@php
    $isActive = request('sort') === $key;
    $dir = $isActive && request('dir') === 'desc' ? 'desc' : 'asc';
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]);
@endphp
<th class="{{ $class ?? 'px-4 py-3' }}">
    <a href="{{ $url }}" class="inline-flex items-center gap-1 hover:underline" title="Urutkan berdasarkan {{ $label }}">
        {{ $label }}
        @if ($isActive)
            <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
        @else
            <span class="text-slate-300">↕</span>
        @endif
    </a>
</th>
