<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        // RPL dan TEI = jurusan nyata dari file MASTER di Drive referensi.
        Jurusan::create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak', 'deskripsi' => 'Jurusan Rekayasa Perangkat Lunak.']);
        Jurusan::create(['kode' => 'TEI', 'nama' => 'Teknik Elektronika Industri', 'deskripsi' => 'Jurusan Teknik Elektronika Industri.']);

        // Dua jurusan di bawah ini CONTOH placeholder — sesuaikan/hapus
        // dengan jurusan yang benar-benar ada di SMKN 5 Surakarta.
        Jurusan::create(['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan (Contoh)', 'deskripsi' => 'CONTOH — sesuaikan dengan data sekolah.']);
        Jurusan::create(['kode' => 'TSM', 'nama' => 'Teknik Sepeda Motor (Contoh)', 'deskripsi' => 'CONTOH — sesuaikan dengan data sekolah.']);
    }
}
