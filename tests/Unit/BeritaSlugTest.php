<?php

namespace Tests\Unit;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_dibuat_dari_judul_dan_unik(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@smkn5.sch.id')->first();
        $kategori = KategoriBerita::first();

        $a = Berita::create([
            'kategori_berita_id' => $kategori->id, 'user_id' => $admin->id,
            'judul' => 'Judul Berita Tes', 'slug' => Berita::buatSlug('Judul Berita Tes'),
            'isi' => '<p>Isi.</p>', 'status' => 'draft',
        ]);
        $this->assertSame('judul-berita-tes', $a->slug);

        $b = Berita::create([
            'kategori_berita_id' => $kategori->id, 'user_id' => $admin->id,
            'judul' => 'Judul Berita Tes', 'slug' => Berita::buatSlug('Judul Berita Tes'),
            'isi' => '<p>Isi.</p>', 'status' => 'draft',
        ]);
        $this->assertSame('judul-berita-tes-2', $b->slug);
    }

    public function test_satu_nis_hanya_boleh_satu_record(): void
    {
        $this->seed();
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Siswa::create([
            'nis' => '20250001', 'nama' => 'Duplikat (Contoh)', 'jenis_kelamin' => 'L',
            'tahun_ajaran_id' => 13, 'jurusan_id' => 1, 'status' => 'aktif',
        ]);
    }
}
