<?php

namespace Database\Seeders;

use App\Models\Pengumuman;
use Illuminate\Database\Seeder;

/** 3 pengumuman CONTOH. */
class PengumumanSeeder extends Seeder
{
    public function run(): void
    {
        Pengumuman::create([
            'judul' => 'Libur Akhir Semester (Contoh)',
            'isi' => 'Libur akhir semester dimulai sesuai kalender pendidikan. Kegiatan belajar mengajar kembali pada tanggal yang diumumkan wali kelas.',
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addDays(14),
            'is_active' => true,
        ]);

        Pengumuman::create([
            'judul' => 'Pengumpulan Dokumen Siswa Baru (Contoh)',
            'isi' => 'Siswa baru dimohon mengumpulkan fotokopi akta kelahiran dan kartu keluarga ke Tata Usaha paling lambat minggu depan.',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDays(7),
            'is_active' => true,
        ]);

        Pengumuman::create([
            'judul' => 'Pemeliharaan Sistem Buku Induk (Contoh)',
            'isi' => 'Sistem Buku Induk akan menjalani pemeliharaan rutin. Selama pemeliharaan, layanan pencatatan tetap berjalan seperti biasa.',
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDay(),
            'is_active' => false,
        ]);
    }
}
