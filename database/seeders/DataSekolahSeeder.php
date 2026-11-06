<?php

namespace Database\Seeders;

use App\Models\DataSekolah;
use Illuminate\Database\Seeder;

class DataSekolahSeeder extends Seeder
{
    public function run(): void
    {
        DataSekolah::create([
            'id' => 1,
            'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            'npsn' => '20328145',
            'alamat' => 'Jl. Adi Sucipto No. 33, Surakarta, Jawa Tengah',
            'telepon' => '(0271) 000000',
            'email' => 'info@smkn5surakarta.sch.id',
            'website' => 'https://smkn5surakarta.sch.id',
            'kepala_sekolah' => 'Kepala Sekolah (Contoh)',
        ]);
    }
}
