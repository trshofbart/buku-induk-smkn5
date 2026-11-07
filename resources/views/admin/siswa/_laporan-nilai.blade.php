{{--
    Laporan Penilaian Hasil Belajar — dipakai halaman Cetak & Transkrip Nilai.
    Mengikuti LAPORAN PENILAIAN file MASTER (sheet HAL_PRINT, baris 51-200).
    Variabel: $siswa, $laporan (dari Siswa::laporanNilai()).
    Bila jurusan belum punya daftar mapel, tampilkan baris kosong seperti MASTER.
--}}
@php
    $kelompokKosong = [
        ['judul' => 'A. Muatan Nasional', 'baris' => 6],
        ['judul' => 'B. Muatan Kewilayahan', 'baris' => 3],
        ['judul' => 'C. Muatan Peminatan Kejuruan', 'baris' => 0],
        ['judul' => 'C1. Dasar Bidang Keahlian', 'baris' => 3],
        ['judul' => 'C2. Dasar Program Keahlian', 'baris' => 3],
        ['judul' => 'C3. Kompetensi Keahlian', 'baris' => 5],
    ];
@endphp

<p class="judul-bagian">Laporan Penilaian Hasil Belajar Peserta Didik</p>
<table class="identitas">
    <tr><td class="lbl">NAMA SISWA</td><td class="titik">:</td><td>{{ strtoupper($siswa->nama) }}</td></tr>
    <tr><td class="lbl">NOMOR INDUK SISWA</td><td class="titik">:</td><td>{{ $siswa->nis }}</td></tr>
    <tr><td class="lbl">NISN</td><td class="titik">:</td><td>{{ $siswa->nisn ?? '' }}</td></tr>
    <tr><td class="lbl">BIDANG STUDI KEAHLIAN</td><td class="titik">:</td><td></td></tr>
    <tr><td class="lbl">PROGRAM STUDI KEAHLIAN</td><td class="titik">:</td><td></td></tr>
    <tr><td class="lbl">KOMPETENSI KEAHLIAN</td><td class="titik">:</td><td>{{ strtoupper($siswa->jurusan?->nama ?? '') }}</td></tr>
</table>

@foreach (['X', 'XI', 'XII'] as $i => $tingkat)
    @php
        $d = $laporan[$tingkat];
        $smtNo = ['X' => [1, 2], 'XI' => [3, 4], 'XII' => [5, 6]][$tingkat];
    @endphp
    {{-- Tiap tahun ajaran di halaman cetak sendiri. --}}
    @if ($i > 0)<div class="halaman-baru"></div>@endif
    <p class="subjudul">TAHUN DIKLAT : {{ $d['tahun'] ?? '&ndash;' }} &nbsp;&nbsp; TINGKAT : {{ $tingkat }}</p>
    <table class="nilai">
        <thead>
            <tr>
                <th rowspan="2" style="width:26px;">NO</th>
                <th rowspan="2">MATA PELAJARAN</th>
                <th colspan="3">SEMESTER : {{ $smtNo[0] }}</th>
                <th colspan="3">SEMESTER : {{ $smtNo[1] }}</th>
            </tr>
            <tr>
                <th>KKM *)</th><th>NILAI ANGKA</th><th>HURUF</th>
                <th>KKM *)</th><th>NILAI ANGKA</th><th>HURUF</th>
            </tr>
        </thead>
        <tbody>
            @if (! empty($d['kelompok']))
                @foreach ($d['kelompok'] as $kode => $baris)
                    <tr class="grup"><td colspan="8">{{ \App\Models\Mapel::KELOMPOK[$kode] ?? $kode }}</td></tr>
                    @foreach ($baris as $i => $r)
                        <tr>
                            <td class="no">{{ $i + 1 }}</td>
                            <td>{{ $r['mapel']->nama }}</td>
                            <td class="angka">{{ rtrim(rtrim($r['smt1']?->kkm ?? $r['mapel']->kkm_default, '0'), '.') }}</td>
                            <td class="angka">{{ $r['smt1'] ? rtrim(rtrim($r['smt1']->nilai_angka, '0'), '.') : '&ndash;' }}</td>
                            <td class="angka">{{ $r['smt1']?->nilai_huruf ?? '&ndash;' }}</td>
                            <td class="angka">{{ rtrim(rtrim($r['smt2']?->kkm ?? $r['mapel']->kkm_default, '0'), '.') }}</td>
                            <td class="angka">{{ $r['smt2'] ? rtrim(rtrim($r['smt2']->nilai_angka, '0'), '.') : '&ndash;' }}</td>
                            <td class="angka">{{ $r['smt2']?->nilai_huruf ?? '&ndash;' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @else
                @foreach ($kelompokKosong as $kel)
                    <tr class="grup"><td colspan="8">{{ $kel['judul'] }}</td></tr>
                    @for ($i = 1; $i <= $kel['baris']; $i++)
                        <tr><td class="no">{{ $i }}</td><td class="tinggi"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td><td class="angka"></td></tr>
                    @endfor
                @endforeach
            @endif
            <tr>
                <td colspan="2" style="text-align:right; font-weight:bold;">RATA-RATA</td>
                <td class="angka"></td><td class="angka"><strong>{{ $d['smt1']?->rata_rata ?? '' }}</strong></td><td class="angka"></td>
                <td class="angka"></td><td class="angka"><strong>{{ $d['smt2']?->rata_rata ?? '' }}</strong></td><td class="angka"></td>
            </tr>
        </tbody>
    </table>
@endforeach
<p class="catatan">*) KKM = Kriteria Ketuntasan Minimal. Predikat huruf mengikuti skala sekolah: A &ge; 92, B 84&ndash;91, C 76&ndash;83, D &lt; 76.</p>
