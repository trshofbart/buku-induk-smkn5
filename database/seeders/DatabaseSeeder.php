<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TahunAjaranSeeder::class,
            JurusanSeeder::class,
            KelasSeeder::class,
            DataSekolahSeeder::class,
            ProfilSekolahSeeder::class,
            KategoriBeritaSeeder::class,
            SiswaSeeder::class,
            BeritaSeeder::class,
            PengumumanSeeder::class,
        ]);
    }
}
