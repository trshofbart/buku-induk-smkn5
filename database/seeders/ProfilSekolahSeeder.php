<?php

namespace Database\Seeders;

use App\Models\ProfilSekolah;
use Illuminate\Database\Seeder;

/**
 * Isi halaman profil CONTOH — admin wajib menyesuaikan dengan
 * profil resmi SMKN 5 Surakarta sebelum dipakai publik.
 */
class ProfilSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $halaman = [
            [
                'slug' => 'sejarah', 'judul' => 'Sejarah Sekolah', 'urutan' => 1,
                'isi' => '<p><strong>Contoh isi — silakan sesuaikan dengan sejarah resmi sekolah.</strong></p><p>SMK Negeri 5 Surakarta adalah sekolah menengah kejuruan negeri di Kota Surakarta, Jawa Tengah. Halaman ini berisi sejarah berdirinya sekolah, perkembangan jurusan, dan tonggak penting sekolah dari tahun ke tahun.</p>',
            ],
            [
                'slug' => 'visi-misi', 'judul' => 'Visi & Misi', 'urutan' => 2,
                'isi' => '<p><strong>Visi (contoh):</strong></p><p>Menjadi SMK unggul yang menghasilkan lulusan kompeten, berkarakter, dan siap kerja.</p><p><strong>Misi (contoh):</strong></p><ol><li>Menyelenggarakan pendidikan kejuruan yang berkualitas.</li><li>Membangun karakter disiplin, jujur, dan tanggung jawab.</li><li>Menjalin kemitraan dengan dunia usaha dan dunia industri.</li></ol>',
            ],
            [
                'slug' => 'sambutan-kepala-sekolah', 'judul' => 'Sambutan Kepala Sekolah', 'urutan' => 3,
                'isi' => '<p><strong>Contoh isi sambutan — silakan sesuaikan.</strong></p><p>Selamat datang di website SMK Negeri 5 Surakarta. Website ini kami hadirkan sebagai jendela informasi sekolah: profil, berita, dan layanan administrasi, termasuk pencatatan Buku Induk siswa yang kini dikelola secara digital.</p>',
            ],
            [
                'slug' => 'struktur-organisasi', 'judul' => 'Struktur Organisasi', 'urutan' => 4,
                'isi' => '<p><strong>Contoh isi — silakan sesuaikan dengan struktur resmi.</strong></p><p>Kepala Sekolah, Wakil Kepala Sekolah (Kurikulum, Kesiswaan, Sarana Prasarana, Humas), Kepala Tata Usaha, Ketua Jurusan (RPL, TEI, dan jurusan lainnya), serta Wali Kelas.</p>',
            ],
            [
                'slug' => 'guru-staff', 'judul' => 'Guru & Staff', 'urutan' => 5,
                'isi' => '<p><strong>Contoh isi — silakan isi data guru dan staff.</strong></p><p>Daftar guru dan tenaga kependidikan SMK Negeri 5 Surakarta, lengkap dengan jurusan dan mata pelajaran yang diampu.</p>',
            ],
            [
                'slug' => 'fasilitas', 'judul' => 'Fasilitas', 'urutan' => 6,
                'isi' => '<p><strong>Contoh isi — silakan sesuaikan.</strong></p><ul><li>Laboratorium komputer dan praktik jurusan</li><li>Perpustakaan</li><li>Masjid / ruang ibadah</li><li>Lapangan olahraga</li><li>Unit Kesehatan Sekolah (UKS)</li></ul>',
            ],
            [
                'slug' => 'kontak', 'judul' => 'Kontak', 'urutan' => 7,
                'isi' => '<p><strong>SMK Negeri 5 Surakarta</strong></p><p>Alamat: Jl. Adi Sucipto No. 33, Surakarta, Jawa Tengah<br>Telepon: (0271) 000000<br>Email: info@smkn5surakarta.sch.id</p>',
            ],
        ];

        foreach ($halaman as $h) {
            ProfilSekolah::create($h + ['is_active' => true]);
        }
    }
}
