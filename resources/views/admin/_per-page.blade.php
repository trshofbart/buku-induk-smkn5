{{-- Pilihan jumlah data per halaman (15 / 25 / 50 / 100).
    Param: $default (opsional, jumlah default tabel ini).
    Form berdiri sendiri (di luar form filter) dan mempertahankan
    query string yang sedang aktif (sort, filter, cari) lewat input
    tersembunyi, supaya ganti jumlah tidak menghilangkan filter. --}}
@php
    $default = $default ?? 15;
    $opsi = [15, 25, 50, 100];
    $saatIni = (int) request('per_page', $default);
    if (! in_array($saatIni, $opsi, true)) { $saatIni = $default; }
@endphp
<form method="GET" class="inline-flex items-center gap-2 text-sm">
    @foreach (request()->except(['per_page', 'page']) as $k => $v)
        @if (is_scalar($v))
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach
    <label for="per_page" class="text-slate-600">Tampilkan</label>
    <select name="per_page" id="per_page" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5">
        @foreach ($opsi as $o)
            <option value="{{ $o }}" @selected($saatIni == $o)>{{ $o }}</option>
        @endforeach
    </select>
    <span class="text-slate-600">data</span>
</form>
