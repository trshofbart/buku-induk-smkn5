<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\User;
use Illuminate\Database\Seeder;

/** 5 berita CONTOH. */
class BeritaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->first();
        $kat = fn (string $nama) => KategoriBerita::where('nama', $nama)->first()?->id;

        $list = [
            ['Kegiatan', 'Upacara Bendera dan Pembinaan Karakter di Awal Semester (Contoh)', 'Kegiatan upacara bendera rutin di awal semester genap sebagai pembinaan karakter dan kedisiplinan siswa.'],
            ['Prestasi', 'Siswa RPL Juara Lomba Kompetensi Siswa Tingkat Kota (Contoh)', 'Tim siswa jurusan RPL meraih juara pada lomba kompetensi siswa tingkat kota. Prestasi ini menjadi motivasi bagi seluruh siswa.'],
            ['Pengumuman', 'Jadwal Pembagian Rapor Semester Genap (Contoh)', 'Pembagian rapor semester genap akan dilaksanakan sesuai jadwal yang diumumkan wali kelas masing-masing. Orang tua/wali dimohon hadir.'],
            ['PPDB', 'Penerimaan Peserta Didik Baru Tahun Ajaran 2026/2027 Dibuka (Contoh)', 'PPDB SMK Negeri 5 Surakarta tahun ajaran 2026/2027 segera dibuka. Informasi syarat dan jadwal pendaftaran akan diumumkan di halaman ini.'],
            ['Kegiatan', 'Kunjungan Industri ke Perusahaan Teknologi (Contoh)', 'Siswa kelas XI melaksanakan kunjungan industri untuk melihat langsung penerapan teknologi di dunia kerja.'],
        ];

        foreach ($list as $i => [$kategori, $judul, $ringkasan]) {
            Berita::create([
                'kategori_berita_id' => $kat($kategori),
                'user_id' => $admin->id,
                'judul' => $judul,
                'slug' => Berita::buatSlug($judul),
                'ringkasan' => $ringkasan,
                'isi' => '<p>'.$ringkasan.'</p><p>Isi lengkap berita contoh ini bisa diedit Admin TU dari menu Berita. Tuliskan isi berita yang sebenarnya di sini, tambahkan foto kegiatan, dan atur status menjadi Publish agar tampil di halaman depan.</p>',
                'status' => 'publish',
                'published_at' => now()->subDays(5 - $i),
                'views' => ($i + 1) * 17,
            ]);
        }

        // Satu berita draft (tidak tampil di publik).
        Berita::create([
            'kategori_berita_id' => $kat('Kegiatan'),
            'user_id' => $admin->id,
            'judul' => 'Draf Berita — Belum Diterbitkan (Contoh)',
            'slug' => 'draf-berita-belum-diterbitkan-contoh',
            'ringkasan' => 'Berita ini masih draft, jadi tidak tampil di halaman depan.',
            'isi' => '<p>Contoh berita berstatus draft.</p>',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
