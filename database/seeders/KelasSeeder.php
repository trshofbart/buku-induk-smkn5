<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $aktif = TahunAjaran::where('nama', '2025-2026')->first();
        $lama = TahunAjaran::where('nama', '2021-2022')->first();

        // Kelas untuk tahun ajaran aktif (2025-2026): tiap jurusan, tingkat X-XIII.
        foreach (Jurusan::all() as $jurusan) {
            foreach (['X', 'XI', 'XII', 'XIII'] as $tingkat) {
                Kelas::create([
                    'tahun_ajaran_id' => $aktif->id,
                    'jurusan_id' => $jurusan->id,
                    'tingkat' => $tingkat,
                    'nama' => $tingkat.' '.$jurusan->kode.' A',
                    'wali_kelas' => null,
                ]);
            }
        }

        // Kelas lama sesuai Drive referensi (2021-2022): XII & XIII.
        foreach (Jurusan::whereIn('kode', ['RPL', 'TEI'])->get() as $jurusan) {
            foreach (['XII', 'XIII'] as $tingkat) {
                Kelas::create([
                    'tahun_ajaran_id' => $lama->id,
                    'jurusan_id' => $jurusan->id,
                    'tingkat' => $tingkat,
                    'nama' => $tingkat.' '.$jurusan->kode.' A',
                    'wali_kelas' => null,
                ]);
            }
        }
    }
}
