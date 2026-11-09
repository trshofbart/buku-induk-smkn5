<?php

namespace Database\Seeders;

use App\Models\Mapel;
use Illuminate\Database\Seeder;

class MapelSeeder extends Seeder
{
    /**
     * Daftar mata pelajaran dari sheet MAPEL file MASTER 2021-2022
     * (MASTER-TEI.xlsm & MASTER [RPL A].xlsm — daftarnya sama).
     * Label kelompok mengikuti HAL_PRINT ("A. Muatan Nasional").
     * Dua mapel khusus tingkat mengikuti tabel HAL_PRINT tahun ke-3:
     * Mikroprosesor hanya X-XI, Pembuatan Peralatan hanya XII.
     *
     * Koreksi dari file Leger per kelas (2021-2022): mapel C2/C3 yang
     * hanya muncul di file TEI (Kerja Bengkel, T. Pemograman, Penerapan
     * Rangkaian, Sistem Pengendali, Mikroprosesor, Pengendali Robotik,
     * Pembuatan Peralatan) adalah khusus TEI, bukan umum; 'Dasar
     * Listrik' tetap umum (TEI, TITL, TOI) dan 'Produk Kreatif' tetap
     * umum (semua jurusan). Tiga mapel agama non-Islam adalah umum
     * kelompok A (siswa mengambil salah satu sesuai agamanya).
     */
    public function run(): void
    {
        $tei = \App\Models\Jurusan::where('kode', 'TEI')->value('id');

        $daftar = [
            // [nama, kelompok, urutan, tingkat(null = semua), kode jurusan(null = umum)]
            ['Pendidikan Agama Islam dan Budi Pekerti', 'A', 1, null, null],
            ['Pendidikan Agama Kristen dan Budi Pekerti', 'A', 1, null, null],
            ['Pendidikan Agama Katholik dan Budi Pekerti', 'A', 1, null, null],
            ['Pendidikan Agama Buddha dan Budi Pekerti', 'A', 1, null, null],
            ['Pendidikan Pancasila dan Kewarganegaraan', 'A', 2, null, null],
            ['Bahasa Indonesia', 'A', 3, null, null],
            ['Matematika (Umum)', 'A', 4, null, null],
            ['Sejarah Indonesia', 'A', 5, null, null],
            ['Bahasa Inggris', 'A', 6, null, null],
            ['Seni Budaya', 'B', 7, null, null],
            ['Pendidikan Jasmani, Olahraga dan Kesehatan', 'B', 8, null, null],
            ['Bahasa Jawa', 'B', 9, null, null],
            ['Simulasi dan Komunikasi Digital', 'C1', 10, null, null],
            ['Fisika', 'C1', 11, null, null],
            ['Kimia', 'C1', 12, null, null],
            ['Kerja Bengkel dan Gambar Teknik', 'C2', 13, null, 'TEI'],
            ['Dasar Listrik dan Elektronika', 'C2', 14, null, null],
            ['T. Pemograman. M. Prosesor dan M. Kontroler', 'C2', 15, null, 'TEI'],
            ['Produk Kreatif dan Kewirausahaan', 'C3', 16, null, null],
            ['Penerapan Rangkaian Elektronika', 'C3', 17, null, 'TEI'],
            ['Sistem Pengendali Elektronik', 'C3', 18, null, 'TEI'],
            ['Mikroprosesor dan Mikrokontroler', 'C3', 19, 'X', 'TEI'],
            ['Mikroprosesor dan Mikrokontroler', 'C3', 19, 'XI', 'TEI'],
            ['Pengendali Sistem Robotik', 'C3', 20, null, 'TEI'],
            ['Pembuatan, Perbaik dan Pemel Peralatan Elektronika', 'C3', 21, 'XII', 'TEI'],
        ];

        foreach ($daftar as [$nama, $kelompok, $urutan, $tingkat, $kodeJurusan]) {
            $jurusanId = $kodeJurusan === 'TEI' ? $tei : null;
            Mapel::updateOrCreate(
                ['nama' => $nama, 'jurusan_id' => $jurusanId, 'tingkat' => $tingkat],
                ['kelompok' => $kelompok, 'urutan' => $urutan, 'kkm_default' => 76]
            );
        }
    }
}
