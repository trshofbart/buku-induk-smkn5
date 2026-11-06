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
     */
    public function run(): void
    {
        $daftar = [
            // [nama, kelompok, urutan, tingkat(null = semua)]
            ['Pendidikan Agama Islam dan Budi Pekerti', 'A', 1, null],
            ['Pendidikan Pancasila dan Kewarganegaraan', 'A', 2, null],
            ['Bahasa Indonesia', 'A', 3, null],
            ['Matematika (Umum)', 'A', 4, null],
            ['Sejarah Indonesia', 'A', 5, null],
            ['Bahasa Inggris', 'A', 6, null],
            ['Seni Budaya', 'B', 7, null],
            ['Pendidikan Jasmani, Olahraga dan Kesehatan', 'B', 8, null],
            ['Bahasa Jawa', 'B', 9, null],
            ['Simulasi dan Komunikasi Digital', 'C1', 10, null],
            ['Fisika', 'C1', 11, null],
            ['Kimia', 'C1', 12, null],
            ['Kerja Bengkel dan Gambar Teknik', 'C2', 13, null],
            ['Dasar Listrik dan Elektronika', 'C2', 14, null],
            ['T. Pemograman. M. Prosesor dan M. Kontroler', 'C2', 15, null],
            ['Produk Kreatif dan Kewirausahaan', 'C3', 16, null],
            ['Penerapan Rangkaian Elektronika', 'C3', 17, null],
            ['Sistem Pengendali Elektronik', 'C3', 18, null],
            ['Mikroprosesor dan Mikrokontroler', 'C3', 19, 'X'],
            ['Mikroprosesor dan Mikrokontroler', 'C3', 19, 'XI'],
            ['Pengendali Sistem Robotik', 'C3', 20, null],
            ['Pembuatan, Perbaik dan Pemel Peralatan Elektronika', 'C3', 21, 'XII'],
        ];

        foreach ($daftar as [$nama, $kelompok, $urutan, $tingkat]) {
            Mapel::updateOrCreate(
                ['nama' => $nama, 'jurusan_id' => null, 'tingkat' => $tingkat],
                ['kelompok' => $kelompok, 'urutan' => $urutan, 'kkm_default' => 76]
            );
        }
    }
}
