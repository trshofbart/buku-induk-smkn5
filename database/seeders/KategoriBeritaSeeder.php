<?php

namespace Database\Seeders;

use App\Models\KategoriBerita;
use Illuminate\Database\Seeder;

class KategoriBeritaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pengumuman', 'Kegiatan', 'Prestasi', 'PPDB'] as $nama) {
            KategoriBerita::create(['nama' => $nama, 'slug' => \Illuminate\Support\Str::slug($nama)]);
        }
    }
}
